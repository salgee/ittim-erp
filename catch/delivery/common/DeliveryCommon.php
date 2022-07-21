<?php
// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~{$year} http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace catchAdmin\delivery\common;

use catchAdmin\basics\model\Shop;
use catchAdmin\basics\model\ZipCode as zipCodeModel;
use catchAdmin\basics\model\ShopWarehouse as swModel;
use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\Warehouses as warModel;
use catchAdmin\warehouse\model\WarehouseStock as whsModel;
use catchAdmin\warehouse\model\OutboundOrders as oboModel;
use catchAdmin\warehouse\model\OutboundOrderProducts as obopModel;
use catchAdmin\order\model\OrderRecords as orsModel;
use catchAdmin\product\model\ProductInfo as pinfoModel;
use catchAdmin\basics\model\LogisticsFeeConfig as lfcModel;
use catchAdmin\basics\model\ZipCodeSpecial as zcsModel;
use catchAdmin\basics\model\OrderFeeSetting as ofsModel;
use catchAdmin\basics\model\StorageFeeConfig as sfcModel;
use catchAdmin\basics\model\StorageFeeConfigInfo as sfciModel;
use catchAdmin\product\model\ProductGroup as pgModel;
use catchAdmin\basics\model\Company as cModel;
use catchAdmin\order\model\OrderItemRecords as oriModel;
use catcher\exceptions\FailedException;
use catchAdmin\basics\model\CompanyAmountLog as cal;
use catchAdmin\order\model\OrderDeliver as odeModel;
use catchAdmin\system\model\Config;
use catchAdmin\product\model\Product;
use catchAdmin\order\model\OrderBuyerRecords as obrModel;
use catchAdmin\store\model\Platforms;
use catchAdmin\order\model\OrderDeliverProducts;

