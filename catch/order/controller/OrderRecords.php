<?php
/*
 * @Version: 1.0
 * @Date: 2021-02-23 09:59:13
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-14 16:26:26
 * @Description:
 */

namespace catchAdmin\order\controller;

use catchAdmin\store\model\Platforms as platformsModel;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderRecords as orderRecordsModel;
use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\order\model\OrderItemRecords;
use catcher\Code;
use catchAdmin\order\request\OrderRecordsRequest;
use catchAdmin\order\request\AfterSaleOrderRequest;
use catchAdmin\order\model\AfterSaleOrder as afterSaleOrderModel;
use catcher\platform\order\OrderService;
use catchAdmin\product\model\Product as productModel;
use catchAdmin\basics\model\Shop as shopModel;
use catchAdmin\permissions\model\Users as usersModel;
use catchAdmin\order\model\OrderDeliver as orderDeliverModel;
use catchAdmin\order\model\OrderDeliverProducts;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\OutboundOrderProducts;
// use catchAdmin\warehouse\model\WarehouseStock as warehouseStockModel;
use catchAdmin\product\model\ProductPlatformSku as ProductPlatformSkuModel;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\product\model\Category;
use catchAdmin\report\model\ReportOrder;
use catchAdmin\basics\model\Company;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\system\model\Config;
use catcher\platform\order\Order;
use catcher\Utils;
use think\facade\Cache;
use catchAdmin\product\model\ProductPrice;
use catchAdmin\product\model\ProductCombination;
use catcher\base\CatchRequest;
use catcher\CatchAdmin;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\Parts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\WarehouseStock;

class OrderRecords extends CatchController
{
    protected $orderRecordsModel;
    protected $orderBuyerRecords;
    protected $orderItemRecords;
    protected $afterSaleOrderModel;
    protected $productModel;
    protected $orderService;
    protected $orderDeliverProducts;
    protected $outboundOrdersModel;
    protected $outboundOrderProductsModel;
    protected $warehouseStockModel;
    protected $productPlatformSkuModel;

    public function __construct(
        OrderRecordsModel $orderRecordsModel,
        AfterSaleOrderModel $afterSaleOrderModel,
        OrderBuyerRecords $orderBuyerRecords,
        OrderItemRecords $orderItemRecords,
        ProductModel $productModel,
        OrderService $orderService,
        OrderDeliverProducts $orderDeliverProducts,
        OutboundOrders $outboundOrdersModel,
        OutboundOrderProducts $outboundOrderProductsModel,
        // WarehouseStockModel $warehouseStockModel,
        ProductPlatformSkuModel $productPlatformSkuModel
    ) {
        $this->orderRecordsModel = $orderRecordsModel;
        $this->orderItemRecords = $orderItemRecords;
        $this->orderBuyerRecords = $orderBuyerRecords;
        $this->afterSaleOrderModel = $afterSaleOrderModel;
        $this->productModel = $productModel;
        $this->orderService = $orderService;
        $this->orderDeliverProducts = $orderDeliverProducts;
        $this->outboundOrdersModel = $outboundOrdersModel;
        $this->outboundOrderProductsModel = $outboundOrderProductsModel;
        // $this->warehouseStockModel = $warehouseStockModel;
        $this->productPlatformSkuModel = $productPlatformSkuModel;
    }

    /**
     * 列表
     * @time 2021年02月05日 19:25
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        $types = $request->param('status');
        return CatchResponse::paginate(
            $this->orderRecordsModel->getList($types)
                ->each(function (&$item) {
                    if (empty($item['goods_code'])) {
                        $item['goods_code'] = $item['product_code'];
                    }
                    $item['get_at'] = (new \Datetime())->setTimestamp($item['get_at'])->format('Y-m-d H:i:s');
                    $utils = new Utils;
                    // 转化美国时间
                    $item['paid_at_text'] = $utils->toNewYorkTime($item['paid_at'], $item['timezone']);
                    $item['created_at'] = $utils->toNewYorkTime($item['created_at_order'], $item['timezone']);
                    $item['status_text'] = orderRecordsModel::$orderStatus[$item['status']];
                    $listData = Cache::get(Code::CACHE_PRESALE . $item['shop_basics_id'] . '_' . $item['goods_id']);
                    // 预售日期 $listData = Cache::get(Code::CACHE_PRESALE .'22_57');
                    if (!empty($listData)) {
                        $listDataJson = json_decode($listData);
                        $item['estimate_time'] = (new \Datetime())->setTimestamp($listDataJson->estimated_delivery_time)->format('Y-m-d H:i:s');
                    }
                    if (!empty($item['pre_shipped_at'])) {
                        $item['estimate_time'] = (new \Datetime())->setTimestamp($item['pre_shipped_at'])->format('Y-m-d H:i:s');
                    }
                    $item['category_text'] = $item['parent_name'] . '_' . $item['category_name'];
                })
        );
    }

    /**
     * 新增订单选择组合商品
     */
    public function combinationProductList(Request $request, $id)
    {
        $list = $this->productPlatformSkuModel->getShopGoodsList($id);
        return CatchResponse::success($list);
    }

