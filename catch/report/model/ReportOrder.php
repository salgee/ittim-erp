<?php

namespace catchAdmin\report\model;

use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Product;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseStockLogs;
use catcher\base\CatchModel as Model;
use catchAdmin\order\model\OrderRecords as orderModel;
use catchAdmin\basics\model\Shop as shopModel;
use catchAdmin\product\model\Category as categoryModel;
use catchAdmin\report\model\search\ReportOrderSearch;
use catcher\Code;

class ReportOrder extends Model
{
    use ReportOrderSearch;

    // 表名
    public $name = 'report_order';
    // 数据库字段映射
    public $field = array(
        'id',
        // erp系统自动生成的编号
        'order_no',
        // 渠道拉取的编号
        'platform_no',
        // 订单商品发货对应的物流单号
        'shipping_code',
        // 订单发货对应的物流公司(非亚马逊物流)
        'shipping_company',
        // 订单上所属店铺
        'shop_basics_id',
        // 订单上来源平台ID
        'platform_id',
        // 订单上来源平台
        'platform_name',
        // 渠道SKU编码
        'platform_sku',
        // ERP系统中的SKU编码
        'product_sku',
        // ERP系统中的SKU中文名称
        'product_name',
        // 商品分类ID
        'product_category_id',
        // 订单中该商品数量
        'quantity',
        // 订单中该商品的销售金额
        'price_amount',
        // 订单中该商品的税费
        'tax_amount',
        // 采购基准价
        'purchase_amount',
        // 海运费
        'freight_fee',
        // 关税
        'tariff_fee',
        // 订单处理费
        'order_operation_fee',
        // 快递费
        'express_fee',
        // 快递增值附加费
        'express_surcharge_fee',
        // 仓储费
        'storage_fee',
        // 备注
        'remark',
        // 订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)
        'order_type',
        // 采购价单位usd,rmb
        'purchase_amount_currencyid',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新人
        'updated_id',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    /**
     * 返回列表数据
     * @param $type
     * @param $action
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getOrderList($type, $action = 'list')
    {
        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $order = $this->where($whereOr)->catchSearch();
        // 排序不查询的字段
        $field = 'id, platform_id, creator_id, created_at, updated_at, deleted_at, remark, ';
        switch ($type) {
            case 'all':
            default:
                $order->catchLeftJoin(ReportOrderAfterSale::class, 'report_order_id', 'id', ['type,amount']);
                break;
            case 'fbm':
                $field .= 'order_type';
                // 不查询FBA订单
                $order->where('order_type', '<>', 5)
                    ->catchLeftJoin(ReportOrderAfterSale::class, 'report_order_id', 'id', ['type,amount']);
                break;
            case 'fba':
                $field .= 'shipping_code, shipping_company, order_type, express_fee, express_surcharge_fee';
                // 只查询FBA订单
                $order->where('order_type', '=', 5);
                $order->order('order_no', 'desc');
                break;
        }
        $order = $order->catchLeftJoin(shopModel::class, 'id', 'shop_basics_id', ['shop_name'])
            ->catchLeftJoin(
                categoryModel::class,
                'id',
                'product_category_id',
                ['parent_name as category_parent_name', 'name as category_name']
            )
            ->withoutField($field)
            ->field([
                $this->aliasField('created_at'),
                $this->aliasField('id'),
                $this->aliasField('remark')
            ]);
        if ($action == 'list') { //列表
            // 遍历数据添加一级商品分类
            $order = $order->order('id', 'desc')->paginate()->each(function (&$item) {
                //                $categoryData = categoryModel::where('id', $item->product_category_id)->find();
                $item['category_name'] = $item['category_parent_name'] . '-' . $item['category_name'];
            });
        } elseif ($action == 'export') { //导出
            $order = $order->order('id', 'desc')->select();
        }

        return $order;
    }

    /**
     * 返回销售报表列表数据
     * @param $action
     * @throws \think\db\exception\DbException
     */
    public function getSaleOrderList($action = 'list')
    {
        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $where = [['o.order_type', 'in', '0,4,5'], ['o.status', 'in', '1,2,3,4,5']];
        $params = request()->param();

        if ($prowerData['is_company']) {
            $res = [
                'code'    => Code::SUCCESS,
                'message' => 'success',
                'count'   => 0,
                'current' => 1,
                'limit'   => 100,
                'data'    => []
            ];
            return json($res);
        }
        // $query = OrderItemRecords::alias('oi')->leftJoin('order_records o', 'o.id=oi.order_record_id')
        //     ->field('o.id, shop_basics_id, platform,oi.goods_id, oi.name as name_ch,  oi.goods_code, o.order_type, transaction_price_currencyid as currency, transaction_price_value AS price, sum(quantity_purchased) as sales_numer, transaction_price_value* sum(quantity_purchased) as sales_amount')
        //     ->where($whereOr)
        //     ->where($where)
        //     ->where('o.id', '>', 0)
        //     ->where('oi.type', 0)
        // ->where('oi.goods_id', '>', 0);
        //     // ->where('o.order_type', '<>', 1);
        $query = OrderRecords::alias('o')
            ->leftJoin('order_item_records oi', 'o.id=oi.order_record_id')
            ->leftJoin('product p', 'p.id = oi.goods_id')
            ->leftJoin('shop_basics s', 's.id = o.shop_basics_id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->field('o.id, shop_basics_id, platform,oi.goods_id, p.name_ch, p.category_id, 
                p.CODE AS goods_code, o.order_type, transaction_price_currencyid as currency, 
                c.parent_name as category_parent_name, c.name as category_name,
                s.shop_name, transaction_price_value AS price, sum(quantity_purchased) as sales_numer, 
                sum(transaction_price_value*quantity_purchased) as sales_amount')
            ->where($whereOr)
            ->where($where)
            ->where('o.id', '>', 0)
            ->where('o.status', '<>', 6)
            ->where('oi.type', 0)
            ->where('abnormal', 0);

        if (isset($params['platform']) && $params['platform']) {
            $query->whereLike('o.platform', $params['platform']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $ids = Product::whereLike('name_ch', $params['name_ch'])->column('id');
            $query->whereIn('oi.goods_id', $ids);
        }

        if (isset($params['sku']) && $params['sku']) {
            // $query->whereLike('oi.goods_code', $params['sku']);
            $ids = Product::where('code', $params['sku'])->column('id');
            $query->whereIn('oi.goods_id', $ids);
        }


        if (isset($params['shop_ids']) && $params['shop_ids']) {
            // $shopId = Shop::whereLike('shop_name', $params['shop'])->value('id');
            $query->whereIn('o.shop_basics_id', $params['shop_ids']);
        }

        if (isset($params['order_type']) && $params['order_type']) {
            if ($params['order_type'] == 'FBA') {
                $query->where('o.order_type', 5);
            } else {
                $query->where('o.order_type', '<>', 5);
            }
        }

        if (isset($params['start_at']) && $params['start_at']) {
            $query->whereTime('o.created_at', '>=', strtotime($params['start_at']));
        }

        if (isset($params['end_at']) && $params['end_at']) {
            $query->whereTime('o.created_at', '<=', strtotime($params['end_at']));
        }
        $query = $query->group('oi.goods_code, oi.goods_id, o.shop_basics_id, o.order_type');

        $list = [];
        if ($action == 'list') {
            $countQuery = clone $query;
            $salesAmount = $countQuery->sum("quantity_purchased*transaction_price_value");
            $salesNumber = $countQuery->sum('quantity_purchased');
            $list = $query->paginate();
        }elseif ($action == 'export'){
            $list = $query->select()->toArray();
        }
        $goods_code = array_column(is_object($list) ? $list->items() : $list, 'goods_code');
        $goods_id = array_column(is_object($list) ? $list->items() : $list, 'goods_id');
        $amount_number = $this->preMonthSalesAmountANDNumber($goods_id, $params['start_at'], $params['end_at']);
        $amount_number = array_column($amount_number, NULL, 'goods_id');
        $t_s = $this->turnoverRateStockTransfer($goods_code, $params['start_at'], $params['end_at']);
        foreach ($list as &$val) {
            $val['order_type'] =  $val['order_type'] == 5 ? 'FBA' : 'FBM';
            $val['pre_month_sales_numer'] = $amount_number[$val['goods_id']]['number'] ?? 0;
            $val['pre_month_sales_amount'] = $amount_number[$val['goods_id']]['amount'] ?? 0;
            $val['pre_month_growth_rate'] = $this->preMonthGrowthRate($val['sales_amount'], $val['pre_month_sales_amount']);
            $rateStock = $this->rateStock($val['sales_numer'],
                $t_s['startStock'][$val['goods_code']]['number'] ?? 0,
                $t_s['endStock'][$val['goods_code']]['number'] ?? 0,
                $t_s['purchaseNumber'][$val['goods_code']]?? 0 );
            $val['turnover_rate'] =  $rateStock['turnover_rate'];
            $val['stock_transfer'] =  $rateStock['stock_transfer'];
            $val['category_name'] = $val['category_parent_name'] . '-' . $val['category_name'];
            $val['price'] = $val['sales_numer'] > 0 ? round($val['sales_amount'] / $val['sales_numer'], 4) : 0;
        }

        if ($action == 'list') {
            $res = [
                'code'    => Code::SUCCESS,
                'message' => 'success',
                'count'   => $list->total(),
                'current' => $list->currentPage(),
                'limit'   => $list->listRows(),
                'data'    => $list->getCollection(),
                'salesAmount' => $salesAmount, 'salesNumber' => $salesNumber
            ];
            return json($res);
        }elseif ($action == 'export'){
            return $list;
        }

    }

    /**
     * 上月销售总金额/总销量
     *
     * @param [type] $goodsId
     * @param [type] $startAt
     * @param [type] $endAt
     * @return array
     */
    public function preMonthSalesAmountANDNumber($goodsId, $startAt, $endAt)
    {
        $query = OrderItemRecords::where([
            ['goods_id', 'in',  implode(',', $goodsId)]
        ])
            ->field('goods_id, sum(transaction_price_value * quantity_purchased) as amount, sum(quantity_purchased) as number');
        if ($startAt) {
            $query->whereTime('created_at', '>=', strtotime("$startAt -1 month"));
        }

        if ($endAt) {
            $query->whereTime('created_at', '<=', strtotime("$endAt -1 month"));
        }
        $query->group('goods_id');
        return $query->select()->toArray();
    }

    /**
     *上月同期增长率
     *
     * @param  $salesAmount
     * @param  $preSalesAmount
     * @return void
     */
    public function preMonthGrowthRate($salesAmount, $preSalesAmount)
    {
        $preSalesAmountNew = $preSalesAmount > 0 ? $preSalesAmount : 1;
        // return ($salesAmount - $preSalesAmount) / $preSalesAmount .'%';
        return bcdiv((bcsub($salesAmount, $preSalesAmount, 4)), $preSalesAmountNew, 2) . '%';
    }

    /**
     * 动销率/库转(基础数据查询)
     *
     * @param  $goodsCode
     * @param  $startAt
     * @param  $endAt
     * @return array
     */
    public function turnoverRateStockTransfer($goodsCode, $startAt, $endAt)
    {
        /*
            1、时间段为销售数量的采购成本=销售数量*采购单价
            2、期初/期末的库存金额=库存数量*采购价
            3、期初指搜索时间开始时间的零点
            4、期末指搜索时间结束时间的23点59分59秒
        */
        $startAtNew = date("Y-m-d", strtotime($startAt));
        $endAtNew = date("Y-m-d", strtotime("+1 day", strtotime($endAt)));
        //期初库存数量
        $startStock = WarehouseStockLogs::where([
            ['goods_code', 'in', implode(',', $goodsCode)]
        ])
            ->field('goods_code, sum(number) as number')->where('log_date', $startAtNew)
            ->group('goods_code')->select()->toArray();
        //期末库存数量
        $endStock = WarehouseStockLogs::where([
            ['goods_code', 'in', implode(',', $goodsCode)]
        ])
            ->field('goods_code, sum(number) as number')->where('log_date', $endAtNew)
            ->group('goods_code')->select()->toArray();

        $startStock = array_column($startStock, NULL, 'goods_code');
        $endStock = array_column($endStock, NULL, 'goods_code');
        //时间段内的采购数量
        $purchaseNumber = WarehouseOrderProducts::alias('op')
            ->leftJoin('warehouse_orders o', 'o.id=op.warehouse_order_id')
            ->leftJoin('product p', 'p.code = op.goods_code')
            ->field('op.goods_code, p.purchase_price_rmb, p.purchase_price_rmb, p.purchase_price_usd, sum(op.number) as number')
            ->where([
                ['op.goods_code', 'in', implode(',', $goodsCode)]
            ])
            ->where('o.warehousing_status', 1) // 已入库
            ->where('o.created_at', '>=', strtotime($startAt))
            ->where('o.created_at', '<=', strtotime($endAt))
            ->group('goods_code')
            ->select()->toArray();

        $purchaseNumber = array_column($purchaseNumber, NULL, 'goods_code');
        return [
            'startStock' => $startStock,
            'endStock' => $endStock,
            'purchaseNumber' => $purchaseNumber
        ];
    }

    /**
     * 动销率/库转(数据查询计算)
     *
     * @param  $goodsCode
     * @param  $startAt
     * @param  $endAt
     * @return array
     */
    public function rateStock($salesNumber, $startStock, $endStock, $purchaseNumber)
    {
        //采购单价
        $price =  0;
        if ($purchaseNumber) {
            $price = $purchaseNumber['purchase_price_rmb'] > 0 ? $purchaseNumber['purchase_price_rmb'] : $purchaseNumber['purchase_price_usd'];
        }

        //采购成本=销售数量*采购单价
        $purchaseAmount =  bcmul($purchaseNumber['number'] ?? 0, $price, 4);
        $startStockAmount = bcmul($startStock, $price, 4);
        $endStockAmount = bcmul($endStock, $price, 4);

        //库转=时间段内销售数量的采购成本/（(期初的库存金额+期末的库存金额）/2))
        $stock_transfer = bcdiv(bcadd($startStockAmount, $endStockAmount, 4), 2, 2)  == '0.00' ? 1 : bcdiv(($startStockAmount + $endStockAmount), 2, 2);
        $stock_transfer = bcdiv($purchaseAmount, $stock_transfer, 2);

        $turnover_rate = bcadd($startStock, $purchaseNumber['number'] ?? 0, 0) == '0' ? 1 : bcadd($startStock, $purchaseNumber['number'] ?? 0, 0);
        $turnover_rate = bcdiv($salesNumber, $turnover_rate, 4) . "%";

        return ['turnover_rate' => $turnover_rate, 'stock_transfer' => $stock_transfer];
    }

    /**
     * 生成报表订单（发货后）
     * @param $orderNo
     * @param $orderId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveOrder($orderNo, $orderId = 0)
    {
        $where = ['o.order_no' => $orderNo];
        // ID不为空则使用ID查询订单
        if (!empty($orderId)) {
            $where = ['o.id' => $orderId];
        }
        // 查询订单基本信息
        if ($order = orderModel::where($where)
            ->alias('o')
            ->field('o.order_no, o.platform_no, o.shop_basics_id, o.platform as platform_name, o.platform_id, 
            o.order_type, o.paid_at, o.remarks as remark, oi.sku as platform_sku, oi.quantity_purchased as quantity, p.type, 
            oi.transaction_price_value as price_amount, oi.tax_amount_value as tax_amount, p.code as product_sku,
            oi.goods_price, oi.goods_tax_amount, oi.transaction_price_currencyid, o.created_at, 
            p.name_ch as product_name, p.category_id as product_category_id, p.purchase_price_usd, pp.purchase_price_rmb,
            pp.purchase_price_usd, pp.ocean_freight as freight_fee, pp.all_tariff as tariff_fee, pp.order_operation_fee, pp.storage_fee,
            p.benchmark_price, p.purchase_price_usd, pp.purchase_benchmark_price')
            ->leftJoin('order_item_records oi', 'oi.order_record_id = o.id')
            ->leftJoin('product p', 'p.id = oi.goods_id')
            ->leftJoin('product_price pp', 'pp.product_id = p.id and pp.is_status = 1')
            ->find()
        ) {
            $order = \GuzzleHttp\json_decode($order, true);
            // 客户订单不入库统计
            if ($order['order_type'] == Code::ORDER_TYPE_CUSTOMER) {
                return false;
            }
            // 客户商品
            if ($order['type'] == 1) {
                // 使用申报单价*数量,单位固定USD
                $order['purchase_amount'] = $order['purchase_price_usd'] * $order['quantity'];
                $order['purchase_amount_currencyid'] = 'USD';
            } else {
                // 内部商品基准价
                if (strtolower($order['transaction_price_currencyid']) != 'usd') {
                    //商品对应价格中基准价*数量
                    $order['purchase_amount'] = !empty($order['purchase_benchmark_price']) ? $order['purchase_benchmark_price'] : 0;
                    $order['purchase_amount_currencyid'] = 'RMB';
                }
                if (strtolower($order['transaction_price_currencyid']) != 'rmb') {
                    $order['purchase_amount'] = !empty($order['purchase_benchmark_price']) ? $order['purchase_benchmark_price'] : 0;
                    $order['purchase_amount_currencyid'] = 'USD';
                }
            }
            //调用基准价里的海运费*数量
            $order['freight_fee'] = $order['freight_fee'] * $order['quantity'];
            //调用基准价里的总关税*数量
            $order['tariff_fee'] = $order['tariff_fee'] * $order['quantity'];
            //基准价里的订单处理费*数量
            $order['order_operation_fee'] = $order['order_operation_fee'] * $order['quantity'];
            $order['created_at'] = $order['paid_at'] ?? $order['created_at'];
            return $this->storeBy($order);
        }
    }
}