class DeliveryCommon
{
    /**
     * 获取货运包裹详细信息//判断type类型是否返回手工发货
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getOrderInfo($id, $type, $uid)
    {
        $item = new odeModel;
        $itemData = $item->where('id', $id)->find();
        if (isset($itemData)) {
            //判断是否全部发货
            $odCount = $item->where('order_record_id', $itemData->order_record_id)->whereNotIn('delivery_state', '6')->count();
            $hasTrackingCount = $item->where('order_record_id', $itemData->order_record_id)->whereNotIn('delivery_state', '1,6')->count();
            if(!empty($odCount)) {
                $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;
                $orsModel = new orsModel;
                $orsModel->where('id', $itemData->order_record_id)->update(['status' => $orderStatus]);
            }

            $itemObj = $this->getOrderDeliverInfo($itemData);
            if ($type == 1) { //不需要手工发货
                $getlDelivery = null;
                return $itemObj;
            } elseif ($type == 2) { //需要手工发货
                $getlDelivery = $this->getManualDelivery($itemObj, $uid);
                return $getlDelivery;
            }
        }
    }
    /**
     * 查看包裹详细信息
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getOrderDeliverInfo($item)
    {
        $item = $item->toArray();
        //判断包裹存在库存id 则查询包裹仓库名称
        if (isset($item['en_id']) && $item['en_id'] > 0 && isset($item['vi_id']) && $item['vi_id'] > 0) {
            $en_name = (new warModel())->field('name')->where('id', $item['en_id'])->find()->toArray();
            $vi_name = (new warModel())->field('name')->where('id', $item['vi_id'])->find()->toArray();
            $item['en_name'] = $en_name['name'];
            $item['$vi_name'] = $vi_name['name'];
        }
        //对订单表查讯信息
        $field = "currency,total_price,platform_no,platform,shop_basics_id";
        $p_order_no = (new orsModel())->field($field)->where('id', $item['order_record_id'])->find();
        $item['platform_no'] = $p_order_no->platform_no;
        $item['platform'] = $p_order_no->platform;
        $item['total_price'] = $p_order_no->total_price;
        $item['currency'] = $p_order_no->currency;
        //包裹创建人
        $user_name = (new Users())->field('username')->where('id', $item['creator_id'])->find();
        // var_dump('>>>>>>>', $item['updater_id']);
        // exit;
        //判断是否存在包裹信息审核人
        if (!empty($item['updater_id'])) {
            $up_name = (new Users())->field('username')->where('id', $item['updater_id'])->find();
            $item['up_name'] = $up_name->username;
        }
        $item['creator_name'] = $user_name->username;
        //查询店铺名称
        if ($p_order_no->shop_basics_id) {
            $shop_name = (new Shop())->field('shop_name')->where('id', $p_order_no->shop_basics_id)->find();
            $item['shop_name'] = $shop_name->shop_name;
        }
        // 补货订单
        $product = new Product;
        if ($item['order_type_source'] == 1) {
            //商品名称及商品编码
            $product = $product->where('id', $item['goods_id'])->find();
            $item['packing_method'] = $product->packing_method ?? 1;
        } else {
            $item['packing_method'] = -1;
        }

        // $item['goods_name_ch'] = $product->name_ch;
        // $item['goods_name_en'] = $product->name_en;
        // $item['goods_code'] = $product->code;
        // $item['category_name'] = $product->category_name;
        // 订单商品表种查询
        $oriModel = new oriModel;
        if ($item['order_type_source'] == 1) {
            $item['product'] = $oriModel->where('order_record_id', $item['order_record_id'])
                ->select()->each(function ($val) {
                    $val['warehouse_name'] = warModel::where('id', $val['warehouse_id'])->value('name');

                    $product = new Product;
                    $val['goods_code'] = $product->where('id', $val['goods_id'])->value('code');
                });
            //查询购买用户联系方式
            $order_by_record = new obrModel;
            $order_by_records = $order_by_record->where(['order_record_id' => $item['order_record_id'], 'is_disable' => 1, 'type' => 0])->find();
            if (isset($order_by_records)) {
                $item['user_addres'] = $order_by_records->toArray();
            }
            $order_by_records_replenishment = $order_by_record->where(['order_record_id' => $item['order_record_id'], 'is_disable' => 1, 'type' => 1])->find();
            if (isset($order_by_records_replenishment)) {
                $item['user_addres_replenishment'] = $order_by_records_replenishment->toArray();
            }

            // 获取发货成功订单商品信息
            $item['product_deliver'] = OrderDeliverProducts::where('order_deliver_id', $item['id'])
                ->select()->each(function (&$item) {
                    $product = new Product;
                    $product = $product->where('id', $item['goods_id'])->find();
                    $item['packing_method'] = $product['packing_method'];
                    $item['transaction_price_all'] = bcadd((bcmul((string)$item['transaction_price_value'], (string)$item['number'], 2)), $item['tax_amount_value'], 2);
                });
        } else {
            $item['product'] = $oriModel->where(['after_order_id' => $item['after_order_id'], 'type' => 1])
                ->select()->each(function ($val) {
                    $val['warehouse_name'] = warModel::where('id', $val['warehouse_id'])->value('name');
                });
            //查询购买用户联系方式
            $order_by_record = new obrModel;
            $order_by_records = $order_by_record->where(['after_sale_id' => $item['after_order_id'], 'is_disable' => 1, 'type' => 1])->find();
            if (isset($order_by_records)) {
                $item['user_addres'] = $order_by_records->toArray();
            }
            $order_by_records_replenishment = $order_by_record->where(['after_sale_id' => $item['after_order_id'], 'is_disable' => 1, 'type' => 1])->find();
            if (isset($order_by_records_replenishment)) {
                $item['user_addres_replenishment'] = $order_by_records_replenishment->toArray();
            }
            // 获取发货成功订单商品信息
            $item['product_deliver'] = OrderDeliverProducts::where('order_deliver_id', $item['id'])
                ->select()->each(function (&$item) {
                    $product = new Product;
                    $product = $product->where('id', $item['goods_id'])->find();
                    $item['packing_method'] = $product['packing_method'];
                    $item['transaction_price_all'] = bcadd((bcmul((string)$item['transaction_price_value'], (string)$item['number'], 2)), $item['tax_amount_value'], 2);
                });
        }
        return $item;
    }

    /**
     * 发货列表条件筛选
     * $data['server_type'] 1-发货单号/2-订单编号/3-实体仓库/4-虚拟仓库/5-平台订单编号/6-店铺名称/7-平台名称
     * $data['state']1-待发货（订单没有转为发货单时的状态）2-已发货（订单已经转发货单时的状态）3-运输中（订单对应的发货单打印物流面单后的状态）4-配送中（订单对应的发货单物流在配送中状态）5-已收货（订单对应的发货单物流单用户已收货）
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderDeliverList($data)
    {
        //对客户的搜索条件进行判断组合。发货单号/订单编号/实体仓库/虚拟仓库/平台订单编号/店铺名称/平台名称
        if (isset($data['service_type']) && $data['service_type'] == 1 && !empty($data['value'])) { //发货单号
            $where_key = 'invoice_no';
            $where_factor = 'like';
            $where_value = '%' . $data['value'] . '%';
        } elseif (isset($data['service_type']) && $data['service_type'] == 2 && !empty($data['value'])) { //订单编号
            $where_key = 'order_no';
            $where_factor = 'like';
            $where_value = '%' . $data['value'] . '%';
        } elseif (isset($data['service_type']) && $data['service_type'] == 3 && !empty($data['value'])) { //实体仓库
            $warehouse = new warModel();
            $warehouse = $warehouse->whereLike('name', $data['value'])->where('parent_id', 0)->column('id');
            if (isset($warehouse)) {
                $stringData = implode(',', $warehouse);
                $where_key = 'en_id';
                $where_factor = '=';
                $where_value = $stringData;
            } else {
                $where_key = 'en_id';
                $where_factor = '<>';
                $where_value = 0;
            }
        } elseif (isset($data['service_type']) && $data['service_type'] == 4 && !empty($data['value'])) { //虚拟仓库
            $warehouse = new warModel();
            $warehouse = $warehouse->whereLike('name', $data['value'])->where('parent_id', '<>', 0)->column('id');
            if (isset($warehouse)) {
                $stringData = implode(',', $warehouse);
                $where_key = 'vi_id';
                $where_factor = 'in';
                $where_value = $stringData;
            } else {
                $where_key = 'vi_id';
                $where_factor = '<>';
                $where_value = 0;
            }
        } elseif (isset($data['service_type']) && $data['service_type'] == 5 && !empty($data['value'])) { //平台订单编号
            $where_key = 'platform_no';
            $where_factor = 'like';
            $where_value = '%' . $data['value'] . '%';
        } elseif (isset($data['service_type']) && $data['service_type'] == 6 && !empty($data['value'])) { //店铺名称
            $shop_name = (new Shop())->whereLike('shop_name', $data['value'])->column('id');
            if (isset($shop_name)) {
                $stringData = implode(',', $shop_name);
                $where_key = 'shop_basics_id';
                $where_factor = 'in';
                $where_value = $stringData;
            } else {
                $where_key = 'shop_basics_id';
                $where_factor = '<>';
                $where_value = 0;
            }
        } elseif (isset($data['service_type']) && $data['service_type'] == 7 && !empty($data['value'])) { //平台名称
            $platform_name = (new Platforms())->whereLike('name', $data['value'])->column('id');
            if (isset($platform_name)) {
                $stringData = implode(',', $platform_name);
                $where_key = 'platform_id';
                $where_factor = 'in';
                $where_value = $stringData;
            } else {
                $where_key = 'platform_id';
                $where_factor = '<>';
                $where_value = 0;
            }
        } else {
            $where_key = 'id';
            $where_factor = '<>';
            $where_value = 0;
        }
        $array[0] = [$where_key, $where_factor, $where_value];
        //1-待发货（订单没有转为发货单时的状态）2-已发货（订单已经转发货单时的状态）
        //3-运输中（订单对应的发货单打印物流面单后的状态）4-配送中（订单对应的发货单物流在配送中状态）
        //5-已收货（订单对应的发货单物流单用户已收货）
        if (isset($data['order_state']) && $data['order_state'] > 0) {
            $state_key = 'delivery_state';
            $state_factor = '=';
            $state_value = $data['order_state'];
        } else {
            $state_key = 'id';
            $state_factor = '<>';
            $state_value = 0;
        }
        $array[1] = [$state_key, $state_factor, $state_value];
        //对页面列表类型
        //type=1未打印订单列表;type=2异常发货订单列表;type=3异常物流处理列表
        if (isset($data['type']) && $data['type'] < 3) {
            $logistics_key = 'logistics_status';
            $logistics_factor = '=';
            $logistics_value = $data['type'];
        } elseif (isset($data['type']) && isset($data['type']) == 3) {
            // var_dump('===>>>>>>'); exit;
            $logistics_key = 'deliver_day';
            $logistics_factor = '<';
            $logistics_value = strtotime('-7 days');
        }
        $array[2] = [$logistics_key, $logistics_factor, $logistics_value];
        //判断订单是否打印发货单1-未打印;2；打印
        //delivery_process_status:发货过程状态（0未确认发货单，1以确认发货单，2获取物流单号，3打印物流面单）
        if (isset($data['delivery_type']) && $data['delivery_type'] == 1) {
            $delivery_key = 'delivery_process_status';
            $delivery_factor = '=';
            $delivery_value = 1;
        } else if (isset($data['delivery_type']) && $data['delivery_type'] == 2) {
            $delivery_key = 'delivery_process_status';
            $delivery_factor = '=';
            $delivery_value = 2;
        }
        $array[3] = [$delivery_key, $delivery_factor, $delivery_value];
        return $array;
    }


    //-----------------------------分割线--------------------- 

    /**
     * 对批量发货接口传来的订单id进行分类
     * 区分：可发订单：不可发订单
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderSort($id)
    {
        $delivery_y = array(); //验证成功可发货
        $delivery_n = ''; //异常订单，信息不足
        $delivery_u = ''; //客户余额不足小于0
        foreach ($id as $k => $v) {
            $data = $this->getOrderDelivery($v);
            //对订单进行校验，成功则带着st=1与订单及商品信息，
            if (isset($data['st']) && $data['st'] == 1 || isset($data['qs'])) {
                $delivery_y[$k] = $data;  //可以发货的订单
            } elseif (isset($data['st']) && $data['st'] == 2) {
                $delivery_u .= $v . ','; //客户资金不足
            } elseif (!isset($data['st'])) { //异常订单号
                $delivery_n .= $v . ',';
            }
        }
        return array($delivery_y, $delivery_n, $delivery_u);
    }

    /**
     * 发货订单需要的信息
     * @return mixed|\think\Paginator
     * @param $id 订单id
     * @throws \think\db\exception\DbException
     */
    public function getOrderDelivery($id)
    {
        $data = new orsModel;
        //查询订单关联的产品详细信息
        $data = $data->orderGoods($id);
        // var_dump('111>>>>>>>>>',  $data); exit;
        if (isset($data)) {
            $data = $data->toArray();
            $userAmount = new cModel; // 公司（客户）
            //检查用户的余额是否小于等于0 需求：发货时，若订单明细中若商品所属客户额度<=0时，需给予提示，不可发货
            $userAmount = $userAmount->getUserAmount($data['company_id']);
            if (isset($userAmount)) {
                //对订单的仓库进行远近/库存筛选
                $data = array_merge($data, $this->freightAmount($data, 1));
                // var_dump('>>>>>>11111', $data); exit;
            } else {
                $data['st'] = 2; // 资金不足
                throw new FailedException('客户金额不足，请先增加额度');
            }
        } else {
            throw new FailedException('请检查基础商品，发货商品');
        }
        return $data;
    }

