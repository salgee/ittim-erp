<?php

namespace catchAdmin\order\model;

use catchAdmin\basics\model\Shop;
use catcher\base\CatchModel as Model;
use catchAdmin\order\model\search\OrderRecordsSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users as usersModel;
use catchAdmin\basics\model\Shop as shopModel;
use catcher\Code;
use catcher\exceptions\FailedException;
use think\App;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\product\model\Product;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\warehouse\model\OutboundOrderProducts;
use catchAdmin\warehouse\model\WarehouseStock as warehouseStockModel;
use catchAdmin\permissions\model\Users;
use catcher\Utils;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\report\model\ReportOrder;
use catchAdmin\order\model\AfterSaleOrder;


class OrderRecords extends Model
{
    use DataRangScopeTrait;
    use OrderRecordsSearch;
    use HasOrderItemsTrait;
    use HasOrderBuyerTrait;

    // 表名
    public $name = 'order_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // 订单编号（系统自动生成O+年月日+5位流水）
        'order_no',
        // 平台订单编号1
        'platform_no',
        // 平台订单编号2
        'platform_no_ext',
        // 平台名称
        'platform',
        // 平台ID
        'platform_id',
        // 店铺ID
        'shop_basics_id',
        // 新状态 1-待发货 2-发货中（已有部分发货） 3-已发货（全部完成发货） 6-作废
        // 订单状态(1-待发货、2-已发货、3-运输中、4-配送中、5-已收货、6-作废订单)
        'status',
        // 是否存在售后(1-是；0-否)
        'after_sale_status',
        // 是否出库（FBA订单使用）1-已出库 0-未出库
        'is_delivery',
        // 发货状态（0-未发货订单，1-成功发货订单，2-异常发货订单）
        'logistics_status',
        // 合计金额
        'total_price',
        //订单仓储费用
        'order_storage_fee',
        //订单操作费
        'order_operation_fee',
        //订单物流费
        'order_logistics_fee',
        // 订单拉取时间
        'get_at',
        // 合计数量
        'total_num',
        // 发货时间
        'shipped_at',
        // 支付时间
        'paid_at',
        // 币种
        'currency',
        // 运输方式
        'shipping_method',
        // 物流公司名称
        'shipping_name',
        // 买家备注
        'platform_remark',
        // 订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)
        'order_type',
        // 订单来源;0-平台接口;1-录入;2-导入
        'order_source',
        // 异常订单类型;0-正常;1-商品异常;2-地址异常 3-异常已处理;
        'abnormal',
        // 备注
        'remarks',
        // 时区
        'timezone',
        // 所属客户ID 关联 company
        'company_id',
        // 预计发货时间
        'pre_shipped_at',
        // 借卖订单发货类型 0-自发 1-客户
        'delivery_method',
        // 快递运单号
        'shipping_code',
        // 结算单价
        'settlement_price',
        'after_num1',
        'after_num2',
        'after_num3',
        'after_num4',
        'after_num5',
        // 售后进行中数量
        'after_have',
        // 售后是否有全部退款
        'after_refund_all',
        // 关联发货单已打印数量
        'print_delivery_num',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 修改人ID
        'updater_id',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    /**
     * 平台类型
     */
    public static $orderSource = array(
        Code::ORDER_SOURCE_API => '平台接口',
        Code::ORDER_SOURCE_INSERT => '录入',
        Code::ORDER_SOURCE_IMPORT => '导入'
    );

    /**
     * 订单状态
     */
    public static $orderStatus = array(
        Code::ORDER_UNSHIPPED => '待发货',
        Code::ORDER_SHIPPED => '发货中',
        Code::ORDER_TRAFFIC => '已发货',
        Code::ORDER_DELIVERY => '配送中',
        Code::ORDER_DELIVERED => '已收货',
        Code::ORDER_CANCELED => '已作废',
        Code::ORDER_REFUND => '已退款'
    );

