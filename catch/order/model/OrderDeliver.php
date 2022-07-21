<?php

namespace catchAdmin\order\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\product\model\Product;
use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\order\model\search\OrderDeliverSearch;
use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\order\model\OrderDeliverProducts;
use catchAdmin\store\model\Platforms;
use catcher\Code;
use catchAdmin\warehouse\model\WarehouseStock;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\ProductGroup;


class OrderDeliver extends Model
{
    use BaseOptionsTrait, ScopeTrait, DataRangScopeTrait;
    use OrderDeliverSearch;

    // 表名
    public $name = 'order_deliver';
    public $field = array(
        'id',
        // 原始订单类型 0-正常订单、4-预售订单 2-借卖订单 3-客户订单
        'order_type',
        //发货物流类型 0-未设置 1-自有物流 2-它有物流
        'logistics_type',
        // 发货类型 0-自有发货 1-第三方发货
        'deliver_type',
        // 订单来源 1-正常订单 2-补货订单
        'order_type_source',
        // 发货单类型 1-整单发货 2-拆分发货
        'order_delivery_type',
        // 分组商品名称
        'goods_group_name',
        // 分组商品id
        'goods_group_id',
        // 售后订单id
        'after_order_id',
        // 订单表ID
        'order_record_id',
        // 商品表ID
        'goods_id',
        // 商品code
        'goods_code',
        // 订单编号（系统自动生成O+年月日+5位流水）
        'order_no',
        // 包裹内商品数量
        'number',
        // 平台订单编号1
        'platform_no',
        // 平台订单编号2
        'platform_no_ext',
        // 发货编号（系统自动生成FH+年月日+5位流水）
        'invoice_no',
        // 运输方式
        'shipping_method',
        // 商品缩率图
        'goods_pic',
        // 物流公司名称
        'shipping_name',
        // 物流运单号
        'shipping_code',
        // 物流运单号usps原始单号
        'shipping_code2',
        // 平台ID
        'platform_id',
        // 所属客户id 关联 company
        'company_id',
        // 店铺ID
        'shop_basics_id',
        // 实体仓id
        'en_id',
        // 虚拟仓id
        'vi_id',
        // 美制长长
        'length_AS_total',
        // 美制宽cm 总长
        'width_AS_total',
        // 美制高cm 总长
        'height_AS_total',
        // 美制毛重 kg  总重
        'weight_AS_total',
        // 仓储费单个商品
        'warehouse_price',
        // 订单操作费/单个商品
        'order_price',
        // 发货审核状态（0-待审核包裹，1-已审核包裹）
        'status',
        // 发货单类型（1-成功发货订单，2-异常发货订单）
        'logistics_status',
        // 发货商品物流重量费/单个商品
        'freight_weight_price',
        // 发货商品附加费/单个商品
        'freight_additional_price',
        // 偏远/超级偏远物流费 （结算统计不乘数量）
        'postcode_fee',
        // 保费/单个商品
        'hedge_fee',
        // 发货状态
        // 1-待发货（没有获取物流单号）
        // 2-已发货(待打印面单)（已获取物流单号未打印物流面单的状态）
        // 3-运输中（发货单对应物流单号的运输中状态）4-配送中（订单对应的发货单物流在配送中状态）5-已收货（订单对应的发货单物流单用户已收货）6-作废
        'delivery_state',
        // 是否打印面单 1-未打印 2-打印
        'delivery_process_status',
        // 发货日期
        'send_at',
        // zone 发货邮编分区匹配的zone
        'zone',
        // 扩展字段1(Walmart-存储lineNumber字段)
        'extend_1',
        // 创建人ID
        'creator_id',
        //上次物流更新时间
        'deliver_day',
        //物流id
        'shipping_id',
        //物流信息详情
        'tracking_info',
        // 物流面单文件日期
        'tracking_date',
        // 创建时间
        'created_at',
        // 修改人ID
        'updater_id',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
        //打印时间
        'print_time',
        // 是否同步物流信息 0-未同步 1-已同步
        'sync_logistics'
    );