    /**
     * 传入订单信息对商品的仓库远近进行排序
     *
     * @return mixed|\think\Paginator
     * @param $data 订单详情， 
     * @param $t 1-自动发货 1-手工发货
     * @throws \think\db\exception\DbException
     */
    public function freightAmount($data, $t)
    {
        //查找店家关联的虚拟仓库【多个】
        $shop = new swModel;
        $shop = $shop->getOrderWarehouse($data['shop_basics_id'])->each(function ($item) use ($data) {
            $stock = new warModel;
            //循环查找仓库邮编
            $stock = $stock->warehouseGoods($item->warehouse_fictitious_id, $item->warehouse_id);
            if (isset($stock)) {
                //循环查找对应的仓库邮编和订单邮编
                $dest = new zipCodeModel;
                $dest = $dest->selZipzone($data['address_postalcode'], $stock->zipcode);
                $item->zone = $dest->zone ?? 0;
                $item['warehouse_zipcode'] = $stock->zipcode;
            }
        });
        if (isset($shop) && !$shop[0]['warehouse_zipcode']) {
            throw new FailedException('请查看仓库是否被禁用');
        }
        //对关联仓库距离进行排序
        if (isset($shop) && ($shop[0]['zone'] != 0)) {
            $shop = $shop->toArray();
            $news_key = array_column($shop, 'zone');
            array_multisort($news_key, SORT_ASC, $shop);
            //对仓库进行库存判断
            $data = $this->inventoryCheck($data, $shop, $t);
        } else {
            throw new FailedException('请设置订单邮编分区' . '订单邮编' . $data['address_postalcode'] . ',仓库邮编' . $shop[0]['warehouse_zipcode']);
        }

        return $data;
    }

    /**
     * 对由近到远仓库的库存商品进行对比。
     * $data 订单商品信息
     * $shop 商品关联仓库信息
     * $t 非手工发货-1;手工发货;2
     * @param $order
     * @param $transaction
     * @return mixed
     */
    public function inventoryCheck($data, $shop, $t)
    {
        foreach ($shop as $k => $v) {
            //查看对应仓库产品的库存
            $mateModel = new whsModel;
            $arr = [
                $v['warehouse_id'],
                $v['warehouse_fictitious_id'],
                $data['goods_code'],
                $data['quantity_purchased']
            ];
            //查看库内存商品的数量 （获取符合条件）
            $mate = $mateModel->warehouseNumber($arr);

            if ($mate && isset($mate)) {
                $mate = $mate->toArray();
                $obo = new oboModel;
                // 固定商品出库单查询 // 该仓库的出库单查询
                $obo = $obo->getWarehosebob($v['warehouse_id'], $v['warehouse_fictitious_id'], $data['goods_id']);
                // 获取库存总数量
                $mate_num = array_sum(array_column($mate, 'number'));
                // 存本仓库内的商品申请单需要相加后对比是否库存足够
                if (isset($obo) && count($obo->toArray()) > 0) {
                    $obo_arr = $obo->toArray();
                    $o_id = array();
                    foreach ($obo_arr as $ks => $vs) {
                        $o_id[$ks] = $vs['id'];
                    }
                    $obopmodel = new obopModel;
                    //获取到出库单商品申请中的数量 （出库单id集合 $o_id）
                    $obop = $obopmodel->obopSumNumber($o_id, $data['goods_id'], $mate);
                    // 获取最后的出库批次及数量
                    $obopm = $obopmodel->obopgoods($o_id, $data['goods_id'], $mate);
                    $num = bcsub(strval($mate_num), strval($obop), 0);
                    var_dump($num);
                    exit;
                    $array = [
                        $data, //订单信息
                        $num, //仓库内剩余的商品数量
                        $data['quantity_purchased'], //订单购买数量
                        $v['warehouse_id'], //主仓库id
                        $v['warehouse_fictitious_id'], //虚拟仓库id
                        $v['zone'], //仓库与用户邮编的远近
                        $obop,
                        $data['goods_code'], // 商品编码
                    ];
                    if ($t == 1) {
                        $data_arr = $this->contrast($array, $obopm, $t);
                        continue 1;
                    } elseif ($t == 2) {
                        $data_arr[$k] = $this->contrast($array, $obopm, $t);
                    }
                } else { //无出库申请单直接对比
                    $array = [
                        $data, //订单信息
                        $mate_num, //仓库内的商品数量
                        $data['quantity_purchased'],
                        $v['warehouse_id'],
                        $v['warehouse_fictitious_id'],
                        $v['zone'],
                        0,
                        $data['goods_code'],
                    ];
                    //获取最后的出库批次及数量
                    $obopmodel = new whsModel;
                    $obopm = $obopmodel->obopGoods($arr);
                    if ($t == 1) {
                        $data_arr = $this->contrast($array, $obopm, $t);
                        //匹配到合适的终止循环。
                        continue 1;
                    } elseif ($t == 2) {
                        //手工发货需获取所有仓库
                        $data_arr[$k] = $this->contrast($array, $obopm, $t);
                    }
                }
            } else { //存在仓库但是数量不足
                // var_dump('=====`111111======', $t); exit;
                if ($t == 2) {
                    $array_k['en'] = $v['warehouse_id'];
                    $array_k['vi'] = $v['warehouse_fictitious_id'];
                    $array_k['st'] = 1;
                    $array_k['ckarray'] = 1;
                    $array_k['zone'] = $v['zone'];
                    $array_k['warehouses_id'] = $v['warehouse_fictitious_id'];
                    $array_k['qs'] = 2; //库存不足
                    $data_arr[$k] = $array_k;
                    unset($array_k);
                } else {
                    // throw new FailedException('商品库存不足'.'商品编码='. $data['goods_code'].'商品id='. $data['goods_id'].'虚拟仓库id='. $v['warehouse_fictitious_id']);
                }
            }
        }
        if (!isset($data_arr) && $t == 1) {
            $data_arr['qs'] = 2; //无仓库
        }
        return $data_arr;
    }