    /**
     * 订单类型
     */
    public static $orderTypesData = array(
        Code::ORDER_TYPE_SALES => '销售订单',
        Code::ORDER_TYPE_ABNORMAL => '异常订单',
        Code::ORDER_TYPE_LOAN => '借卖订单',
        Code::ORDER_TYPE_CUSTOMER => '客户订单',
        Code::ORDER_TYPE_PRESALES => '预售订单',
        Code::ORDER_TYPE_FBA => 'FBA订单'
    );

    /**
     * @param $id 订单id
     */
    public function updateAfterNum($id)
    {
        $data = [];
        $data['after_num1'] = AfterSaleOrder::where(['order_id' => $id, 'type' => 1, 'status' => 1])->count();
        $data['after_num2'] = AfterSaleOrder::where(['order_id' => $id, 'type' => 2, 'status' => 1])->count();
        $data['after_num3'] = AfterSaleOrder::where(['order_id' => $id, 'type' => 3, 'status' => 1])->count();
        $data['after_num4'] = AfterSaleOrder::where(['order_id' => $id, 'type' => 4, 'status' => 1])->count();
        $data['after_num5'] = AfterSaleOrder::where(['order_id' => $id, 'type' => 5, 'status' => 1])->count();
        $data['after_have'] = AfterSaleOrder::where(['order_id' => $id])->whereNotIn('status', '1')->count();
        $data['after_refund_all'] = AfterSaleOrder::where(['order_id' => $id, 'refund_type' => 2,  'type' => 1, 'status' => 1])->count();
        $this->where('id', $id)->update($data);
    }
    protected $append
    = [
        'after_num1', 'after_num2', 'after_num3', 'after_num4', 'after_num5', 'after_have', 'after_refund_all'
    ];

    /**
     * 重写订单列表数据
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
        $whereBaiscs = [
            ['oa.is_disable', '=', 1],
            ['oa.type', '=', 0],
            ['op.type', '=', 0]
        ];
        $whereBaiscsTwo = [];
        // // 非作废订单
        if ((int)$type != Code::ORDER_CANCELED) {
            $whereBaiscsTwo = [['o.status', 'in', '1,2,3,4,5']];
        }
        $searchData = $this->dataRange()
            ->alias('o')
            ->withoutField(['deleted_at'], true)
            ->field(
                'o.created_at as created_at_order, u.username as updater_username, p.category_id as category_id, p.code as product_code, p.name_ch, 
            p.category_id, oa.address_name, oa.address_phone, oa.address_postalcode, o.print_delivery_num
            , oa.address_email, oa.address_name, oa.address_stateorprovince, oa.address_cityname, 
            op.goods_id, op.goods_code, op.sku, op.name as sku_nam, op.buyer_email, op.quantity_purchased ,op.tax_amount_value
            ,op.tax_amount_currencyid, cag.parent_name, cag.name as category_name, cp.name as company_name'
            )
            ->catchSearch()
            ->where($whereBaiscs)
            ->where($whereBaiscsTwo)
            ->whereOr(function ($query) use ($whereOr, $whereBaiscs, $where, $whereBaiscsTwo) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->where($whereBaiscs)
                        ->where($whereBaiscsTwo)
                        ->catchSearch();
                }
            })
            ->catchLeftJoin(shopModel::class, 'id', 'shop_basics_id', ['shop_name'])
            ->leftJoin('order_buyer_records oa', 'oa.order_record_id= o.id')
            ->leftJoin('order_item_records op', 'op.order_record_id= o.id')
            ->catchLeftJoin(usersModel::class, 'id', 'creator_id', ['username as creator_username'])
            ->leftJoin('users u', 'o.updater_id=u.id')
            ->leftJoin('product p', 'p.id = op.goods_id')
            ->order('o.id', 'desc');

        $ListData = $searchData->leftJoin('category cag', 'p.category_id=cag.id');
        // $ListData = $searchData->leftJoin('after_sale_order afo', 'afo.order_id=o.id and afo.status=1');
        $ListData = $searchData->leftJoin('company cp', 'o.company_id=cp.id');
        $ListData = $searchData->group('o.id');
        $ListData = $searchData->paginate();
        // $ListData = $searchData->fetchSql()->find(1);
        // var_dump($ListData);
        // exit;

        return $ListData;
    }
    /**
     * 订单详情
     * @return object
     */
    public function orderGoods($id)
    {
        $list_field = [
            'o.shop_basics_id', 'o.id', 'o.order_no', 'o.status', 'o.order_type',
            'u.goods_code', 'u.goods_id', 'o.platform_id', 'o.platform_no', 'u.warehouse_id', 'u.quantity_purchased',
            'u.transaction_price_currencyid', 'u.transaction_price_value', 'u.tax_amount_value', 'u.tax_amount_currencyid',
            'u.order_record_id', 'p.name_ch', 'p.name_en', 'p.code', 'p.image_url',
            'p.packing_method', 'p.merge_num', 'p.hedge_price', 'p.benchmark_price',
            'p.company_id', 'p.insured_price', 'r.address_postalcode', 'p.type',
            'ca.name as category_name', 'p.name_ch', 'p.name_en', 'p.image_url as goods_pic',
            'pi.length_AS', 'pi.width_AS', 'pi.height_AS', 'pi.weight_gross_AS'
            // 'sf.goods_pic'
        ];
        return $this->field($list_field)
            ->alias('o')
            ->where('o.id', $id)
            ->where('u.type', 0)
            ->where('o.status', '<=', 1)
            ->where('o.order_type', '<>', 5)
            // ->where('o.logistics_status', '=', 0)
            ->leftJoin('order_item_records u', 'u.order_record_id = o.id')
            ->leftJoin('product p', 'p.id = u.goods_id')
            ->leftJoin('product_info pi', 'pi.product_id = u.goods_id')
            // ->leftJoin('product p', 'p.id = u.goods_id and p.status = 1')
            ->leftJoin('category ca', 'ca.id = p.category_id ')
            // ->leftJoin('sales_forecast_products sf', 'sf.goods_id = p.id ')
            ->leftJoin('order_buyer_records r', 'r.order_record_id = o.id and r.is_disable = 1')
            ->find();
    }