    /**
     * 发货列表
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($type = '')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $where = [
                        'o.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $whereBaiscsTwo = [];
        if ((int)$type != Code::ORDER_CANCELED) {
            $whereBaiscsTwo = [['o.delivery_state', '<>', '6']];
        }
        $searchData = $this->dataRange()
            ->field('o.id, o.order_type, o.logistics_type, o.deliver_type, o.weight_AS_total,
            o.order_type_source, o.order_delivery_type, o.goods_group_name, o.goods_group_id,
            o.after_order_id, o.order_record_id, o.goods_id, o.goods_code,
            o.order_no, o.number, o.platform_no, o.platform_no_ext, o.invoice_no,
            o.shipping_method, o.shipping_name, o.shipping_code, o.platform_id,
            o.company_id, o.shop_basics_id, o.en_id, o.vi_id, o.status, o.logistics_status,
            o.delivery_state, o.delivery_process_status, o.send_at, o.zone,
            o.creator_id, o.deliver_day, o.shipping_id, o.created_at, o.sync_logistics,
            o.updated_at
            ,w.name as en_name, wvi.name as vi_name, p.name_ch, sb.shop_name, p.code as code_goods,
            pf.name as platform, u.username as creator_name, IFNULL(us.username, "-") as update_name,
            odp.goods_name, odp.category_name, p.category_id,
            p.code as goods_codes, p.name_ch, 
            ca.parent_name, ca.name as category_name_real')
            ->alias('o')
            ->catchSearch();
        // 非作废订单
        // if ((int)$type != Code::ORDER_CANCELED) {
        //     $searchData = $searchData->whereNotIn('o.delivery_state', '6');
        // }
        return $searchData->where($whereBaiscsTwo)
            // ->whereOr($where)
            // ->whereOr($whereOr)
            // ->where(function ($query)  use ($whereOr) {
            //     $query->whereOr([$whereOr]);
            // })
            ->whereOr(function ($query) use ($whereOr, $where, $whereBaiscsTwo) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->where($whereBaiscsTwo)
                        ->catchSearch();
                }
            })
            ->leftJoin('warehouses w', 'w.id=o.en_id')
            ->leftJoin('warehouses wvi', 'wvi.id=o.vi_id')
            ->leftJoin('shop_basics sb', 'sb.id=o.shop_basics_id')
            ->leftJoin('platform pf', 'pf.id=o.platform_id')
            ->leftJoin('product p', 'p.id=o.goods_id')
            ->leftJoin('users u', 'u.id = o.creator_id')
            ->leftJoin('users us', 'us.id = o.updater_id')
            ->leftJoin('category ca', 'ca.id = p.category_id')
            ->leftJoin('order_deliver_products odp', 'odp.order_deliver_id = o.id')
            // ->leftJoin('order_buyer_records obr', '(obr.order_record_id = o.order_record_id and o.order_type_source=1 and obr.is_disable=1) or
            // (obr.after_sale_id = o.after_order_id and o.order_type_source=2 and obr.is_disable=1)')
            ->leftJoin('order_buyer_records obr', '(obr.order_record_id = o.order_record_id)')
            ->group('o.invoice_no')
            ->order(['o.delivery_state' => 'asc', 'o.id' => 'desc'])
            ->paginate()->each(function (&$item) {
                // 存在部分售后地址与原本地址对不上
                if (!empty($item['after_order_id'])) {
                    $buyerRecords = OrderBuyerRecords::where(['order_record_id' => $item['order_record_id'], 'after_sale_id' => $item['after_order_id']])->find();
                } else {
                    $buyerRecords = OrderBuyerRecords::where(['order_record_id' => $item['order_record_id'], 'is_disable' => 1])->find();
                }

                $item['address_name'] = $buyerRecords['address_name'];
                $item['address_email'] = $buyerRecords['address_email'];
                $item['address_cityname'] = $buyerRecords['address_cityname'];
                $item['address_stateorprovince'] = $buyerRecords['address_stateorprovince'];
                //  obr.address_name, obr.address_email,
                //  obr.address_cityname, obr.address_stateorprovince, 
            });
    }

    /**
     * 发货单、拣货单、异常物流单 导出
     * getExportList
     */
    public function getExportLists($type = '')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $where = [
                        'o.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $whereBaiscsTwo = [];
        if ((int)$type != Code::ORDER_CANCELED) {
            $whereBaiscsTwo = [['o.delivery_state', '<>', '6']];
        }
        ini_set('memory_limit', '1024M');
        // , wvi.name as vi_name
        $list = $this->dataRange()
            ->field('o.id, o.order_type, o.logistics_type, o.deliver_type, o.weight_AS_total,
            o.order_type_source, o.order_delivery_type, o.goods_group_name, o.goods_group_id,
            o.after_order_id, o.order_record_id, o.goods_id, o.goods_code,
            o.order_no, o.number, o.platform_no, o.platform_no_ext, o.invoice_no,
            o.shipping_method, o.shipping_name, o.shipping_code, o.platform_id,
            o.company_id, o.shop_basics_id, o.en_id, o.vi_id, o.status, o.logistics_status,
            o.delivery_state, o.delivery_process_status, o.send_at, o.zone,
            o.creator_id, o.deliver_day, o.shipping_id, o.created_at, o.sync_logistics,
            o.updated_at,o.length_AS_total,o.width_AS_total, o.height_AS_total,
            w.name as en_name, wvi.name as vi_name, p.name_ch, sb.shop_name, p.packing_method,o.order_record_id,
            odp.goods_code, odp.category_name, odp.number as quantity_purchased, p.category_id, p.code as code_goods,odp.goods_group_name')
            ->alias('o')
            ->catchSearch();
        // 非作废订单
        // if ((int)$type != Code::ORDER_CANCELED) {
        //     $list = $list->whereNotIn('o.delivery_state', '6');
        // }
        return $list->where($whereBaiscsTwo)
            // ->whereOr($where)
            // ->whereOr($whereOr)
            // ->where(function ($query)  use ($whereOr) {
            //     $query->whereOr([$whereOr]);
            // })
            ->whereOr(function ($query) use ($whereOr, $where, $whereBaiscsTwo) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->where($whereBaiscsTwo)
                        ->catchSearch();
                }
            })
            ->leftJoin('warehouses w', 'w.id=o.en_id')
            ->leftJoin('warehouses wvi', 'wvi.id=o.vi_id')
            ->leftJoin('shop_basics sb', 'sb.id=o.shop_basics_id')
            ->leftJoin('product p', 'p.id=o.goods_id')
            ->leftJoin('order_deliver_products odp', 'odp.order_deliver_id = o.id')
            // ->leftJoin('order_buyer_records obr', '(obr.order_record_id = o.order_record_id and o.order_type_source=1 and obr.is_disable=1) or
            // (obr.after_sale_id = o.after_order_id and o.order_type_source=2 and obr.is_disable=1)')
            // ->leftJoin('order_buyer_records obr', '(obr.order_record_id = o.order_record_id and obr.is_disable=1 and obr.after_sale_id = o.after_order_id)')
            ->leftJoin('order_buyer_records obr', '(obr.order_record_id = o.order_record_id)')
            ->group('o.invoice_no')
            ->order(['o.delivery_state' => 'asc', 'o.id' => 'desc'])
            ->select()->each((function (&$item) use ($type) {
                if (!empty($item['send_at'])) {
                    $item['send_at_text'] = (new \Datetime())->setTimestamp($item['send_at'])->format('Y-m-d H:i:s');
                } else {
                    $item['send_at_text'] = '';
                }
                // 商品编码*数量 // 同快递面单上的SKU显示
                if ($item['packing_method'] == 1) { // 1-普通商品 2-多箱包装
                    $item['sku_import'] = $item['code_goods'] . '*' . $item['number'];
                    $item['sku_goods_name'] = $item['code_goods'];
                } else {
                    if ((int)$item['order_type_source'] == 2) { // 补货
                        $item['sku_import'] = $item['goods_code'] . '*' . $item['number'];
                        $item['sku_goods_name'] = $item['goods_code'];
                    } else {
                        $item['sku_import'] = $item['goods_group_name'] . '*' . $item['number'];
                        $item['sku_goods_name'] = $item['goods_group_name'];
                    }
                }
                if (!empty($item['after_order_id'])) {
                    $buyerRecords = OrderBuyerRecords::where(['order_record_id' => $item['order_record_id'], 'after_sale_id' => $item['after_order_id']])->find();
                } else {
                    $buyerRecords = OrderBuyerRecords::where(['order_record_id' => $item['order_record_id'], 'is_disable' => 1])->find();
                }
                $item['address_name'] = $buyerRecords['address_name'];
                $item['address_email'] = $buyerRecords['address_email'];
                $item['address_cityname'] = $buyerRecords['address_cityname'];
                $item['address_stateorprovince'] = $buyerRecords['address_stateorprovince'];
                $item['address_phone'] = $buyerRecords['address_phone'];
                $item['address_street1'] = $buyerRecords['address_street1'];
                $item['address_postalcode'] = $buyerRecords['address_postalcode'];
                $item['address_country_name'] = $buyerRecords['address_country_name'];

                // obr.address_name, obr.address_phone,obr.address_street1,obr.address_email,obr.address_cityname,obr.address_stateorprovince,
                // obr.address_postalcode, obr.address_country_name, 
                // 第三方发货单
                // if ((int)$type == 3) {
                //     $item['stock'] = $this->findWarehouseProduct($item['vi_id'], $item['code_goods']);
                // }
            }));
    }
    /**
     * 查询仓库商品数量  findWarehouseProduct
     * @param $goods_code 商品编码
     * @param $viWarehouseId 虚拟仓库id
     *
     */
    public function findWarehouseProduct($viWarehouseId, $goodsCode)
    {
        $warehouseStock = new WarehouseStock;
        $list = $warehouseStock->field('ws.number, ws.batch_no, ws.goods_code')
            ->alias('ws')
            ->where('ws.goods_code', $goodsCode)
            ->where('ws.virtual_warehouse_id', $viWarehouseId)
            ->sum('ws.number') ?? 0;
        return $list;
    }
    /**
     * 发货单
     */
    public function exportFieldThirdPart()
    {
        return [
            [
                'title' => '发货单编号',
                'filed' => 'invoice_no',
            ],
            [
                'title' => '运单号',
                'filed' => 'shipping_code',
            ],
            [
                'title' => 'SKU商品编码',
                'filed' => 'goods_code',
            ],
            [
                'title' => '数量1/Quantity',
                'filed' => 'quantity_purchased',
            ],
            [
                'title' => '虚拟仓库名称',
                'filed' => 'vi_name',
            ],
            [
                'title' => '平台订单编码',
                'filed' => 'platform_no',
            ],
            [
                'title' => '平台订单编码2',
                'filed' => 'platform_no_ext',
            ],
            [
                'title' => '店铺',
                'filed' => 'shop_name', //店铺名称
            ],
            [
                'title' => '实体仓库名称',
                'filed' => 'en_name',
            ],
            [
                'title' => '收件人姓名/Consignee Name',
                'filed' => 'address_name'
            ],
            [
                'title' => '收件人电话/Consignee Phone',
                'filed' => 'address_phone'
            ],
            [
                'title' => '街道/Street',
                'filed' => 'address_street1'
            ],
            [
                'title' => '城市/City',
                'filed' => 'address_cityname'
            ],
            [
                'title' => '州/Province',
                'filed' => 'address_stateorprovince'
            ],
            [
                'title' => '邮编/Zip Code',
                'filed' => 'address_postalcode'
            ],
            [
                'title' => '收件人国家/Consignee Country',
                'filed' => 'address_country_name'
            ]
        ];
    }
    /**
     * 异常物流导出
     */
    public function exportFieldLogistics()
    {
        return [
            [
                'title' => '发货单编号',
                'filed' => 'invoice_no',
            ],
            [
                'title' => '系统订单编号',
                'filed' => 'order_no',
            ],
            [
                'title' => '平台订单编号',
                'filed' => 'platform_no',
            ],
            [
                'title' => '物流公司名称',
                'filed' => 'shipping_name',
            ],
            [
                'title' => '物流运单号',
                'filed' => 'shipping_code',
            ],
            [
                'title' => '虚拟仓',
                'filed' => 'vi_name',
            ],
            [
                'title' => '实体仓',
                'filed' => 'en_name',
            ],
            [
                'title' => '商品编码',
                'filed' => 'goods_code',
            ],
            [
                'title' => '商品中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '店铺名称',
                'filed' => 'shop_name',
            ]
        ];
    }
    /**
     * 拣货单导出
     */
    public function exportFieldPick()
    {
        return [
            [
                'title' => 'SKU商品编码',
                'filed' => 'sku_goods_name',
            ],
            [
                'title' => 'Product Name商品名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => 'Quantity数量',
                'filed' => 'quantity_purchased',
            ],
            [
                'title' => 'ExpressCorp快递公司',
                'filed' => 'shipping_name',
            ]
        ];
    }
    /**
     * 发货单导出字段
     */
    public function exportField()
    {
        return [
            [
                'title' => 'BILLNO',
                'filed' => 'invoice_no', // 发货单号
            ],
            [
                'title' => 'TRACKINGNUMBER',
                'filed' => 'shipping_code', // 物流单号
            ],
            [
                'title' => 'EXPRESSCORP',
                'filed' => 'shipping_name', // 物流公司名称
            ],
            [
                'title' => 'SKU',
                'filed' => 'sku_import', // 商品编码*数量 //同快递面单上的SKU显示
            ],
            [
                'title' => 'SKU_ALL',
                'filed' => 'code_goods', // 商品编码
            ],
            [
                'title' => 'SKUNAME', // 商品名称
                'filed' => 'name_ch',
            ],
            [
                'title' => 'ORDERID', // 平台订单编号
                'filed' => 'platform_no',
            ],
            [
                'title' => 'SHOPNAME',
                'filed' => 'shop_name', //店铺名称
            ],
            [
                'title' => 'WHNAME', //虚拟仓库
                'filed' => 'vi_name',
            ],
            [
                'title' => 'SENDWHNAME', //实体仓库
                'filed' => 'en_name',
            ],
            [
                'title' => 'TOTALLENGHT', // 长（inch）
                'filed' => 'length_AS_total',
            ],
            [
                'title' => 'TOTALWIDTH', //宽（inch）
                'filed' => 'width_AS_total',
            ],
            [
                'title' => 'TOTALHEIGHT', //高（inch)
                'filed' => 'height_AS_total',
            ],
            [
                'title' => 'TOTALWEIGHT', //毛重（lbs）
                'filed' => 'weight_AS_total',
            ],
            [
                'title' => 'QTY', //数量
                'filed' => 'number',
            ],
            [
                'title' => 'BUYERNAME',
                'filed' => 'address_name',
            ],
            [
                'title' => 'BUYERPHONE',
                'filed' => 'address_phone',
            ],
            [
                'title' => 'BUYERADDRESS',
                'filed' => 'address_street1',
            ],
            [
                'title' => 'BUYEREMAIL',
                'filed' => 'address_email',
            ],
            [
                'title' => 'BUYERCITY',
                'filed' => 'address_cityname',
            ],
            [
                'title' => 'BUYERSTATE',
                'filed' => 'address_stateorprovince',
            ],
            [
                'title' => 'BUYERZIP',
                'filed' => 'address_postalcode',
            ],
            [
                'title' => 'BUYERCOUNTY',
                'filed' => 'address_country_name',
            ],
            [
                'title' => 'CDATE',
                'filed' => 'send_at_text',
            ]
        ];
    }
    public function orderBuyRecord()
    {
        if ($this->order_type_source == 2) {
            return $this->belongsTo(OrderBuyerRecords::class, 'after_order_id', 'after_sale_id');
        } else {
            return $this->belongsTo(OrderBuyerRecords::class, 'order_record_id', 'order_record_id');
        }
    }

    public function product()
    {
        return $this->hasOne(OrderDeliverProducts::class, 'order_deliver_id', 'id');
    }
    // 查询多箱商品分组
    public function productGroup()
    {
        // 正常订单
        return $this->belongsTo(ProductGroup::class, 'goods_group_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouses::class, 'en_id');
    }

    public function platForm()
    {
        return $this->hasOne(Platforms::class, 'id', 'platform_id');
    }
}