    /**
     * 对订单商品数量与仓库库存数量进行对比。
     * @param $order
     * @param $transaction
     * @return mixed
     */
    public function contrast($array, $res, $t)
    {
        if ($array[1] >= $array[2]) {
            //计算可出库商品的在库天数
            $data['warehoseOrder'] = $this->getGoodsWarehose($array, $res);
            $data['en'] = $array[3];
            $data['vi'] = $array[4];
            $data['st'] = 1;
            $data['ckarray'] = 1;
            $data['zone'] = $array[5];
            $data['warehouses_id'] = $array[4];
            $data['qs'] = 1; //判断是库存 仓库存在
        } else {
            if ($t == 2) {
                $data['en'] = $array[3];
                $data['vi'] = $array[4];
                $data['st'] = 1;
                $data['ckarray'] = 1;
                $data['zone'] = $array[5];
                $data['warehouses_id'] = $array[4];
                $data['qs'] = 2; //库存不足
            }
        }
        return $data;
    }

    /**
     * 计算可出库商品的在库天数 确定此订单出自哪个入库批次
     * @param $order
     * @param $transaction
     * @return mixed
     */
    public function getGoodsWarehose($arr, $data)
    {

        //存在申请出库单的计算方式
        // $arr[3]-主仓库id,  $arr[4]-虚拟仓库id, $arr[7]-商品编码
        $mate = new whsModel;
        $array = array($arr[3], $arr[4], $arr[7], $data[0]);
        //出库单数据
        $mate = $mate->warehouseGoodsNumber($array, 2)->toArray();
        $res = array();
        foreach ($mate as $k => $v) {
            if ($k == 0) { //判断本批次扣除审核数量是否还有商品
                $num = $v['number'] - $data[1]; // 批次总数减掉审核数量如果
                if (isset($num) && $num > 0) { //商品数量存在的情况下进行扣除
                    $no_num = $arr[2] - $num; //判断购买的商品数量减掉本批次的剩余数量是否大于0
                    if ($no_num > 0) { //本批次的货品数量不够则扣除所有数量
                        $res[$k]['num'] = $num;
                        $res[$k]['no'] = $v['batch_no'];
                        $res[$k]['t'] = $v['created_at'];
                    } else { //本批次足以扣除求购数量结束循环
                        $res[$k]['num'] = $num;
                        $res[$k]['no'] = $v['batch_no'];
                        $res[$k]['t'] = $v['created_at'];
                        break;
                    }
                } else {
                    continue; //小于或者等于0则本批次不在扣除商品数量
                }
            } elseif ($k > 0) {
                //计算已扣除后的商品数量
                $num = $arr[2] - array_sum(array_column($res, 'num'));
                $no_num = $num - $v['number'];
                if ($no_num > 0) { //本批次的货品数量不够则扣除所有数量
                    $res[$k]['num'] = $v['number'];
                    $res[$k]['no'] = $v['batch_no'];
                    $res[$k]['t'] = $v['created_at'];
                } else { //本批次足以扣除求购数量结束循环
                    $res[$k]['num'] = $num;
                    $res[$k]['no'] = $v['batch_no'];
                    $res[$k]['t'] = $v['created_at'];
                    break;
                }
            }
        }
        unset($mate, $no_num, $array);
        return array_values($res);
    }

    /**
     * 对可发订单进行发货处理
     * @return mixed|\think\Paginator
     * @param $delivery_y 发货订单集合
     * @throws \think\db\exception\DbException
     */
    public function processDelivery($delivery_y, $id)
    {
        // 获取时候自动发货 1-自动发货  2-手工发货
        $config_deliver = Config::where(['key' => 'order.delivery'])->value('value');
        foreach ($delivery_y as $k => $v) {
            // 异常发货是不需要做出库单处理 
            if (isset($config_deliver) && $config_deliver == 1 && $v['qs'] == 1) {
                if ($v['packing_method'] == 1) {
                    //单箱商品计算
                    $delivery_y[$k] = $this->alone($v, 1);
                } elseif ($v['packing_method'] == 2) {
                    //多箱商品计算
                    $delivery_y[$k] = $this->alone($v, 2);
                }
                //商品出库单处理
                $this->outBoundOrder($delivery_y[$k]);

                //订单主表状态修改
                $order_array = [
                    'order_storage_fee' => $delivery_y[$k]['warehouse_price'], //订单总仓储费
                    'order_operation_fee' => $delivery_y[$k]['order_price'], //订单总操作费
                    'order_logistics_fee' => $delivery_y[$k]['freight_price'], //订单总物流费
                    'status' => 1, // 发货中
                    'logistics_status' => 1, // // 发货状态（0-未发货订单，1-成功发货订单，2-异常发货订单）
                    'updated_at' => time()
                ];
                //订单商品表修改
                $order_info_array = [
                    'warehouse_id' => $delivery_y[$k]['warehouses_id'], //发货仓库id
                    'updated_at' => time()
                ];
                $str = '订单id:' . $delivery_y[$k]['id'] . '修改订单商品异常';
                $this->orderInfoRevise($delivery_y[$k]['id'], $order_info_array, $str);
                //对用户余额进行扣费
                $amount = bcadd(strval($delivery_y[$k]['order_price']), bcadd(strval($delivery_y[$k]['warehouse_price']), strval($delivery_y[$k]['freight_price']), 4), 4);
                $str[0] = '订单id:' . $delivery_y[$k]['id'] . '修改用户余额异常';
                $str[1] = '订单id:' . $delivery_y[$k]['id'] . '用户余额记录异常';
                $this->deduction($delivery_y[$k]['company_id'], $amount, $str);
                $t = 1;
            } else {
                //订单主表状态修改
                $order_array = [
                    'order_storage_fee' => 0, //订单总仓储费
                    'order_operation_fee' => 0, //订单总操作费
                    'order_logistics_fee' => 0, //订单总物流费
                    'logistics_status' => 2, // // 发货状态（0-未发货订单，1-成功发货订单，2-异常发货订单）
                    'updated_at' => time()
                ];
                $t = 2;
            }
            //订单主表状态修改
            $str = '订单id:' . $delivery_y[$k]['id'] . '修改订单异常';
            $this->orderRevise($delivery_y[$k]['id'], $order_array, $str);
            //订单包裹信息入库
            $str = '订单id:' . $delivery_y[$k]['id'] . '订单包裹入库异常';
            $this->orderDeliverSave($delivery_y[$k], $str, $t, $id);
        }
    }