    /**
     * 保存信息
     * @time 2021年02月05日 19:25
     * @param Request $request
     */
    public function save(Request $request): \think\Response
    {
        try {
            $data = $request->post();
            if ((int)$data['order_type'] != 3) {
                if (empty($data['platform_id'])) {
                    return CatchResponse::fail('平台不能为空', Code::FAILED);
                }
                // if ($this->orderRecordsModel->where(['platform_no' => $data['platform_no']])
                //     ->whereNotIn('order_type', '2,3')->whereNotIn('status', '6')->value('id')
                // ) {
                //     return CatchResponse::fail('第三方平台订单编码已存在', Code::FAILED);
                // }
            }
            // 来源 1-录入; 2-导入
            $data['order_source'] = 1;
            $data['abnormal'] = 0;
            if (empty($data['paid_at'])) {
                $data['paid_at'] = time();
            }
            if (!in_array($request->param('order_type'), [0, 2, 3])) {
                return CatchResponse::fail('类型参数不正确', Code::FAILED);
            }
            $data['get_at'] = time();
            $data['timezone'] = '+08:00';
            // 获取地址
            $address = [];
            if (isset($data['address'])) {
                foreach ($data['address'] as $item => $value) {
                    $address = $value;
                }
            } else {
                return  CatchResponse::fail('地址不能为空', Code::FAILED);
            }
            $addressData['order_buyer'] = $address;
            // 验证地址正确性
            $dataVal = $this->orderService->checkUpsAddress($addressData);
            if ($dataVal == Code::ORDER_TYPE_ABNORMAL) {
                return CatchResponse::fail('用户地址匹配失败', Code::FAILED);
            }
            $products = $data['product'];
            if ((int)$data['order_type'] != 3) {
                $data['company_id'] = shopModel::where('id', $data['shop_basics_id'])->value('company_id');
            }
            // var_dump('$data[]', $data['company_id']);
            // exit;
            // 组合商品
            if ((int)$data['product_type'] == 2) {
                if (isset($products)) {
                    $data['order_buyer'] = $address;
                    $products[0]['item_id'] = 0;
                    $products[0]['product_code'] = $products[0]['sku'];
                    $products[0]['buyer_email'] = $address['address_email'];
                    // buyer_user_firstname
                    $products[0]['buyer_user_firstname'] = '';
                    // buyer_user_lastname
                    $products[0]['buyer_user_lastname'] = '';
                    // company_id
                    $products[0]['company_id'] = $data['company_id'];
                    // 判断商品是否存在
                    if (!ProductCombination::where('code', $products[0]['goods_code'])->value('id')) {
                        return  CatchResponse::fail('组合商品不存在');
                    }

                    $res = $this->orderService->saveOrder($data, $products);
                    return  CatchResponse::success($res);
                }
            } else {
                $this->orderRecordsModel->startTrans();
                // 获取商品
                if (isset($products)) {
                    foreach ($products as $key => $id) {

                        $row = $id;
                        if (empty($row['goods_tax_amount'])) {
                            $row['goods_tax_amount'] = 0;
                        }
                        $data['total_num'] = $id['quantity_purchased'];
                        $data['total_price'] = bcmul($data['total_num'], $id['transaction_price_value'], 4);
                        $data['currency'] = $id['transaction_price_currencyid'];
                        // if ((int)$data['order_type'] != 3) {
                        //     $shop_basics_id = $data['shop_basics_id'];
                        // } else {
                        //     $shop_basics_id = 0;
                        // }
                        // var_dump(($this->orderRecordsModel->findOrderProduct($data['platform_no'], $id['goods_id'], $data['shop_basics_id']))); exit;
                        if ((int)$data['order_type'] != 2 && (int)$data['order_type'] != 3) {
                            // 判断是否存在同订单同商品
                            // if (!empty($this->orderRecordsModel->findOrderProduct($data['platform_no'], $id['goods_id'], '', $shop_basics_id))) {
                            //     $this->orderRecordsModel->rollback();
                            //     return  CatchResponse::fail('添加失败');
                            // }
                        }
                        // 预售日期商品
                        if ((int)$data['order_type'] !== Code::ORDER_TYPE_LOAN && (int)$data['order_type'] !== Code::ORDER_TYPE_CUSTOMER) {
                            $listData = Cache::get(Code::CACHE_PRESALE . $data['shop_basics_id'] . '_' .  $row['goods_id']);
                            if (!empty($listData)) {
                                $listDataJson = json_decode($listData);
                                $data['pre_shipped_at'] = $listDataJson->estimated_delivery_time;
                                $data['order_type'] = Code::ORDER_TYPE_PRESALES;
                            }
                        }
                        // 生成订单
                        $order_id = $this->orderRecordsModel->storeBy($data);
                        if (!$order_id) {
                            $this->orderRecordsModel->rollback();
                            return  CatchResponse::fail('操作失败', Code::FAILED);
                        }
                        $addressData['order_buyer']['order_record_id'] = $order_id;
                        $addressData['order_buyer']['type'] = 0;
                        // 替换买家姓名中的特殊字符（\&）
                        $addressData['order_buyer']['address_name'] = str_replace(array("&", "\\"), array("*", "*"), $addressData['order_buyer']['address_name']);
                        // 替换买家地址中的多个空格
                        $addressData['order_buyer']['address_street1'] = preg_replace("/\s(?=\s)/", "\\1", $addressData['order_buyer']['address_street1']);
                        $num = $this->orderRecordsModel->findNum($addressData['order_buyer']['address_phone']);
                        // 替换买家电话位数不足10位替换为10个0
                        $addressData['order_buyer']['address_phone'] = strlen($num) < 10 ? '0000000000' : $addressData['order_buyer']['address_phone'];
                        // 生成地址关联表
                        if (!$this->orderBuyerRecords->storeBy($addressData['order_buyer'])) {
                            $this->orderRecordsModel->rollback();
                            return  CatchResponse::fail('操作失败', Code::FAILED);
                        };
                        // 生成商品关联表
                        $row['order_record_id'] = $order_id;
                        $row['buyer_email'] = $address['address_email'];
                        $row['buyer_user_firstname'] = $address['buyer_firstname'] ?? '';
                        $row['buyer_user_lastname'] = $address['buyer_lastname'] ?? '';
                        $row['type'] = 0;
                        if (!$this->orderItemRecords->storeBy($row)) {
                            $this->orderRecordsModel->rollback();
                            return  CatchResponse::fail('操作失败', Code::FAILED);
                        };
                        $this->orderRecordsModel->commit();
                        return  CatchResponse::success($order_id);
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->orderRecordsModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 读取
     * @time 2021年02月05日 19:25
     * @param $id
     */
    public function read($id): \think\Response
    {
        $data = $this->orderRecordsModel->alias('o')->field('o.*, o.created_at as created_at_order')
            ->whereOr('id', $id)
            ->whereOr('order_no', $id)
            ->find();
        if (empty($data)) {
            return CatchResponse::fail('详情不存在');
        }
        if ($data['shop_basics_id']) {
            $shop = shopModel::where('id', $data['shop_basics_id'])->find();
            $data['shop_name'] = $shop['shop_name'];
        }


        $id = $data['id'];
        // $data['company'] = shopModel::field('c.name as company_name, c.id')->where('sp.id', $data['shop_basics_id'])->alias('sp')
        //     ->leftJoin('company c', 'c.id = sp.company_id')->find();
        if (!empty($item['company_id'])) {
            $data['company_name'] = Company::where('id', $item['company_id'])->value('name');
        } else {
            $data['company_name'] = '-';
        }
        // $item['company_name'] = shopModel::where('sp.id', $item['shop_basics_id'])->alias('sp')
        //     ->leftJoin('company c', 'c.id = sp.company_id')->value('c.name as company_name');
        $data['status_text'] = orderRecordsModel::$orderStatus[$data['status']];
        $data['get_at_text'] = date('Y-m-d H:i:s', $data['get_at']);
        //$data['paid_at_text'] = date('Y-m-d H:i:s', $data['paid_at']);
        // 转换美国时间
        $data['created_at'] = Utils::toNewYorkTime($data['created_at_order'], $data['timezone']);
        if (!empty($data['paid_at'])) {
            $data['paid_at_text'] = Utils::toNewYorkTime($data['paid_at'], $data['timezone']);
        }

        if (!empty($data['creator_id'])) {
            $data['created_name'] = usersModel::where('id', $data['creator_id'])->value('username');
        }
        if (!empty($data['updater_id'])) {
            $data['updater_name'] = usersModel::where('id', $data['updater_id'])->value('username');
        }
        // 当订单状态 o.delivery_state == 3 运输中
        $data['product_after_order'] = $this->orderDeliverProducts
            ->where('p.order_id', $id)
            ->field('p.*, p.type as product_type, o.delivery_state, o.shipping_name, o.shipping_code,
            o.goods_code as goods_codes, o.order_type_source')
            ->alias('p')
            ->leftJoin('order_deliver o', 'o.id = p.order_deliver_id and o.delivery_state != 6')
            ->select();
        $data['product'] = $this->orderItemRecords->where('order_record_id', $id)->where('type', 0)->select()->each(function ($item) {
            $product = productModel::where('id', $item['goods_id'])->find();
            $item['goods_pic'] = $product['image_url'];
            $item['goods_name'] = $product['name_ch'];
            $item['goods_name_en'] = $product['name_en'];
            $item['packing_method'] = $product['packing_method'];
            $item['goods_code'] = $product['code'];
            $category = Category::where('id', $product['category_id'])->find();
            $item['category_name'] = $category['parent_name'] . '-' . $category['name'];
        });
        $data['address'] = $this->orderBuyerRecords->where(['order_record_id' => $id, 'is_disable' => 1, 'type' => 0])->select();

        // 预售订单预售时间查询
        if ((int)$data['order_type'] == Code::ORDER_TYPE_PRESALES) {
            // $listData = Cache::get(Code::CACHE_PRESALE . $data['shop_basics_id'] . '_' . $data['product'][0]['goods_id']);
            // var_dump('>>>>>', $listData, $data['shop_basics_id'], $data['product'][0]['goods_id']);
            // exit;
            // 预售日期 $listData = Cache::get(Code::CACHE_PRESALE .'22_57');
            // if (!empty($listData)) {
            //     $listDataJson = json_decode($listData);
            //     $data['estimate_time'] = (new \Datetime())->setTimestamp($listDataJson->estimated_delivery_time)->format('Y-m-d H:i:s');
            // }
            if (!empty($data['pre_shipped_at'])) {
                $data['estimate_time'] = (new \Datetime())->setTimestamp($data['pre_shipped_at'])->format('Y-m-d H:i:s');
            } else {
                $data['estimate_time'] = '-';
            }
        }


        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年02月05日 19:25
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        $data =  $request->post();
        $addressData['order_buyer'] = $data;
        // 验证地址正确性
        $dataVal = $this->orderService->checkUpsAddress($addressData);
        if ($dataVal == Code::ORDER_TYPE_ABNORMAL) {
            return CatchResponse::fail($addressData['remarks'], Code::FAILED);
        }
        $orderData = $this->orderRecordsModel->where('id', $id)->find();
        $itemProduct = $this->orderItemRecords->field(['goods_code', 'goods_id', 'goods_type', 'sku'])
            ->where(['order_record_id' => $id])->find();

        // 查询收货信息ID
        $addressId = $this->orderBuyerRecords->where('order_record_id', $id)->value('id');
        // $data['address_name'] = $data['address_name'];
        $dataUser['updater_id'] = $data['creator_id'];
        $dataUser['updated_at'] = time();
        if ((int)$orderData->abnormal == 2) {
            if ((int)$itemProduct['goods_id'] == 0) {
                $dataUser['abnormal'] = 1;
            } else {
                $dataUser['abnormal'] = 3;
            }
        }
        // 更新订单状态
        $this->orderRecordsModel->where('id', $id)->update($dataUser);
        $num = $this->orderRecordsModel->findNum($addressData['order_buyer']['address_phone']);
        // 替换买家电话位数不足10位替换为10个0
        $addressData['order_buyer']['address_phone'] = strlen($num) < 10 ? '0000000000' : $addressData['order_buyer']['address_phone'];
        // 生成地址关联表
        // 更新 地址
        $this->orderBuyerRecords->updateBy($addressId, $addressData['order_buyer']);

        // 更新商品
        $dataShop = [];
        $dataShop['buyer_email'] = $data['address_email'];
        $dataShop['buyer_user_firstname'] = $data['buyer_firstname'];
        $dataShop['buyer_user_lastname'] = $data['buyer_lastname'];
        if (isset($data['goods_id'])) {
            if ((int)$data['goods_id'] != (int)$itemProduct['goods_id']) {
                $dataShop['goods_code'] = $data['goods_code'];
                $dataShop['goods_id'] = $data['goods_id'];
                $addressIds = $this->orderItemRecords->where('order_record_id', $id)->value('id');
                $this->orderItemRecords->updateBy($addressIds, $dataShop);
            }
        }
        return CatchResponse::success(true);
    }
    /**
     * 异常订单修改商品
     */
    public function updateProduct(Request $request, $id)
    {
        try {
            $data = $request->post();
            // 组合商品
            if ((int)$data['type'] == 1) {
                // 获取订单信息
                $order = $this->orderRecordsModel
                    ->field([
                        'shop_basics_id', 'order_type', 'abnormal', 'platform_no', 'platform_no_ext', 'shop_basics_id',
                        'platform', 'platform_id', 'status', 'get_at', 'total_num', 'total_price', 'paid_at',
                        'timezone', 'currency', 'order_type', 'order_source', 'creator_id', 'after_num1', 'after_num2',
                        'after_num3', 'after_num4', 'after_num5', 'after_have', 'after_refund_all'
                    ])
                    ->where('id', $id)->find()->toArray();
                if (!$order) {
                    return CatchResponse::fail('订单不存在');
                }
                // 获取商品信息
                $item = $this->orderItemRecords->field([
                    'goods_code', 'goods_id', 'goods_type', 'sku', 'item_id', 'product_code', 'quantity_purchased',
                    'transaction_price_currencyid', 'transaction_price_value', 'tax_amount_currencyid', 'tax_amount_value',
                    'name', 'buyer_email', 'buyer_user_firstname', 'buyer_user_lastname'
                ])
                    ->where(['order_record_id' => $id])->find()->toArray();
                $item['company_id'] = 0;
                $item['goods_type'] = Code::TYPE_PRODUCT; // 默認非組合商品
                // 获取地址信息
                $address = $this->orderBuyerRecords->where(['order_record_id' => $id, 'is_disable' => 1, 'type' => 0])->find();
                $order['order_buyer'] = \GuzzleHttp\json_decode($address, true);
                unset($order['order_buyer']['id']);
                unset($order['order_buyer']['created_at']);
                unset($order['order_buyer']['updated_at']);
                $res = $this->orderService->saveOrder($order, [$item], 2);
                if ($res) {
                    $this->orderRecordsModel->deleteBy($id);
                } else {
                    return  CatchResponse::fail('商品修改失败', Code::FAILED);
                }
            } else {
                // 获取订单信息
                $order = $this->orderRecordsModel->field(['shop_basics_id', 'order_type', 'abnormal'])->where('id', $id)->find();
                if (!$order) {
                    return CatchResponse::fail('订单不存在');
                }
                // 预售日期商品
                if ((int)$order['order_type'] == Code::ORDER_TYPE_PRESALES) {
                    $listData = Cache::get(Code::CACHE_PRESALE . $order['shop_basics_id'] . '_' . $data['product_id']);
                    if (!empty($listData)) {
                        $listDataJson = json_decode($listData);
                        $data['pre_shipped_at'] = $listDataJson->estimated_delivery_time;
                    }
                }
                // 修改商品信息
                if ($this->orderItemRecords->where(['order_record_id' => $id, 'type' => 0])
                    ->update(['goods_code' => $data['product_code'], 'goods_id' => $data['product_id'], 'updated_at' => time()])
                ) {
                    // 获取商品信息
                    $item = $this->orderItemRecords->field(['goods_code', 'goods_id', 'goods_type', 'sku'])
                        ->where(['order_record_id' => $id])->find();
                    // 获取地址信息
                    $address = $this->orderBuyerRecords->where(['order_record_id' => $id, 'is_disable' => 1, 'type' => 0])->find();
                    $order['order_buyer'] = $address;
                    // 获取订单 type 验证订单
                    $type = $this->orderService->checkOrderType($order, $item);

                    $this->orderRecordsModel->where('id', $id)->update([
                        'abnormal' => $order['abnormal'],
                        'order_type' => $type,
                        'updated_at' => time()
                    ]);
                }
            }
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 作废
     * @param Request $request
     * @param $id
     */
    public function invalid(Request $request, $id)
    {
        $orderData = $this->orderRecordsModel->where(['id' => $id])
            ->where('print_delivery_num', '0')
            ->whereNotIn('status', '6')
            ->where('order_type', 'in', [0, 1, 2, 3, 4, 5])->find();
        if ($orderData && (int)$orderData['order_type'] == 5) {
            return CatchResponse::fail('Fba订单不可作废');
        }
        $orderDelivery = new orderDeliverModel;
        $deliveryCount = $orderDelivery->where(['order_record_id' => $id, 'order_type_source' => 1])
            ->where('delivery_process_status', '2')
            ->whereNotIn('delivery_state', '6')
            ->count();
        $creator_id = $request->post('creator_id');
        if ($deliveryCount > 0) {
            // 修改已打印发货单个数
            $this->orderRecordsModel->updateBy($id, ['print_delivery_num' => $deliveryCount]);
            return CatchResponse::fail('发货单已打印不可作废');
        }
        // $this->orderRecordsModel->startTrans();
        if ($orderData) {
            $data['status'] = 6;
            $data['updater_id'] = $creator_id;
            if ($this->orderRecordsModel->updateBy($id, $data)) {
                // 获取需要作废发货单id
                $ids = $orderDelivery->where(['order_record_id' => $id, 'order_type_source' => 1])
                    ->where('delivery_process_status', '1')
                    ->whereNotIn('delivery_state', '6')
                    ->column('id');
                if ($ids) {
                    foreach ($ids as $value) {
                        if ($this->voidOrderFunc($value, $creator_id)) {
                            // 作废相对应发货单
                            $orderDelivery->where(['id' => $value])
                                ->update(['delivery_state' => 6, 'updated_at' => time()]);
                        }
                    }
                }
                // 作废发货单关联 报表
                $reportOrder = new ReportOrder;
                if ($id = $reportOrder->where('order_no', $orderData['order_no'])->value('id')) {
                    $reportOrder->deleteBy(strval($id), true);
                }
            }
            return CatchResponse::success('作废成功');
        } else {
            return CatchResponse::fail('请检查订单状态');
        }
    }
    /**
     * 订单导出
     *
     * @time 2021年03月24日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $type = $request->param('status');
        $res = $this->orderRecordsModel->getExportList($type);
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        ini_set('memory_limit', '1024M');
        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderRecordsModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '订单导出');

        return  CatchResponse::success($url);
    }
    /**
     * 保存信息 申请售后
     * @time 2021年03月25日 15:33
     * @param Request $request
     */
    public function createdAfterSale(AfterSaleOrderRequest $request, $id): \think\Response
    {
        try {
            $data = $request->post();
            $orderData = $this->orderRecordsModel->findBy($id);
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            // 判断订单状态是否作废
            if ($orderData['status'] == 6) {
                return CatchResponse::fail('请检查订单状态', Code::FAILED);
            }
            // 查找订单售后订单是否有进行中订单
            if ($this->afterSaleOrderModel->where(['status' => 0, 'order_id' => $id])->find()) {
                return CatchResponse::fail('已有售后订单待处理，请勿重复提交', Code::FAILED);
            }
            $addresModifyFee = 0;
            $recallModifyFee = 0;
            // 订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)
            if ((int)$orderData['order_type'] == 0 || (int)$orderData['order_type'] == 4) {
                // 查询修改地址费用
                $addresModifyFee = Config::where(['key' => 'orders.addresModifyFee'])->value('value');
                // 查询召回修改修改
                $recallModifyFee = Config::where(['key' => 'orders.recallModifyFee'])->value('value');
            }

            $data['order_id'] = $id;
            $data['platform_order_no'] = $orderData['order_no'];
            $data['platform_order_no2'] = $orderData['platform_no'];
            $data['platform_no_ext'] = $orderData['platform_no_ext'];

            $data['shop_id'] = $orderData['shop_basics_id'];
            $data['order_type'] = $orderData['order_type'];
            $data['company_id'] = $orderData['company_id'];

            // 退款订单
            if ((int) $data['type'] == Code::ORDER_SALES_REFUND) {
                $data['refund_amount'] = $data['refund_amount']; // 退款金额 等于售后产生费用
                $data['fill_amount'] = $data['refund_amount'];
                // if (bccomp($data['fill_amount'], '0') != 1) {
                //     return CatchResponse::fail('退款金额需要大于0', Code::FAILED);
                // }
                // 判断是否进行了全额退款
                if ($this->afterSaleOrderModel->where(['order_id' => $id, 'type' => 1, 'refund_type' => 2, 'status' => 1])->find()) {
                    return CatchResponse::fail('订单已经全额退款,不能在退款进行操作', Code::FAILED);
                }
            }
            // 退款退货
            if ((int) $data['type'] == Code::ORDER_SALES_REFUNDALL) {
                $data['refund_amount'] = $data['fill_amount']; // 退款金额
                $data['recall_num'] = $data['recall_num']; // 退款数量
                $data['refund_logistics'] = $data['refund_logistics']; // 退货物流
                $data['storage_id'] = $data['storage_id']; // 仓储id
                if (!isset($data['products'])) {
                    return CatchResponse::fail('退款退货商品信息不能为空', Code::FAILED);
                }
            }
            // 补货
            if ((int)$data['type'] == Code::ORDER_SALES_CPFR) {
                $data['replenish_type'] = $data['replenish_type']; // 补货类型 1-整件补货 2-配件补货
                $data['refund_logistics'] = $data['refund_logistics']; // 物流
                if (!isset($data['product'])) {
                    return CatchResponse::fail('补货商品/配件信息不能为空', Code::FAILED);
                }
                // 补货
                if ((int)$orderData['order_type'] == 3 || (int)$orderData['order_type'] == 4) {
                    $data['fill_amount'] = 0;
                } else {
                    $productPrice = new ProductPrice;
                    // ocean_freight benchmark_price order_operation_fee
                    if ($price = $productPrice->where(['product_id' => $data['product'][0]['goods_id'], 'is_status' => 1, 'status' => 1])->find()) {
                        $fee = bcadd(bcadd($price['ocean_freight'], $price['purchase_benchmark_price'], 2), $price['order_operation_fee'], 2);
                        $data['fill_amount'] = bcadd($price['all_tariff'], $fee, 2);
                    } else {
                        $data['fill_amount'] = 0;
                    }
                }
            }
            // 召回
            if ((int)$data['type'] == Code::ORDER_SALES_RECALL) {
                // $data['refund_amount'] = $data['refund_amount']; // 退款金额
                $data['recall_num'] = $data['recall_num']; // 召回数量
                $data['refund_logistics'] = $data['refund_logistics']; // 召回物流
                $data['storage_id'] = $data['storage_id']; // 召回仓储id
                $data['logistics_no'] = $data['logistics_no']; // 物流单号
                // 获取发货单订单快递费
                $fee = orderDeliverModel::where('id', $data['order_deliver_id'])->find();
                $feeAll = bcadd(bcmul(bcadd($fee['freight_weight_price'], $fee['freight_additional_price'], 2), $fee['number'], 2), $fee['postcode_fee'], 2);

                // 召回操作费
                $data['modify_amount'] = $recallModifyFee ?? '0';
                // 召回快递费 快递费 * 2
                $data['logistics_fee'] = bcmul($feeAll, 2, 2) ?? '0';
                // 召回售后产生费用
                $data['fill_amount'] = bcadd($data['modify_amount'], $data['logistics_fee'], 2);
            }
            // 修改地址
            if ((int)$data['type'] == Code::ORDER_SALES_MODIFY_ADDRESS) {
                $data['modify_amount'] = $addresModifyFee ?? 0;
                if (!isset($data['address'])) {
                    return CatchResponse::fail('请传入地址信息', Code::FAILED);
                }
            }
            // 修改地址
            if (isset($data['address'])) {
                $addressData['order_buyer'] = $data['address'][0];
                // 验证地址正确性
                $dataVal = $this->orderService->checkUpsAddress($addressData);
                if ($dataVal == Code::ORDER_TYPE_ABNORMAL) {
                    return CatchResponse::fail('地址验证失败', Code::FAILED);
                }
                $address = $addressData['order_buyer'];
            }
            $this->afterSaleOrderModel->startTrans();
            // // 生成售后订单
            if (!$res = $this->afterSaleOrderModel->storeBy($data)) {
                $this->afterSaleOrderModel->rollback();
                return CatchResponse::fail('售后单生成失败失败', Code::FAILED);
            } else {
                // 新增后修改订单售后统计
                $this->orderRecordsModel->updateAfterNum($id);
                $type = (int)$data['type'];
                // 退款
                if ($type == Code::ORDER_SALES_REFUND) {
                    $this->afterSaleOrderModel->commit();
                    return CatchResponse::success($res);
                }

                // 退款退货
                if ($type == Code::ORDER_SALES_REFUNDALL) {
                    if (isset($data['products'])) {
                        $listProducts = [];
                        foreach ($data['products'] as $key => $value) {
                            $dataProduct = $this->orderDeliverProducts->where('id', $value['id'])->find();
                            $row = [
                                'id' => $value['id'],
                                'return_num' => $data['recall_num'],
                                'after_amount' => $value['fill_amount'],
                                'after_order_id' => $res,
                            ];
                            $listProducts[] = $row;
                        }
                        if (!$this->orderDeliverProducts->saveAll($listProducts)) {
                            $this->afterSaleOrderModel->rollback();
                            return CatchResponse::fail('退款退货商品修改生成失败', Code::FAILED);
                        }
                        $this->afterSaleOrderModel->commit();
                        return CatchResponse::success($res);
                    }
                }
                // 补货发货
                if ($type == Code::ORDER_SALES_CPFR) {
                    // 补货
                    // 新增地址（增加一项类型，补货地址）
                    $address['is_disable'] = 1; // 启用
                    $address['order_record_id'] = $id;
                    $address['after_sale_id'] = $res;
                    $address['type'] = 1; // 0-正常订单 1-补货订单
                    // 保存地址信息
                    $this->orderBuyerRecords->storeBy($address);
                    $list = [];
                    $dataProduct = [$data['product'][0]];
                    foreach ($dataProduct as $key => $value) {
                        $row = [
                            'after_order_id' =>  $res,
                            'type' => 1, // 0-正常订单 1-补货订单
                            'goods_type' => $data['replenish_type'] == 1 ? 0 : 1, // 0-商品 1-配件
                            'goods_code' => $value['goods_code'],
                            'goods_id' => $value['goods_id'],
                            'freight_fee' => $value['freight_fee'] ?? '0',
                            'order_record_id' => $id,
                            'name' => $value['name'] ?? '',
                            'transaction_price_currencyid' => $value['transaction_price_currencyid'] ?? '',
                            'transaction_price_value' => $value['transaction_price_value'] ?? '0',
                            'quantity_purchased' => $data['recall_num'],
                            'tax_amount_currencyid' => $value['tax_amount_currencyid'] ?? '',
                            'tax_amount_value' => $value['tax_amount_value'] ?? '0'
                        ];
                        $list[] = $row;
                    }
                    // 新增商品(或者配件)
                    if (!$this->orderItemRecords->insertAllBy($list)) {
                        $this->afterSaleOrderModel->rollback();
                        return CatchResponse::fail('补货商品生成失败', Code::FAILED);
                    }
                    $this->afterSaleOrderModel->commit();
                    return CatchResponse::success($res);
                }
                // 召回
                if ($type == Code::ORDER_SALES_RECALL) {
                    if (isset($data['products'])) {
                        $listProducts = [];
                        foreach ($data['products'] as $key => $value) {
                            # 召回商品信息
                            $dataProduct = $this->orderDeliverProducts->where('id', $value['id'])->find();
                            $row = [
                                'id' => $value['id'],
                                'return_num' => $dataProduct['number'],
                                // 'after_amount' => $value['after_amount'],
                                'after_order_id' => $res,
                            ];
                            $listProducts[] = $row;
                        }
                        if (!$this->orderDeliverProducts->saveAll($listProducts)) {
                            $this->afterSaleOrderModel->rollback();
                            return CatchResponse::fail('召回商品修改生成失败', Code::FAILED);
                        }

                        $this->afterSaleOrderModel->commit();
                        return CatchResponse::success($res);
                    }
                }
                // 修改地址
                if ($type == Code::ORDER_SALES_MODIFY_ADDRESS) {
                    // 修改地址
                    $address['is_disable'] = 3; // 禁用
                    $address['order_record_id'] = $id;
                    $address['after_sale_id'] = $res;
                    // 保存地址信息
                    if (!$resAddress = $this->orderBuyerRecords->storeBy($address)) {
                        $this->afterSaleOrderModel->rollback();
                        return CatchResponse::fail('售后单地址生成失败失败', Code::FAILED);
                    }
                    $this->afterSaleOrderModel->commit();
                    return CatchResponse::success($res);
                }
            }
        } catch (\Exception $exception) {
            $this->afterSaleOrderModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 获取退款商品列表
     */
    public function productList($id)
    {
        return CatchResponse::success($this->orderItemRecords->where('order_record_id', $id)->select());
    }

    /**
     * 转为正常订单
     */
    public function orderConvert(Request $request, $id)
    {
        $dataObj = $request->post();
        $data = $this->orderRecordsModel->where(['id' => $id, 'order_type' => Code::ORDER_TYPE_ABNORMAL])->find();
        if (!$data) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        $productItem = $this->orderItemRecords->where('order_record_id', $id)->find();

        $data['order_buyer'] = $this->orderBuyerRecords->where(['order_record_id' => $id])->find();
        // 转为正常订单类型判断
        $type = $this->orderService->checkOrderType($data, $productItem);
        if ($data->abnormal == 1 || $data->abnormal == 2) {
            return CatchResponse::fail('请检查订单是否可转', Code::FAILED);
        }
        $orderData['order_type'] = $type ?? 0;
        $orderData['updater_id'] = $dataObj['creator_id'];
        $orderData['abnormal'] = 0;
        $this->orderRecordsModel->updateBy($id, $orderData);
        return CatchResponse::success(orderRecordsModel::$orderTypesData[$type]);
    }

    /**
     * 导入订单
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    public function orderImport(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->order($file['file']);
        foreach ($data as $order) {
            // 判断平台是否正确
            if (!$platformId = platformsModel::where('name', trim($order[3]))->value('id')) {
                return CatchResponse::fail(sprintf('订单【%s】平台【%s】不存在', $order[1], $order[3]));
            }
            // 判断店铺是否正确
            if (!$shopBasicsId = shopModel::where('shop_name', trim($order[4]))
                ->where('platform_id', $platformId)->value('id')) {
                return CatchResponse::fail(sprintf('订单【%s】平台【%s】的店铺【%s】不存在', $order[1], $order[3], $order[4]));
            }

            // // 判断店铺是否正确
            // if (!$shopBasicsData = shopModel::where('shop_name', trim($order[4]))->select()) {
            //     return CatchResponse::fail(sprintf('订单【%s】的店铺【%s】不存在', $order[1], $order[3]));
            // }
            // if (count($shopBasicsData) > 1) {
            //     return CatchResponse::fail(sprintf('订单【%s】的店铺【%s】存在重复', $order[1], $order[3]));
            // }
            // $shopBasicsId = $shopBasicsData[0]['id'];
            // $platformId = $shopBasicsData[0]['platform_id'];


            $order['shop_basics_id'] = $shopBasicsId;
            $order['platform_id'] = $platformId;
            // 店铺ID和商品编码是否匹配
            if (!$platformCode = $this->productPlatformSkuModel->checkProductSKUByShopId($shopBasicsId, trim($order[16]), trim($order[17]))) {
                return CatchResponse::fail(sprintf('订单【%s】店铺【%s】的商品【%s】不存在映射', $order[1], $order[4], $order[16]));
            }
            $order['platform_code'] = $platformCode['platform_code'];
            $order['product_id'] = $platformCode['product_id'];
            // 订单来源-导入
            $order['order_source'] = Code::ORDER_SOURCE_IMPORT;
            // 拼写数据
            $order = $this->orderRecordsModel->import($order);
            // 保存订单
            $res =  $this->orderService->saveOrder($order['data'], $order['item']);
        }
        return CatchResponse::success('导入成功' . $res);
    }

    /**
     * 订单导入模板下载
     * @param Request $request
     */
    public function template(Request $request)
    {
        return download(public_path() . 'template/orderImport.xlsx', 'orderImport.xlsx')->force(true);
    }

    /**
     * 发货单列表
     */
    public function orderDeliverList($id)
    {
        $list = orderDeliverModel::where('order_record_id', $id)->select()->each(function ($item) {
            $item['goods_code'] = $this->productModel->where('id', $item['goods_id'])->value('code');
        });
        return CatchResponse::success($list);
    }

    /**
     * Fba订单出库
     * @param $id 订单id
     */
    public function deliveryFba(Request $request, $id)
    {
        try {
            // 获取订单关联商品
            $order =  $this->orderRecordsModel->findBy($id);
            if ($order['is_delivery'] == 1) {
                return CatchResponse::fail('订单已经出库,请勿重复操作', Code::FAILED);
            }
            if (!$this->orderRecordsModel->deliveryFba($id)) {
                return CatchResponse::fail('商品库存不足，请去仓储管理>FBA仓调拨 中进行补货', Code::FAILED);
            }

            // 生成Fba报表订单信息
            // $reportOrder = new ReportOrder;
            // $reportOrder->saveOrder($order['order_no']);

            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            $this->outboundOrdersModel->rollback();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 单独生成Fba订单报表
     */
    public function fabOrderReportFix()
    {
        try {
            // 查询当前未生成报表的订单
            $order =  $this->orderRecordsModel->field('id, order_no')->where(['is_delivery' => 1, 'order_type' => 5])->select();
            if ($order && count($order) > 0) {
                foreach ($order as $key => $value) {
                    // 生成Fba报表订单信息
                    $reportOrder = new ReportOrder;
                    if (!$id = $reportOrder->where('order_no', $value['order_no'])->value('id')) {
                        $reportOrder->saveOrder($value['order_no']);
                    }
                }
            }
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 单独生成fba 报表
     */
    public function fbmOrderReportFix()
    {
        try {
            // 查询当前未生成报表的订单
            $order =  $this->orderRecordsModel->field('id, order_no')->whereIn('status', '2,3')
                ->whereIn('order_type', '0,4')->select();
            if ($order && count($order) > 0) {
                foreach ($order as $key => $value) {
                    // 生成Fba报表订单信息
                    $reportOrder = new ReportOrder;
                    if (!$id = $reportOrder->where('order_no', $value['order_no'])->value('id')) {
                        $reportOrder->saveOrder($value['order_no']);
                    }
                }
            }
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }



    /**
     * 新增客户订单商品选择
     * @param $id 客户id
     */
    public function orderCustomerProduct(Request $request, $id)
    {
        $list = $this->productModel->getCustomerProduct($id);
        return CatchResponse::success($list);
    }

    /**
     * 导入亚马逊订单用户缺失地址
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    public function orderAmazonImport(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->orderAmazon($file['file']);
        $error = [];
        $success = [];
        foreach ($data as $amazon) {
            // 获取订单信息
            if (!$order = $this->orderRecordsModel->field(['shop_basics_id', 'order_type', 'abnormal', 'id'])
                ->whereIn('abnormal', '2')
                ->where('platform_no', trim($amazon[0]))->find()) {
                return CatchResponse::fail(sprintf('订单号【%s】不存在', $amazon[0]));
            }

            // 判断电话 格式
            if (strpos($amazon[1], '+ ') !== false) {
                return CatchResponse::fail(sprintf('电话【%s】格式不正确 + 后不能有空格', $amazon[1]));
            }

            // 获取地址信息
            $address = $this->orderBuyerRecords->where(['order_record_id' => $order['id'], 'is_disable' => 1, 'type' => 0])->find();
            // 地址已同步的不再同步
            if (!empty($address['address_street1'])) {
                // 寫入不需要同步的訂單
                array_push($error, $amazon[0]);
                continue;
            }

            $num = $this->orderRecordsModel->findNum(($amazon[1]));
            // 替换买家电话位数不足10位替换为10个0
            $address['address_phone'] = strlen($num) < 10 ? '0000000000' : trim($amazon[1]);
            // 替换买家地址中的多个空格
            $address['address_street1'] = preg_replace("/\s(?=\s)/", "\\1", trim($amazon[2]));
            // 替换买家姓名中的特殊字符（\&）
            $address['address_name'] = str_replace(array("&", "\\"), array("*", "*"), trim($amazon[3]));
            $order['order_buyer'] = $address;
            //            // 获取商品信息
            //            $item = $this->orderItemRecords->field(['goods_code', 'goods_id', 'goods_type', 'sku'])
            //                ->where(['order_record_id' => $order['id']])->find();
            $order = json_decode($order, true);
            // 1.查询对比地址库信息
            $type = $this->orderService->checkUpsAddress($order);
            // 判断地址不异常
            if ($type != Code::ORDER_TYPE_ABNORMAL) {
                // 重置为商品异常
                $order['abnormal'] = Code::ABNORMAL_PRODUCT;
                $type = Code::ORDER_TYPE_ABNORMAL;
            }
            // 更新订单
            if ($this->orderRecordsModel->updateBy($order['id'], [
                'abnormal' => $order['abnormal'],
                'order_type' => $type,
                'updated_at' => time()
            ])) {
                // UPS建议地址
                $address['ship_street1'] = $order['order_buyer']['ship_street1'] ?? '';
                $address['ship_street2'] = $order['order_buyer']['ship_street2'] ?? '';
                $address['ship_street3'] = $order['order_buyer']['ship_street3'] ?? '';
                $address['ship_region'] = $order['order_buyer']['ship_region'] ?? '';
                $address['ship_postalcode'] = $order['order_buyer']['ship_postalcode'] ?? '';
                $address['ship_country'] = $order['order_buyer']['ship_country'] ?? '';
                $address['ship_stateorprovince'] = $order['order_buyer']['ship_stateorprovince'] ?? '';
                $address['ship_cityname'] = $order['order_buyer']['ship_cityname'] ?? '';
                // 更新订单地址
                $address->save();
                // 寫入已同步的訂單
                array_push($success, $amazon[0]);
            } else {
                return CatchResponse::fail('导入失败');
            }
        }
        $message = '';
        if (!empty($error)) {
            $message .= sprintf('订单号【%s】已存在地址，', implode(',', $error));
        }
        if (!empty($success)) {
            $message .= sprintf('订单号【%s】导入成功', implode(',', $success));
        }
        if (!empty($error)) {
            return CatchResponse::fail($message);
        } else {
            return CatchResponse::success($message);
        }
    }

    /**
     * 亚马逊订单用户缺失地址导入模板下载
     * @param Request $request
     */
    public function amazonTemplate(Request $request)
    {
        return download(public_path() . 'template/amazonImport.xlsx', 'amazonImport.xlsx')->force(true);
    }

    /**
     * 选择客户,客户订单
     * @param $id 客户id
     */
    public function getCompanyList(Request $request)
    {
        $company = new Company;

        return CatchResponse::success($company->getCompanyList());
    }

    /**
     * 订单导入模板下载
     * @param Request $request
     */
    public function customerTemplate(Request $request)
    {
        return download(public_path() . 'template/orderCustomerImport.xlsx')->force(true);
    }

    /**
     * 导入客户订单
     */
    public function importCustomerOrder(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $user = request()->user();
        $file = $request->file();
        $data = $import->read($file['file']);
        // 获取当前用户的角色ID
        $roleId = $request->user()->getRoles()[0]['id'];
        $dataList = [];
        $company = new Company;
        foreach ($data as $value) {
            // 判断客户是否存在
            if (!$companyData = $company->where('name', trim($value[0]))->find()) {
                $dataList['empty'][] = $value[0] . '客户不存在';
                continue;
            }
            // 判断账号是否客户账号
            if ($roleId == config('catch.permissions.company_role')) {
                if ((int)$user['id'] != (int)$companyData['account_id']) {
                    $dataList['empty'][] = $value[0] . '当前账号客户与导入客户不匹配';
                    continue;
                }
            }
            // 判断商品是否存在
            if (!$productData = $this->productModel->where(['code' => trim($value[2]), 'type' => 1])->find()) {
                $dataList['empty'][] = $value[0] . '商品不存在';
                continue;
            }
            $address = [
                'address_name' => trim($value[4]),
                'address_phone' => trim($value[6]),
                'address_email' => trim($value[5]),
                'address_country' => trim($value[7]),
                'address_country_name' => trim($value[7]),
                'address_postalcode' => trim($value[10]),
                'address_stateorprovince' => trim($value[8]),
                'address_cityname' => trim($value[9]),
                'address_street1' => trim($value[11]),
                'address_street2' => '',
                'address_street3' => '',
                'order_record_id' => 0
            ];
            $addressData['order_buyer'] = $address;
            // 验证地址正确性
            $dataVal = $this->orderService->checkUpsAddress($addressData);
            if ($dataVal == Code::ORDER_TYPE_ABNORMAL) {
                $dataList['fail'][] = $value[0] . '地址无效，匹配失败';
                continue;
            }
            $row = [
                'platform_no' => trim($value[1]) ?? '', // 平台订单编码
                'total_num' => $value[3],
                'company_id' => $companyData['id'],
                'order_source' => 2, // 导入
                'creator_id' => $user['id'],
                'currency' => 'USD',
                'timezone' => '+08:00',
                'order_type' => 3 // 客户订单
            ];
            if ($orderId = $this->orderRecordsModel->createBy($row)) {
                $dataList['success'][] = $value[0] . trim($value[1]);
                $product = [
                    'order_record_id' => $orderId,
                    'goods_id' => $productData['id'],
                    'goods_code' => $productData['code'],
                    'name' => $productData['name_ch'],
                    'quantity_purchased' => $value[3],
                    'goods_price' => $productData['purchase_price_usd'],
                    'transaction_price_currencyid' => 'USD'
                ];
                // 创建关联商品
                $this->orderItemRecords->createBy($product);

                $addressData['order_buyer']['order_record_id'] = $orderId;
                // 创建关联地址
                $this->orderBuyerRecords->createBy($addressData['order_buyer']);
            }
        }
        return CatchResponse::success($dataList);
    }
    /**
     * 借卖订单选择店铺
     */
    public function borrowSellShopList()
    {
        $shop = new shopModel;
        $list = $shop->getBorrowSellShopList();
        return CatchResponse::success($list);
    }
    /**
     * 借卖订单选择客户
     */
    public function borrowSellCompanyList()
    {
        $company = new Company;
        $list = $company->getBorrowSellCompanyList();
        return CatchResponse::success($list);
    }
    /**
     * 借卖订单选择商品
     */
    public function borrowSellGoodsList()
    {
        $list = $this->productModel->getBorrowSellGoodsList();
        return CatchResponse::success($list);
    }
    /**
     * 借卖订单导入模板下载
     */
    public function borrowSellTemplate(Request $request)
    {
        return download(public_path() . 'template/orderBorrowSellImport.xlsx')->force(true);
    }

    /**
     * 导入借卖订单
     */
    public function importBorrowSellOrder(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $user = request()->user();
        $file = $request->file();
        $data = $import->read($file['file']);
        $dataList = [];
        $company = new Company;
        $shopModel = new shopModel;
        foreach ($data as $value) {
            // 平台不存在
            if (!$platform = platformsModel::where('id', 11)->value('name')) {
                $dataList['empty'][] = $value[0] . '不存在客户平台';
                continue;
            }
            // 判断客户是否存在
            if (!$companyData = $company->where(['name' => trim($value[0]), 'is_status' => 1])->find()) {
                $dataList['empty'][] = $value[0] . '客户不存在/或者已禁用';
                continue;
            }
            // 判断店铺是否是借卖店铺
            if (!$shopId = $shopModel::where(['shop_name' => trim($value[1]), 'platform_id' => 11, 'is_status' => 1])->value('id')) {
                $dataList['empty'][] = $value[0] . '店铺不存在/或者已禁用';
                continue;
            }
            // 判断商品是否存在 只查询系统商品
            if (!$productData = $this->productModel->where(['code' => trim($value[7]), 'type' => 0])->find()) {
                $dataList['empty'][] = $value[0] . '商品不存在';
                continue;
            }

            $address = [
                'address_name' => trim($value[10]),
                'address_phone' => trim($value[12]),
                'address_email' => trim($value[11]),
                'address_country' => trim($value[13]),
                'address_country_name' => trim($value[13]),
                'address_stateorprovince' => trim($value[14]),
                'address_cityname' => trim($value[15]),
                'address_postalcode' => trim($value[16]),
                'address_street1' => trim($value[17]),
                'address_street2' => trim($value[18]) ?? '',
                'address_street3' => trim($value[19]) ?? '',
                'order_record_id' => 0
            ];
            $addressData['order_buyer'] = $address;
            // 验证地址正确性
            $dataVal = $this->orderService->checkUpsAddress($addressData);
            if ($dataVal == Code::ORDER_TYPE_ABNORMAL) {
                $dataList['fail'][] = $value[0] . $value[1] . $value[7] . '地址无效，匹配失败';
                continue;
            }
            $typeDelivery = trim($value[4]) == '平台自发' ? 0 : 1;
            if ($typeDelivery == 1 && (empty($value[5]) || empty($value[6]))) {
                $dataList['fail'][] = $value[0] . $value[1] . $value[7] . '客户发货，物流单号/物流公司不能为空';
                continue;
            }
            $row = [
                'platform_no' => trim($value[2]) ?? '', // 平台订单编码
                'platform_no_ext' => trim($value[3]) ?? '', // 平台订单编码
                'total_num' => $value[8],
                'company_id' => $companyData['id'],
                'order_source' => 2, // 导入
                'creator_id' => $user['id'],
                'currency' => 'USD',
                'order_type' => 2, // 借卖订单
                'shipping_name' => $value[5],
                'delivery_method' => $typeDelivery,
                'shipping_code' => $value[6],
                'settlement_price' => $value[9],
                'shop_basics_id' => $shopId,
                'platform_id' => 11,
                'timezone' => '+08:00',
                'platform' => $platform

            ];
            if ($orderId = $this->orderRecordsModel->createBy($row)) {
                $dataList['success'][] = $value[0] . trim($value[1]) . $value[7];
                $product = [
                    'order_record_id' => $orderId,
                    'goods_id' => $productData['id'],
                    'goods_code' => $productData['code'],
                    'name' => $productData['name_ch'],
                    'quantity_purchased' => $value[8],
                    'goods_price' => $productData['benchmark_price'], // 基准价格
                    'transaction_price_currencyid' => 'USD'
                ];
                // 创建关联商品
                $this->orderItemRecords->createBy($product);

                $addressData['order_buyer']['order_record_id'] = $orderId;
                // 创建关联地址
                $this->orderBuyerRecords->createBy($addressData['order_buyer']);
            }
        }
        return CatchResponse::success($dataList);
    }
    /**
     * 订单批量作废
     */
    public function orderInvalidMore(Request $request)
    {
        $data = $request->post();
        if (!$data['ids']) {
            return CatchResponse::fail('订单id不能为空', Code::FAILED);
        }
        $orderDelivery = new orderDeliverModel;
        $dataForm = [];
        $dataObj = [];
        $ids = [];
        foreach ($data['ids'] as $id) {
            $orderData = $this->orderRecordsModel->where(['id' => $id])
                ->where('print_delivery_num', '0')
                ->whereNotIn('status', '6')
                ->where('order_type', 'in', [0, 1, 2, 3, 4, 5])->find();
            $deliveryCount = $orderDelivery->where(['order_record_id' => $id, 'order_type_source' => 1])
                ->where('delivery_process_status', '2')
                ->whereNotIn('delivery_state', '6')
                ->count();
            if ($deliveryCount > 0) {
                // 修改已打印发货单个数
                $this->orderRecordsModel->updateBy($id, ['print_delivery_num' => $deliveryCount]);
                $dataForm['fail'][] = $id;
            } else {
                if ($orderData) {
                    $dataObj['status'] = 6;
                    $dataObj['updater_id'] = $data['creator_id'];
                    $dataObj['updated_at'] = time();
                    if ($this->orderRecordsModel->updateBy($id, $dataObj)) {
                        // 获取需要作废发货单id
                        $ids = $orderDelivery->where(['order_record_id' => $id, 'order_type_source' => '1'])
                            ->where('delivery_process_status', '1')
                            ->whereNotIn('delivery_state', '6')
                            ->column('id');
                        if ($ids) {
                            foreach ($ids as $value) {
                                if ($this->voidOrderFunc($value, $data['creator_id'])) {
                                    // 作废相对应发货单
                                    $orderDelivery->where(['id' => $value])
                                        ->update(['delivery_state' => 6, 'updated_at' => time()]);
                                }
                            }
                        }
                    }
                    $dataForm['success'][] = $id;
                } else {
                    $dataForm['fail'][] = $id;
                }
            }
        }
        // $ids = implode(',', $dataForm['success']);
        // $updater_id = $data['creator_id'];
        // $this->orderRecordsModel->whereIn('id', $ids)->update(['status' => 6, 'updated_at' => time(), 'updater_id' => $updater_id]);
        return CatchResponse::success($dataForm);
    }

    /**
     * 作废发货单库存扣减
     */
    public function voidOrderFunc($id, $updater_id)
    {
        $orderDelivery = new orderDeliverModel;
        // 查询订单状态
        $orderData = $orderDelivery->where(['id' => $id])
            ->where('delivery_process_status', 1) // 未打印
            ->whereNotIn('delivery_state', '3,4,5,6')
            ->find();
        if (!$orderData) {
            return false;
        }
        $orderItem = OrderDeliverProducts::where('order_deliver_id', $orderData['id'])->find();
        $productsAllot = [];
        // 成功发货单
        if ((int)$orderData['logistics_status'] == 1) {
            // 商品
            if (empty($orderData['goods_group_id'])) {
                $goodsId = $orderData['goods_code'];
            } else {
                $goodsId = $orderData['goods_group_name'];
            }
            // $goodsId = $orderData['goods_group_name'] ?? $orderData['goods_code'];
            if ((int)$orderItem['type'] == 1) {
                // 库存退回
                $products = new Product;
                $productData = $products->where('code', $goodsId)->find();
                $category = new Category;
                $categoryData = $category->where('id', $productData['category_id'])->find();
                $productsAllot[0] = [
                    'goods_id' => $productData['id'], // 多箱商品id
                    'goods_code' => $goodsId, // 多箱分组code
                    'category_name' => $categoryData['parent_name'] . $categoryData['name'],
                    'goods_name' => $productData['name_ch'],
                    'goods_name_en' => $productData['name_en'],
                    'goods_pic' => $productData['image_url'],
                    'packing_method' => $productData['packing_method'],
                    'number' => $orderData['number'],
                    'type' => 1,
                    'batch_no' => $orderItem['batch_no']
                ];
            } else {
                // 库存退回
                $parts = new Parts;
                $partsData = $parts->where('id', $orderData['goods_id'])->find();
                $category1 = new Category;
                $categoryData1 = $category1->where('id', $partsData['category_id'])->find();
                $productsAllot[0] = [
                    'goods_id' => (int)$orderData['goods_id'], // 多箱商品id
                    'goods_code' => $orderData['goods_code'], // 多箱分组code
                    'category_name' => $categoryData1['parent_name'] . $categoryData1['name'],
                    'goods_name' => $partsData['name_ch'] ?? '',
                    'goods_name_en' => $partsData['name_en'] ?? '',
                    'goods_pic' => $partsData['image_url'] ?? '',
                    'packing_method' => 1,
                    'number' => $orderData['number'],
                    'type' => 2,
                    'batch_no' => $orderItem['batch_no']
                ];
            }
            // var_dump('$productsAllot', $productsAllot);
            // exit;
            // $this->orderRecordsModel->startTrans();
            // 入库单
            $warehouseOrders = new WarehouseOrders;
            $dataWarehouse = [
                'code' => $warehouseOrders->createOrderNo(),
                'entity_warehouse_id' => $orderData['en_id'],
                'virtual_warehouse_id' => $orderData['vi_id'],
                'source' => 'void',
                'notes' => '发货单作废退货入库',
                'audit_status' => 2,
                'audit_notes' => '自动通过',
                'audit_by' => $updater_id,
                'audit_time' => date('Y-m-d H:i:s'),
                'warehousing_status' => 1,
                'warehousing_time' => date('Y-m-d H:i:s'),
                'created_by' => $updater_id,
                'products' => $productsAllot
            ];
            //变更库存 增加库存 // 单入库
            $idWarehouseStock = $warehouseOrders->createWarehouseOrder($dataWarehouse);
            $warehouseStock = new WarehouseStock;
            foreach ($productsAllot as $product) {
                $warehouseStock->increaseStock(
                    $orderData['en_id'],
                    $orderData['vi_id'],
                    $product['goods_code'],
                    $product['batch_no'],
                    $product['number'],
                    $product['type'],
                    'delivery',
                    $idWarehouseStock ?? $id,
                    $id
                );
            }
        }
        return true;
    }

    /**
     * 修正订单作废状态
     * print_delivery_num
     */
    public function orderDeliveryNumFix()
    {
        $orderData = $this->orderRecordsModel->whereNotIn('status', '6')->where('print_delivery_num', 0)->column('id');
        $orderDelivery = new orderDeliverModel;
        $deliveryCount = 0;
        $dataForm = [];
        foreach ($orderData as $id) {
            $deliveryCount = $orderDelivery->where(['order_record_id' => $id, 'order_type_source' => 1])
                ->where('delivery_process_status', '2')
                ->whereNotIn('delivery_state', '6')
                ->count();
            if ($deliveryCount > 0) {
                $this->orderRecordsModel->where(['id' => $id])
                    ->update(['print_delivery_num' => $deliveryCount]);
                $dataForm['success'][] = $id;
            } else {
                $dataForm['fail'][] = $id;
            }
        }
        return CatchResponse::success($dataForm);
    }

    /**
     * 获取所有订单列表 index
     */
    public function getAllOrder(Request $request)
    {
        return CatchResponse::success($this->orderRecordsModel->field('id, order_no, order_type')->whereNotIn('status', '6')->select());
    }

    /**
     * 批量自动修改商品映射
     */
    public function modifyGoodMapping(Request $request)
    {
        try {
            $data = $request->post();

            if (empty($data['orders'])) {
                return CatchResponse::fail('订单编码不能为空');
            }
            $orders = $data['orders'];
            $dataObj = [];
            $this->orderRecordsModel->startTrans();
            foreach ($orders as $key => $orderNo) {
                if (!$orderObj = $this->orderRecordsModel->where('order_no', $orderNo)->where(['abnormal' => 1])->find()) {
                    $dataObj['fail'][] = $orderNo;
                    continue;
                } else {
                    $sku = $this->orderItemRecords->where(['order_record_id' => $orderObj['id']])->value('sku');
                    if (!$platformProductData = $this->productPlatformSkuModel->checkProductSKUByShopIdNew($orderObj['shop_basics_id'], $sku)) {
                        $dataObj['fail'][] = $orderNo . '商品编码不存在';
                        continue;
                    } else {
                        // 组合商品
                        if ((int)$platformProductData['type'] == 1) {
                            // 获取订单信息
                            $order = $this->orderRecordsModel
                                ->field([
                                    'shop_basics_id', 'order_type', 'abnormal', 'platform_no', 'platform_no_ext', 'shop_basics_id',
                                    'platform', 'platform_id', 'status', 'get_at', 'total_num', 'total_price', 'paid_at',
                                    'timezone', 'currency', 'order_type', 'order_source', 'creator_id', 'after_num1', 'after_num2',
                                    'after_num3', 'after_num4', 'after_num5', 'after_have', 'after_refund_all'
                                ])
                                ->where('id', $orderObj['id'])->find()->toArray();
                            if (!$order) {
                                $dataObj['fail'][] = $orderNo;
                                continue;
                            }
                            // 获取商品信息
                            $item = $this->orderItemRecords->field([
                                'goods_code', 'goods_id', 'goods_type', 'sku', 'item_id', 'product_code', 'quantity_purchased',
                                'transaction_price_currencyid', 'transaction_price_value', 'tax_amount_currencyid', 'tax_amount_value',
                                'name', 'buyer_email', 'buyer_user_firstname', 'buyer_user_lastname'
                            ])
                                ->where(['order_record_id' => $orderObj['id']])->find()->toArray();

                            $item['company_id'] = 0;
                            $item['goods_type'] = Code::TYPE_PRODUCT; // 默認非組合商品
                            // 获取地址信息
                            $address = $this->orderBuyerRecords->where(['order_record_id' => $orderObj['id'], 'is_disable' => 1, 'type' => 0])
                                ->find();

                            $order['order_buyer'] = \GuzzleHttp\json_decode($address, true);
                            unset($order['order_buyer']['id']);
                            unset($order['order_buyer']['created_at']);
                            unset($order['order_buyer']['updated_at']);
                            $res = $this->orderService->saveOrder($order, [$item], 2);
                            if ($res) {
                                $this->orderRecordsModel->deleteBy((string)$orderObj['id']);
                                $dataObj['scuess'][] = $orderNo;
                            } else {
                                $dataObj['fail'][] = $orderNo;
                                continue;
                            }
                        } else {
                            // 获取订单信息
                            $order = $this->orderRecordsModel->field(['shop_basics_id', 'order_type', 'abnormal'])->where('id', $orderObj['id'])->find();
                            if (!$order) {
                                $dataObj['fail'][] = $orderNo;
                                continue;
                            }
                            // 预售日期商品
                            if ((int)$order['order_type'] == Code::ORDER_TYPE_PRESALES) {
                                $listData = Cache::get(Code::CACHE_PRESALE . $orderObj['shop_basics_id'] . '_' . $platformProductData['product_id']);
                                if (!empty($listData)) {
                                    $listDataJson = json_decode($listData);
                                    $data['pre_shipped_at'] = $listDataJson->estimated_delivery_time;
                                }
                            }
                            // 修改商品信息
                            if ($this->orderItemRecords->where(['order_record_id' => $orderObj['id'], 'type' => 0])
                                ->update(['goods_code' => $platformProductData['product_code'], 'goods_id' => $platformProductData['product_id'], 'updated_at' => time()])
                            ) {
                                // 获取商品信息
                                $item = $this->orderItemRecords->field(['goods_code', 'goods_id', 'goods_type', 'sku'])
                                    ->where(['order_record_id' => $orderObj['id']])->find();
                                // 获取地址信息
                                $address = $this->orderBuyerRecords->where(['order_record_id' => $orderObj['id'], 'is_disable' => 1, 'type' => 0])->find();
                                $order['order_buyer'] = $address;
                                // 获取订单 type 验证订单
                                $type = $this->orderService->checkOrderType($order, $item);

                                $this->orderRecordsModel->where('id', $orderObj['id'])->update([
                                    'abnormal' => $order['abnormal'],
                                    'order_type' => $type,
                                    'updated_at' => time()
                                ]);
                                $dataObj['success'][] = $orderNo;
                            }
                        }
                    }
                }
            }
            $this->orderRecordsModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $exception) {
            $this->orderRecordsModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
}