    /**
     * 查询是否存在同订单同商品
     * findOrderProduct
     * @param $platform_no 订单编号
     * @param $goods_id //商品id
     * @param $sku //sku
     * @param $shop_id
     */
    public function findOrderProduct($platform_no, $goods_id, $sku = '', $shop_id)
    {
        $res = $this->alias('o')
            ->where('o.platform_no', $platform_no)
            ->where('op.goods_id', $goods_id);
        if (!empty($sku)) {
            $res->where('op.sku', $sku);
        }
        return $res->where('o.shop_basics_id', $shop_id)
            ->leftJoin('order_item_records op', 'op.order_record_id=o.id')
            ->count();
    }

    /**
     * * 导出数据
     * @param $fileData  导出字段集合
     */
    public function getExportList($type = '')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'o.company_id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $whereBaiscs = [
            ['oa.is_disable', '=', 1],
            ['oa.type', '=', 0],
            ['op.type', '=', 0]
        ];
        $whereBaiscsTwo = [];
        if ((int)$type != Code::ORDER_CANCELED) {
            $whereBaiscsTwo = [['o.status', '<>', '6']];
        }
        $fileList = [
            'o.*', 'o.is_delivery as is_deliverys', 'o.id as order_ids', 'o.created_at as created_at_order',
            'op.*', 'oa.*', 'oa.address_name', 'oa.address_phone',
            'oa.address_postalcode', 'ca.parent_name', 'ca.name as category_name', 'p.name_ch',
            'p.name_en', 'c.name as company_name', 'p.code as codes'
        ];
        // ini_set('display_errors', 1);            //错误信息
        // ini_set('display_startup_errors', 1);    //php启动错误信息
        // error_reporting(-1);                    //打印出所有的 错误信息
        ini_set('memory_limit', '1024M');

        $list = $this->dataRange()
            ->field($fileList)
            // ->whereOr($where)
            // ->whereOr($whereOr)
            ->alias('o')
            ->where($whereBaiscs)
            ->catchSearch()
            ->where($whereBaiscsTwo)
            ->whereOr(function ($query) use ($whereOr, $whereBaiscs, $where, $whereBaiscsTwo) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->where($whereBaiscs)
                        ->where($whereBaiscsTwo)
                        ->catchSearch();
                }
            })
            ->catchLeftJoin(shopModel::class, 'id', 'shop_basics_id', ['shop_name'])
            ->leftJoin('order_item_records op', 'op.order_record_id= o.id')
            ->leftJoin('order_buyer_records oa', 'oa.order_record_id= o.id')
            ->leftJoin('product p', 'p.id = op.goods_id')
            ->leftJoin('category ca', 'ca.id = p.category_id')
            // ->leftJoin('shop_basics sb', 'sb.id= o.shop_basics_id')
            ->leftJoin('company c', 'c.id=o.company_id')
            ->order('o.id', 'desc')
            ->select()
            ->each(function (&$item) {
                $item['goods_code'] = $item['codes'];
                $item['category_names'] = $item['parent_name'] . '-' . $item['category_name'];
                $item['total_amount'] = bcmul(
                    $item['quantity_purchased'],
                    bcadd($item['transaction_price_value'], $item['tax_amount_value'], 2),
                    2
                );
                $item['tax_amount_value_all'] = bcmul($item['quantity_purchased'], $item['tax_amount_value'], 2);
                // $item['get_at'] = (new \Datetime())->setTimestamp($item['get_at'])->format('Y-m-d H:i:s');
                if (!empty($item['shipped_at'])) {
                    $item['shipped_at'] = (new \Datetime())->setTimestamp($item['shipped_at'])->format('Y-m-d H:i:s');
                } else {
                    $item['shipped_at'] = '';
                }
                $item['get_at'] = date('Y-m-d H:i:s', $item['get_at']);
                //  $data['paid_at_text'] = date('Y-m-d H:i:s', $data['paid_at']);
                // 转换美国时间
                $item['created_at'] = Utils::toNewYorkTime($item['created_at_order'], $item['timezone']);
                // $data['paid_at'] = Utils::toNewYorkTime($data['paid_at'], $data['timezone']);
                $item['platform_no'] = strval($item['platform_no']);
                if ((int)$item['order_type'] == Code::ORDER_TYPE_FBA) {
                    $item['status_text'] = (int)$item['is_deliverys'] == 1 ? '已出库' : '待出库';
                } else {
                    $item['status_text'] = $this::$orderStatus[$item['status']];
                }
                $item['delivery_method_text'] = $item['delivery_method'] == 1 ? '客户发货' : '平台自发';
            });
        return $list;
    }
    /**
     * 订单导入数据重组
     * @param $data
     * @return array
     */
    public function import($data)
    {
        try {
            $order = [
                // 基本信息
                'platform_no' => $data[1],
                'platform_no_ext' => $data[2],
                'platform' => $data[3],
                'platform_id' => $data['platform_id'],
                'shop_basics_id' => $data['shop_basics_id'],
                'status' => Code::ORDER_UNSHIPPED,
                'get_at' => time(),
                'paid_at' => (new \DateTime($data[5]))->getTimestamp(),
                'order_type' => Code::ORDER_TYPE_SALES,
                'order_source' => Code::ORDER_SOURCE_IMPORT,
                // todo:计算商品金额信息
                'total_price' => 0, // 订单总额
                'currency' => '', // 币别
                'timezone' => 'UTC', // 默认时区
                'creator_id' => request()->user()->id,
                // 收货人信息
                'order_buyer' => [
                    'address_name' => trim($data[6]),
                    'address_email' => trim($data[7]),
                    'address_phone' => trim($data[8]),
                    'address_postalcode' => trim($data[9]),
                    'address_country' => trim($data[10]),
                    'address_stateorprovince' => trim($data[11]),
                    'address_cityname' => trim($data[12]),
                    'address_street1' => trim($data[13]),
                    'address_street2' => trim($data[14]) ?? '',
                    'address_street3' => trim($data[15]) ?? ''
                ]
            ];
            //商品信息
            $item[] = [
                'item_id' => 0, // 商品 id
                'product_code' => 0,
                'name' => '',
                'sku' => $data['platform_code'],
                'goods_id' => $data['product_id'],
                'goods_code' => $data[16],
                'quantity_purchased' => $data[18],
                'transaction_price_value' => $data[19],
                'tax_amount_currencyid' => $data[20],
                'transaction_price_currencyid' => $data[20],
                'tax_amount_value' => $data[22],
                'buyer_email' => trim($data[7]),
                'buyer_user_firstname' => trim($data[6]),
                'buyer_user_lastname' => '',
                'company_id' => 0,
                'goods_type' => Code::TYPE_PRODUCT // 默認非組合商品
            ];
            return ['data' => $order, 'item' => $item];
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }

    public function orderItem()
    {
        return $this->hasOne(OrderItemRecords::class, 'order_record_id', 'id');
    }

    public function shop()
    {
        return $this->hasOne(Shop::class, 'id', 'shop_basics_id');
    }

    public function buyer()
    {
        return $this->hasOne(OrderBuyerRecords::class, 'order_record_id', 'id');
    }

    public function orderDeliverProducts()
    {
        return $this->hasMany(OrderDeliverProducts::class, 'order_id', 'id');
    }

    /**
     * FBA 订单出库
     * $id 订单id
     */
    public function deliveryFba($id, $uid = 0)
    {
        $data['creator_id'] = $uid ?? request()->user()->id;
        $products = OrderItemRecords::where('order_record_id', $id)->find();
        // 获取Fba 仓库 启用状态 ->where('parent_id', '<>', 0)
        if (!$warehouseData = Warehouses::where(['type' => 4, 'is_active' => 1])->find()) {
            return false;
        };

        $orderNumberAll = 0;
        $product = new Product;
        $productData = $product->categoryNames($products->goods_id);
        $productsGroups = [];
        // 1-普通商品 2-多箱包装
        if ((int)$productData['packing_method'] == 2) {
            // 获取多箱商品分组信息
            $productGroup = new ProductGroup;
            $productsGroups = $productGroup->where('product_id', $products->goods_id)->select();
            foreach ($productsGroups as $key => $value) {
                # code...
                $productsAll[] = [
                    'goods_id' => $products->goods_id,
                    'goods_code' => $value['name'],
                    'type' => 1,
                    'number' => $products->quantity_purchased,
                    'category_name' => $productData->category_name,
                    'goods_name' => $productData->goods_name,
                    'goods_name_en' => $productData->goods_name_en,
                    'goods_pic' => $productData->goods_pic,
                ];
            }
            $orderNumberAll = count($productsGroups) * ($products->quantity_purchased);
        } else {
            // 计算出库批次
            $productsAll[] = [
                'goods_id' => $products->goods_id,
                'goods_code' => $products->goods_code,
                'type' => 1,
                'number' => $products->quantity_purchased,
                'category_name' => $productData->category_name,
                'goods_name' => $productData->goods_name,
                'goods_name_en' => $productData->goods_name_en,
                'goods_pic' => $productData->goods_pic,

            ];
            $orderNumberAll = $products->quantity_purchased;
        }

        $allotOrdersModel = new AllotOrders;
        $productsAll = $allotOrdersModel->getOutboundOrderProducts($warehouseData->id, $warehouseData->id, $productsAll);
        // 判断是否
        if (empty($productsAll) && count($productsAll) < 1 || (count($productsAll) < count($productsGroups))) {
            return false;
        } else {
            $number = array_column($productsAll, 'number');
            $numberAll = array_sum($number);
            if ($orderNumberAll != $numberAll) {
                return false;
            }
            $warehouseStockModel = new warehouseStockModel;
            // 查询实时库存
            foreach ($productsAll as $key => $value) {
                # code...
                $num = $warehouseStockModel->where(
                    [
                        'goods_code' => $value['goods_code'],
                        'batch_no' => $value['batch_no'],
                        'virtual_warehouse_id' => $warehouseData->id,
                        'entity_warehouse_id' => $warehouseData->id
                    ],
                )
                    ->sum('number');
                if ((int)$num < (int)$value['number']) {
                    return false;
                }
            }
            $OutboundOrders = new OutboundOrders;
            $OutboundOrders->startTrans();
            // 审核通过 生成出库单
            $orderData = [
                'entity_warehouse_id' => $warehouseData->id,
                'virtual_warehouse_id' => $warehouseData->id,
                'source' => 'sales', // 销售
                'audit_status' => 2,   //fab出库单默认已审核
                'outbound_status' => 1, //fab出库单默认已出库
                'outbound_time' => date('Y-m-d H:i:s'),
                'created_by' => $data['creator_id'],
                'products' => $productsAll,
            ];
            $idOutboundOrders = $OutboundOrders->createOutOrder($orderData);
            // 变更库存
            foreach ($productsAll as $product) {
                $warehouseStockModel->reduceStock(
                    $warehouseData->id,
                    $warehouseData->id,
                    $product['goods_code'],
                    $product['batch_no'],
                    $product['number'],
                    $product['type'],
                    'orderfba',
                    $idOutboundOrders ?? $id,
                    $id ?? 0
                );
            }
            // 出库单关联商品保存
            $OutboundOrderProducts = new OutboundOrderProducts;
            if ($OutboundOrderProducts->saveAll($productsAll)) {
                // 修改原始订单出库状态
                $this->updateBy($id, ['is_delivery' => 1, 'updated_at' => time()]);
                $reportOrder = new ReportOrder;
                $reportOrder->saveOrder('', $id);

                $OutboundOrders->commit();
            } else {
                $OutboundOrders->rollback();
                return false;
            }
            return true;
        }
    }
    /**
     * 其他订单发货订单出库
     * $row 订单id
     */
    public function deliveryOther($row, $warehouseData, &$list, $type = 1)
    {
        $data = request()->user();
        $products = OrderItemRecords::where('order_record_id', $row['order_record_id'])->find();
        $Product = new Product;
        $productData = $Product->categoryNames($products->goods_id);
        $productsAll = [];
        // 计算出库批次
        foreach ($list as $value) {
            $productsAll[] = [
                'goods_id' => $value['goods_id'],
                'goods_code' => $value['packing_method'] == 1 ? $value['goods_code'] : $value['goods_group_name'],
                'type' => $type, // 1-商品 2-配件
                'number' => $value['number'] ?? $value['goods_number'],
                'category_name' => $productData->category_namea,
                'goods_name' => $productData->goods_name,
                'goods_name_en' => $productData->goods_name_en,
                'goods_pic' => $productData->goods_pic,
            ];
        }
        $allotOrdersModel = new AllotOrders;
        $productsAll = $allotOrdersModel->getOutboundOrderProducts($warehouseData['warehouse_id'], $warehouseData['warehouse_fictitious_id'], $productsAll);
        if (empty($productsAll) && count($productsAll) < 1) {
            return false;
        } else {
            if ($productsAll[0]['number'] < (int)$list[0]['number']) {
                return false;
            }
            $warehouseStockModel = new warehouseStockModel;
            // 查询实时库存
            foreach ($productsAll as $key => $value) {
                # code...
                $num = $warehouseStockModel->where(
                    [
                        'goods_code' => $value['goods_code'],
                        'batch_no' => $value['batch_no'],
                        'entity_warehouse_id' => $warehouseData['warehouse_id'],
                        'virtual_warehouse_id' => $warehouseData['warehouse_fictitious_id']
                    ],
                )
                    ->sum('number');
                if ((int)$num < (int)$value['number']) {
                    return false;
                }
            }
            $OutboundOrders = new OutboundOrders;
            $OutboundOrders->startTrans();
            // 审核通过 生成出库单
            $orderData = [
                'entity_warehouse_id' => $warehouseData['warehouse_id'],
                'virtual_warehouse_id' => $warehouseData['warehouse_fictitious_id'],
                'source' => 'sales', // 销售
                'audit_status' => 2,   //fab出库单默认已审核
                'outbound_status' => 1, //fab出库单默认已出库
                'outbound_time' => date('Y-m-d H:i:s'),
                'created_by' => $data['creator_id'],
                'products' => $productsAll,
            ];

            $idOutboundOrders = $OutboundOrders->createOutOrder($orderData);
            // 变更库存
            foreach ($productsAll as $key => $product) {
                $warehouseStockModel->reduceStock(
                    $warehouseData['warehouse_id'],
                    $warehouseData['warehouse_fictitious_id'],
                    $product['goods_code'],
                    $product['batch_no'],
                    $product['number'],
                    $product['type'],
                    'orderOther',
                    $idOutboundOrders ?? $row['order_record_id'],
                    $row['order_record_id'] ?? 0
                );
                $list[$key]['product'] = $product;
            }
            // 出库单关联商品保存
            $OutboundOrderProducts = new OutboundOrderProducts;
            $OutboundOrderProducts->saveAll($productsAll);


            // 修改原始订单出库状态
            $this->updateBy($row['order_record_id'], ['is_delivery' => 1]);
            $OutboundOrders->commit();
            return true;
        }
    }
    /**
     * 订单导出参数
     */
    public function exportField()
    {
        return [
            [
                'title' => '订单ID',
                'filed' => 'order_ids',
            ],
            [
                'title' => '订单编号',
                'filed' => 'order_no',
            ],
            [
                'title' => '平台订单编号',
                'filed' => 'platform_no',
            ],
            [
                'title' => '平台名称',
                'filed' => 'platform',
            ],
            [
                'title' => '店铺名称',
                'filed' => 'shop_name',
            ],
            [
                'title' => '商品类别',
                'filed' => 'category_names',
            ],
            [
                'title' => '商品名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '系统SKU',
                'filed' => 'sku',
            ],
            [
                'title' => '平台SKU',
                'filed' => 'goods_code',
            ],
            [
                'title' => '总个数',
                'filed' => 'quantity_purchased',
            ],
            [
                'title' => '币别',
                'filed' => 'transaction_price_currencyid',
            ],
            [
                'title' => '总金额（含税）',
                'filed' => 'total_amount',
            ],
            [
                'title' => '税费',
                'filed' => 'tax_amount_value_all',
            ],
            [
                'title' => '订单状态',
                'filed' => 'status_text',
            ],
            [
                'title' => '所属公司',
                'filed' => 'company_name',
            ],
            [
                'title' => '买家姓名',
                'filed' => 'address_name',
            ],
            [
                'title' => '买家邮箱',
                'filed' => 'buyer_email',
            ],
            [
                'title' => '电话号码',
                'filed' => 'address_phone',
            ],
            [
                'title' => '邮编',
                'filed' => 'address_postalcode',
            ],
            [
                'title' => '国家',
                'filed' => 'address_country',
            ],
            [
                'title' => '州',
                'filed' => 'address_stateorprovince',
            ],
            [
                'title' => '城市',
                'filed' => 'address_cityname',
            ],
            [
                'title' => '街道',
                'filed' => 'address_street1',
            ],
            [
                'title' => '订单生成时间',
                'filed' => 'created_at',
            ],
            [
                'title' => '订单拉取时间',
                'filed' => 'get_at',
            ],
            [
                'title' => '发货时间',
                'filed' => 'shipped_at',
            ]
        ];
    }
    /**
     * 提取字符串中的数字
     */
    public function findNum($str = '')
    {
        $str = trim($str);
        if (empty($str)) {
            return '';
        }
        $temp = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
        $result = '';
        for ($i = 0; $i < strlen($str); $i++) {
            if (in_array($str[$i], $temp)) {
                $result .= $str[$i];
            }
        }
        return $result;
    }
}