    /**
     * 计算单包裹的金额
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function alone($data, $type)
    {
        $field = 'volume_weight_AS,weight_gross_AS,oversize,length_AS,width_AS,height_AS,volume_AS';
        if ($type == 1) { //单箱包裹计算方式
            $pinfo = new pinfoModel;
            $pinfo = $pinfo->getPinfo($data['goods_id'], $field);
            //计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
            if ($pinfo['volume_weight_AS'] > $pinfo['weight_gross_AS']) {
                $Weight = $pinfo['volume_weight_AS']; //体积
            } else {
                $Weight = $pinfo['weight_gross_AS']; //毛重
            }
            //将订单信息  商品最大的重量 商品的规格参数传入进行计算
            $amount = $this->weightAlgorithm($data, $Weight, $pinfo);
            $data['freight_price'] = $amount[0]; //物流费
            $data['order_price'] = $amount[1]; //订单操作费
            $data['warehouse_price'] = $amount[2]; //仓储费
            $data['deliver_info'] = $amount[3]; //发货商品详细信息
        } elseif ($type == 2) { //多箱包裹计算方式
            //获取商品的分组规格参数
            $pinfo = new pgModel;
            $pinfo = $pinfo->getInfoGroup($data['goods_id'], $field);
            if (isset($pinfo)) {
                $pinfo = $pinfo->toArray();
                //获取单包裹的规格参数【由多个分组的相加】
                $weight = array_sum(array_column($pinfo, 'volume_weight_AS')); //体积
                $gross = array_sum(array_column($pinfo, 'weight_gross_AS'));; //毛重
                //计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
                if ($weight > $gross) {
                    $o_weight = $weight; //体积
                } else {
                    $o_weight = $gross; //毛重
                }
                //将订单信息  商品最大的重量 商品的规格参数传入进行计算
                $amount = $this->weightAlgorithm($data, $o_weight, $pinfo);
                $data['freight_price'] = $amount[0];
                $data['order_price'] = $amount[1];
                $data['warehouse_price'] = $amount[2];
                $data['freight_weight_price'] = $amount[3]['mbox_weight_amount']; //发货商品物流重量费
                $data['freight_additional_price'] = $amount[3]['mbox_additional']; //发货商品附加费
            }
        }
        return $data;
    }

    /**
     * 计算包裹内的商品数量在计算包裹费用
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function weightAlgorithm($data, $Weight, $oversize)
    {
        //单箱类型
        if ($data['packing_method'] == 1) {
            $merge_num = $data['quantity_purchased'] / $data['merge_num'];
            $deliver_num = $data['merge_num'];
            $volume_AS = $oversize['volume_AS'];
        } elseif ($data['packing_method'] == 2) { //多箱类型
            $merge_num = $data['quantity_purchased'];
            $deliver_num = $data['quantity_purchased'];
            $volume_AS = array_sum(array_column($oversize, 'volume_AS'));
        }
        $merge = array();
        if (is_int($merge_num)) { //满包裹发送
            $merge[0] = $merge_num;
        } else {
            $merge[0] = intval($merge_num); //满包裹发送
            //未满包裹发送获取内容为单包裹内的商品数量
            $merge[1] = intval(bcsub(strval($data['quantity_purchased']), (bcmul(strval((int)$merge_num), strval($data['merge_num']), 4)), 4));
        }
        //对整份包裹进行计算
        if (isset($merge[0]) and $merge[0] > 0) {
            $merge_amount = $this->mergeAmount($data, $deliver_num, $Weight, $oversize);
            $weight_amount1 = $merge_amount;
            $merge_amount = bcmul(strval($merge[0]), bcadd(strval($merge_amount['weight_amount']), strval($merge_amount['additional']), 4), 4);
        }
        //对未满包裹进行计算 例 包裹数量为 4个商品组合一个包裹 此包裹为3个商品
        if (isset($merge[1]) and $merge[1] > 0) {
            $merge_amount1 = $this->mergeAmount($data, $merge[1], $Weight, $oversize);
            $weight_amount2 = $merge_amount1;
            $merge_amount1 = bcmul(strval($merge[1]), bcadd(strval($merge_amount1['weight_amount']), strval($merge_amount1['additional']), 4), 4);
        }
        $array = [
            $data['packing_method'], //包装方式
            $merge, //包裹的数量 键位0-满包裹的数量，键位1-未满包裹内的商品数量
            $weight_amount1, //满包裹的商品计费信息
            $oversize //商品的规格信息
        ];
        //如果存才未满包裹 将满包裹费用与未满包裹费用 相加
        if (isset($merge_amount1)) {
            $amount = bcadd(strval($merge_amount), strval($merge_amount1), 4);
            $data_arr = $this->deliveryInfo($array, $weight_amount2); //type-1记录订单物流费的详细信息
            unset($merge_amount1);
        } else {
            $amount = strval($merge_amount);
            $data_arr = $this->deliveryInfo($array, 0);
        }
        //计算订单费  产品数量*用户设置的订单 商品重量达到用户设置重量后达成条件
        $order = $this->orderAmount($data, $Weight);
        //计算商品仓储费
        $Storagefee = $this->storageFee($data, $volume_AS); //仓储费
        unset($data, $Weight, $oversize, $merge_amount, $merge_num, $merge);
        return array($amount, $order, $Storagefee, $data_arr);
    }

    /**
     * 对包裹的单个商品详细信息进行记录
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function deliveryInfo($array, $weight_amount2)
    {   //满包裹数量
        //$type, $merge, $weight_amount1, $oversize
        $data['mbox'] = $array[1][0];
        $data['mbox_weight_amount'] = $array[2]['weight_amount']; //单个满包裹物流重量费用
        $data['mbox_additional'] = $array[2]['additional']; //单个满包裹物流附加费用
        if (isset($array[1]) && count($array[1]) == 2) {
            $data['wbox'] = $array[1][1]; //未满包裹内的商品数量
            $data['wbox_weight_amount'] = $weight_amount2['weight_amount']; //单个商品物流重量费用
            $data['wbox_additional'] = $weight_amount2['additional']; //单个商品物流附加费用
        }
        if ($array[0] == 1) {
            if (!is_array($array[3])) {
                $array[3] = $array[3]->toArray();
            }
            $data['oversize'] = $array[3];
        }
        return $data;
    }

    /**
     * 对订单商品计算仓储费用
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function storageFee($data, $volume_AS)
    {
        //商品才仓时间 未算
        $day = $this->storageDay($data);
        $sfconfig = new sfcModel;
        $sfconfig = $sfconfig->getStorageConfig($data['company_id']);
        if (isset($sfconfig)) {
            $sfcinfo = new sfciModel();
            $fee = 0;
            $sfcinfo = $sfcinfo->getStorageInfo($sfconfig->id); //筛选仓储对应的模板
            foreach ($sfcinfo as $k => $v) {
                $warehouse_id = explode(',', $v->warehouse_id);
                for ($i = 0; $i < count($day['warehoseOrder']); $i++) {
                    $d = $day['warehoseOrder'][$i]['day'];
                    if (in_array($data['warehouses_id'], $warehouse_id) and $v->min_days <= $d and $v->max_days > $d) {
                        $day['warehoseOrder'][$i]['fee'] = $v->fee; //获取对应模板的费用
                        $n = $day['warehoseOrder'][$i]['num']; //获取对应模板的商品数量
                        //计算费用 订单仓储费=体积/1000000*数量*台阶费
                        $storagefee = bcmul(bcmul(bcdiv(strval($volume_AS), strval(1000000), 4), strval($n), 4), strval($v->fee), 4);
                        //循环追加
                        $fee = bcadd(strval($fee), strval($storagefee), 4);
                        break;
                    }
                }
            }
            return $fee;
        } else {
            throw new FailedException('订单号：' . $data['id'] . '：用户id' . $data['company_id'] . '未设置仓储台阶模板');
        }
    }

    /**
     * 计算商品在仓库的天数
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function storageDay($data)
    {
        foreach ($data['warehoseOrder'] as $k => $v) {
            $time = $this->diffBetweenTwoDays($v['t'], date('Y-m-d'));
            $data['warehoseOrder'][$k]['day'] = ceil($time);
        }
        return $data;
    }


    /**
     * 计算2个时间差距多少天
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }

    /**
     * 对订单费用进行计算
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderAmount($data, $weight)
    {
        $order = new ofsModel;
        $order = $order->getUserOrderAmount($data['company_id'], $weight);
        if (isset($order)) {
            $order = $order->toArray();
            $amount = bcmul(strval($data['quantity_purchased']), strval($order['fee']), 4);
        } else {
            throw new FailedException('订单号：' . $data['id'] . '：用户id(公司)' . $data['company_id'] . '重量[' . $weight . ']未设置订单操作费');
        }
        return $amount;
    }

    /**
     * 对订单费用进行计算
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function mergeAmount($data, $num, $Weight, $pinfo)
    {
        //单箱与多箱判断
        if ($data['packing_method'] == 1) {
            $Weight = bcmul(strval($num), strval($Weight), 4); //包裹商品总数量*单商品的重量
            $oversize = $pinfo['oversize'];
            $oversize = bcmul(strval($num), strval($oversize), 4);
            $num = $num;
        } elseif ($data['packing_method'] == 2) {
            $num = 1;
            $Weight = bcmul(strval($num), strval($Weight), 4); //包裹商品总数量*单商品的重量
            $oversize = array_sum(array_column($pinfo, 'oversize'));
            $oversize = bcmul(strval($num), strval($oversize), 4);
        }
        //计算商品基础运价时，若商品的计费重量<90lbs,但是商品oversize参数>130英寸，则计费重量按照90lbs计算
        if ($Weight < 90 and $oversize > 130) {
            $Weight = (int)90;
        }
        //获取包裹重量的模板参数
        $config = new lfcModel;
        $config = $config->getShopWeightConfig($data['company_id'], $Weight, $data['zone']);
        if (isset($config)) {
            $config = $config->toArray();
            //计算单个包裹
            //物流基础费=(毛重*数量)*台阶费
            $Weight_amount = bcmul(strval(ceil($Weight)), strval($config['zone' . $data['zone']]), 4);
            $array = [
                $data, //商品信息
                $config, //客户指定商品重量模板
                $pinfo, //商品参数规格
                $Weight, //毛重
                $oversize, //商品sversize
                $num //包裹内商品数量
            ];
            //单个包裹的物流附加费
            $additional = $this->additional($array);
            unset($data, $config, $pinfo, $Weight, $oversize, $num);
            return array('weight_amount' => $Weight_amount, 'additional' => $additional);
        } else {
            // return array('weight_amount' => 0, 'additional' => 0);
            throw new FailedException('订单号：' . $data['id'] . '：用户id' . $data['company_id'] . '未设置物流台阶费用' . $Weight);
        }
    }

    /**
     * 单个包裹物流附加费
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function additional($array)
    {
        $data = $array[0]; //订单商品信息
        $config = $array[1]; //客户指定商品重量模板
        $pinfo = $array[2]; //商品参数规格
        $Weight = $array[3]; //毛重
        $oversize = $array[4]; //商品sversize
        $num = $array[5]; //包裹内商品数量
        unset($array);
        //判断毛重
        $amount = array();
        if ($Weight > $config['gross_weight']) {
            $amount[0] = $config['gross_weight_fee'];
        }
        //针对商品包装类型进行不同的计算
        if ($data['packing_method'] == 1) {
            //对比长宽高
            $length = array(bcmul(strval($pinfo['length_AS']), strval($num), 4), bcmul(strval($pinfo['height_AS']), strval($num), 4), bcmul(strval($pinfo['width_AS']), strval($num), 4));
        } elseif ($data['packing_method'] == 2) {
            $length_AS = array_sum(array_column($pinfo, 'length_AS'));
            $height_AS = array_sum(array_column($pinfo, 'height_AS'));
            $width_AS = array_sum(array_column($pinfo, 'width_AS'));
            //对比长宽高
            $length = array(bcmul(strval($length_AS), strval($num), 4), bcmul(strval($height_AS), strval($num), 4), bcmul(strval($width_AS), strval($num), 4));
        }
        //针对商品的边长进行排序
        rsort($length);
        if ($length[0] > $config['big_side_length']) {
            $amount[1] = $config['big_side_length_fee'];
        }
        if ($length[1] > $config['second_side_length']) {
            $amount[2] = $config['second_side_length_fee'];
        }
        //对比oversize区间
        if ($config['oversize_min_size'] < $oversize and $oversize < $config['oversize_max_size']) {
            $amount[3] = $config['oversize_fee'];
        }
        //对比oversize大小
        if ($oversize > $config['oversize_other_size']) {
            $amount[4] = $config['oversize_other_size_fee'];
        }
        if (isset($amount) && count($amount) > 0) {
            rsort($amount);
            $amount = (int)$amount[0];
        } else {
            $amount = 0;
        }
        //对用户的邮编进行判断是否偏远
        $zip = new zcsModel;
        $zip = $zip->getZip($data['address_postalcode'], 'type'); //查询是否偏远
        if (isset($zip)) {
            $zip = $zip->toArray();
            if ($zip['type'] == 1) {
                $amount = bcadd(strval($amount), strval($config['remote_fee']), 4);
            } elseif ($zip['type'] == 2) {
                $amount = bcadd(strval($amount), strval($config['super_remote_fee']), 4);
            }
        }
        unset($data, $config, $pinfo, $Weight, $oversize);
        return $amount;
    }

    /**
     * 修改订单数据
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderRevise($id, $array, $str)
    {
        $order = new orsModel;
        $order = $order->where('id', $id)->update($array);
        if (!isset($order)) {
            throw new FailedException($str);
        }
    }

    /**
     * 修改订单商品数据
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderInfoRevise($id, $array, $str)
    {
        $order = new oriModel;
        $order = $order->where('order_record_id', $id)->update($array);
        if (!isset($order)) {
            throw new FailedException($str);
        }
    }

    /**
     * 扣除用户余额
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function deduction($id, $amount, $str)
    {
        $user = new cModel;
        $overage_amount = $user->field('overage_amount')
            ->where('id', $id)
            ->find();
        $user_amount = $user->where('id', $id)
            ->decrement('overage_amount', (float)sprintf("%.2f", $amount));
        //余额判断扣除成功进行记录操作
        if (isset($user_amount) && $user_amount > 0) {
            $user_log = new cal;
            $user_log->company_id = $id;
            $user_log->before_modify_amount = $overage_amount->overage_amount;
            $user_log->subtract_amount = (float)sprintf("%.2f", $amount);
            $user_log->charge_balance = bcsub(strval($user_log->overage_amount), strval((float)sprintf("%.2f", $amount)), 4);
            $user_log->type = 1;
            $user_log->created_at = time();
            $user_log->save();
            if (!isset($user_log)) {
                throw new FailedException($str[1]);
            }
        } else {
            throw new FailedException($str[0]);
        }
    }

    /**
     * 商品出库单处理
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function outBoundOrder($data)
    {
        //生成出库单申请
        $obo = new oboModel;
        $obo->entity_warehouse_id = $data['en'];
        $obo->virtual_warehouse_id = $data['vi'];
        $obo->audit_status = 2;
        $obo->source = 'sales';
        $obo->created_at = time();
        $obo->save();
        if (isset($obo->id) && $obo->id > 0) {
            $obo_info_id = $obo->getLastInsID();
            for ($i = 0; $i < count($data['warehoseOrder']); $i++) {
                $obo_info = new obopModel;
                $obo_info->outbound_order_id = $obo_info_id;
                $obo_info->goods_id = $data['goods_id'];
                $obo_info->category_name = $data['category_name'];
                $obo_info->goods_code = $data['goods_code'];
                $obo_info->goods_name = $data['name_ch'];
                $obo_info->goods_name_en = $data['name_en'];
                $obo_info->goods_pic = $data['goods_pic'] ?? '';
                $obo_info->number = $data['quantity_purchased'];
                $obo_info->batch_no = $data['warehoseOrder'][$i]['no'];
                $obo_info->type = 1;
                $obo_info->order_type = $data['order_type'];
                $obo_info->created_at = time();
                $obo_info->save();
                if (!isset($obo_info->id)) {
                    throw new FailedException('订单id:' . $data['id'] . '创建出库商品单异常');
                }
                $order = new whsModel();
                $order = $order->where('batch_no', $data['warehoseOrder'][$i]['no'])
                    ->where('goods_code', $data['goods_code'])
                    ->where('entity_warehouse_id', $data['en'])
                    ->where('virtual_warehouse_id', $data['vi'])
                    ->decrement('number', $data['quantity_purchased']);
                if (!isset($order)) {
                    throw new FailedException('订单id:' . $data['id'] . '库存扣除商品数量异常');
                }
            }
        } else {
            throw new FailedException('订单id:' . $data['id'] . '创建出库单异常');
        }
    }

    /**
     * 订单包裹数据入库，对包裹进行数据填充。
     * $t=1//对有出库单申请的数据的数据操作;$t=2//对没有出库申请单的处理;
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function orderDeliverSave($data, $str, $t, $id)
    {
        // var_dump('=========77777=====',$data); exit;
        $res = array();
        //对包裹公共参数进行赋值
        $res['order_no'] = $data['order_no']; //订单编号
        $res['goods_pic'] = $data['goods_pic'] ?? ''; //商品缩率图
        $res['platform_id'] = $data['platform_id']; // 平台ID
        $res['platform_no'] = $data['platform_no']; // 平台订单编号1
        $res['shop_basics_id'] = $data['shop_basics_id']; //店铺id
        $res['transaction_price_currencyid'] = $data['transaction_price_currencyid']; //价格单位
        $res['transaction_price_value'] = $data['transaction_price_value']; //价格
        $res['tax_amount_value'] = $data['tax_amount_value']; //税费
        $res['tax_amount_currencyid'] = $data['tax_amount_currencyid']; //税费单位
        $res['order_record_id'] = $data['order_record_id']; //客户id
        $res['name_ch'] = $data['name_ch'];
        $res['name_en'] = $data['name_en'];
        $res['code'] = $data['code'];
        $res['image_url'] = $data['image_url'];
        $res['company_id'] = $data['company_id'];
        $res['warehouses_id'] = $data['warehouses_id'] ?? 0;
        $res['length_AS'] = $data['length_AS'];
        $res['width_AS'] = $data['width_AS'];
        $res['height_AS'] = $data['height_AS'];
        $res['weight_gross_AS'] = $data['weight_gross_AS'];
        if ($data['packing_method'] == 1) { // 单箱包装模式
            //满包裹
            $res['order_record_id'] = $data['id'];
            $res['goods_id'] = $data['goods_id'];
            $res['number'] = $data['merge_num'];
            $res['warehoseOrder'] = $data['warehoseOrder'] ?? 0;
            if ($t == 1) { //对有出库单申请的数据的数据操作
                $res['en_id'] = $data['en'];
                $res['vi_id'] = $data['vi'];
                $res['warehouse_price'] = bcdiv(strval($data['warehouse_price']), strval($data['quantity_purchased']), 4);
                $res['order_price'] = bcdiv(strval($data['order_price']), strval($data['quantity_purchased']), 4);
                $res['freight_weight_price'] = $data['deliver_info']['mbox_weight_amount'];
                $res['freight_additional_price'] = $data['deliver_info']['mbox_additional'];
                $res['logistics_status'] = 1;
                $this->deliverSave($data['deliver_info']['mbox'], $res, $str, $id);
                if (isset($data['deliver_info']['wbox']) && $data['deliver_info']['wbox'] > 0) {
                    $res['freight_weight_price'] = $data['deliver_info']['wbox_weight_amount'];
                    $res['freight_additional_price'] = $data['deliver_info']['wbox_additional'];
                    $res['number'] = $data['deliver_info']['wbox'];
                    $this->deliverSave(1, $res, $str, $id);
                }
            } elseif ($t == 2) { //对没有出库申请单的处理;
                $res['en_id'] = 0;
                $res['vi_id'] = 0;
                $res['warehouse_price'] = 0;
                $res['order_price'] = 0;
                $res['freight_weight_price'] = 0;
                $res['freight_additional_price'] = 0;
                $res['number'] = $data['merge_num'];
                $res['warehoseOrder'] = $data['warehoseOrder'] ?? 0;
                $res['logistics_status'] = 2;
                $num = floor($data['quantity_purchased'] / (int)$data['merge_num']);
                $this->deliverSave((int)$num, $res, $str, $id);
                $snum = $data['quantity_purchased'] - ($num * $data['merge_num']);
                if ($snum > 0) {
                    $res['freight_weight_price'] = 0;
                    $res['freight_additional_price'] = 0;
                    $res['number'] = $snum;
                    $this->deliverSave(1, $res, $str, $id);
                }
            }
        } elseif ($data['packing_method'] == 2) { //多箱包装
            $res['order_record_id'] = $data['id'];
            $res['goods_id'] = $data['goods_id'];
            $res['warehoseOrder'] = $data['warehoseOrder'] ?? 0;
            $res['number'] = 1;
            if ($t == 1) {
                $res['en_id'] = $data['en'];
                $res['vi_id'] = $data['vi'];
                $res['warehouse_price'] = bcdiv(strval($data['warehouse_price']), strval($data['quantity_purchased']), 4);
                $res['order_price'] = bcdiv(strval($data['order_price']), strval($data['quantity_purchased']), 4);
                $res['freight_weight_price'] = $data['freight_weight_price'];
                $res['freight_additional_price'] = $data['freight_additional_price'];
                $res['logistics_status'] = 1;
            } elseif ($t == 2) {
                $res['en_id'] = 0;
                $res['vi_id'] = 0;
                $res['warehouse_price'] = 0;
                $res['order_price'] = 0;
                $res['freight_weight_price'] = 0;
                $res['freight_additional_price'] = 0;
                $res['logistics_status'] = 2;
            }
            $this->deliverSave($data['quantity_purchased'], $res, $str, $id);
        }
    }

    /**
     * 批量插入包裹发货单
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function deliverSave($num, $data, $str, $id)
    {
        // var_dump('>>>>>>>>>>>>>', $data); exit;

        for ($i = 0; $i < $num; $i++) {
            $deliver = new odeModel;
            $deliverObj['order_no'] = $data['order_no'];
            $deliverObj['goods_pic'] = $data['goods_pic'] ?? '';
            $deliverObj['platform_id'] = $data['platform_id'];
            $deliverObj['platform_no'] = $data['platform_no'];
            $deliverObj['shop_basics_id'] = $data['shop_basics_id'];
            $deliverObj['order_record_id'] = $data['order_record_id'];
            $deliverObj['goods_id'] = $data['goods_id'];
            $deliverObj['number'] = $data['number'];
            $deliverObj['en_id'] = $data['en_id'];
            $deliverObj['vi_id'] = $data['vi_id'];
            $deliverObj['company_id'] = $data['company_id'];
            $deliverObj['creator_id'] = $id;
            $deliverObj['warehouse_price'] = $data['warehouse_price'];
            $deliverObj['order_price'] = $data['order_price'];
            $deliverObj['freight_weight_price'] = $data['freight_weight_price'];
            $deliverObj['logistics_status'] = $data['logistics_status'];
            $deliverObj['freight_additional_price'] = $data['freight_additional_price'];
            $deliverObj['length_AS_total'] = $data['length_AS'];
            $deliverObj['width_AS_total'] = $data['width_AS'];
            $deliverObj['height_AS_total'] = $data['height_AS'];
            $deliverObj['weight_AS_total'] = $data['weight_gross_AS'];

            // $res = $deliver->save();
            // var_dump($deliverObj); exit;
            $res = $deliver->storeBy($deliverObj);
            if (isset($data['warehouses_id'])) {
                $orderDeliverProducts = new OrderDeliverProducts;
                $orderDeliverProducts->save([
                    'order_deliver_id' => $res,
                    'order_id' => $data['order_record_id'],
                    'goods_id' => $data['goods_id'],
                    'goods_code' => $data['code'],
                    'goods_name' => $data['name_ch'],
                    'goods_name_en' => $data['name_en'],
                    'goods_pic' => $data['image_url'],
                    'transaction_price_currencyid' => $data['transaction_price_currencyid'],
                    'transaction_price_value' => $data['transaction_price_value'],
                    'tax_amount_currencyid' => $data['tax_amount_currencyid'],
                    'tax_amount_value' => $data['tax_amount_value'],
                    'number' => $data['number'],
                    'type' => 1, // 1-普通商品 2-配件
                    'batch_no' => $data['warehoseOrder'][0]['no'] ?? 0,
                    'warehouses_id' => $data['warehouses_id'] ?? 0
                ]);
            }
            if (!isset($res)) {
                throw new FailedException($str);
            }
        }
    }


    /**
     * 查看手工发货可用仓库信息,如无可用仓库则关联对应商品归属仓库
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getManualDelivery($item, $uid)
    {
        $item['address_postalcode'] = $item['user_addres']['address_postalcode'];
        $item['quantity_purchased'] = $item['number'];
        $item['merge_num'] = $item['number'];
        //拿出所有对该商品关联的仓库
        $item['warehose_array'] = $this->freightAmount($item, 2);

        return $item;
    }




    /**
     * 导出发货类型筛选
     * $t 1-导出发货单;2-导出拣货单;3-导出出货单
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function export($where, $t)
    {

        if ($t == 1) { //导出发货单
            $fileName = [
                '发货单号', '物流单号', '商品编码', '商品名称',
                '订单编号', '店铺名称', '实体仓库', '虚拟仓库',
                '总长-美制', '总宽-美制', '总高-美制', '总重量-美制', '物流公司',
                '数量', '购买人', '购买人电话', '购买人地址',
                '购买人邮箱', '购买人城市', '购买人邮编', '购买国家',
                '购买时间',
            ];
            $fileData = [
                'invoice_no', 'shipping_code', 'goods_code', 'goods_name',
                'order_no', 'shop_name', 'en_id', 'vi_id',
                'length_AS_total', 'width_AS_total', 'height_AS_total', 'weight_AS_total', 'shipping_name',
                'number', 'address_name', 'address_phone', 'address_street1',
                'address_email', 'address_cityname', 'address_postalcode', 'address_country',
                'paid_at',
            ];
        } elseif ($t == 2) { //导出拣货单
            $fileName = ['商品编码', '商品名称', '商品数量', '物流公司'];
            $fileData = ['goods_code', 'goods_name', 'number', 'shipping_name'];
        } else {
            throw new FailedException('export_type字段为必填项');
        }
        return [$fileName, $fileData];
    }

    /**
     * 客户对包裹点击确认发货单修改包裹状态
     * 发货过程状态（0未确认发货单，1以确认发货单，2获取物流单号，3打印物流面单）
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function upDeliver($id, $uid)
    {
        $order_info_array = [
            'delivery_process_status' => 1,
            'updater_id' => $uid, //审核人id
            'updated_at' => time(),
            'status' => 1
        ];
        $order = new odeModel;
        $order = $order->where('id', $id)->update($order_info_array);
        if (!isset($order)) {
            throw new FailedException('审核订单异常,请稍后再试.');
        }
        return 1;
    }
}
