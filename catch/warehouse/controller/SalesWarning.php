<?php


namespace catchAdmin\warehouse\controller;


use Carbon\Carbon;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Category;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\product\model\ProductPrice;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\model\PurchaseContracts;
use catchAdmin\supply\model\SubOrders;
use catchAdmin\supply\model\Supply;
use catchAdmin\supply\model\TranshipmentOrders;
use catchAdmin\warehouse\excel\UnsalableExport;
use catchAdmin\warehouse\model\ReplenishmentWarning;
use catchAdmin\warehouse\model\SalesForecast;
use catchAdmin\warehouse\model\SalesForecastProducts;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchAuth;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;

class SalesWarning extends CatchController
{

    protected $salseForecastModel;
    protected $salesForecastProductsModel;

    public function __construct(
        SalesForecast $salesForecast,
        SalesForecastProducts $salesForecastProducts
    ) {
        $this->salseForecastModel         = $salesForecast;
        $this->salesForecastProductsModel = $salesForecastProducts;
    }


    /**
     * 补货
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function replenishment(CatchRequest $request)
    {
        $params = $request->param();

        $query  = ReplenishmentWarning::field('replenishment_warning.id as id, code, name_ch, name_en,salse, stock, trans_stock AS transStock, check_date');

        $users = new Users();
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            //如果不是管理员 查询账号绑定店铺
            if ($prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->leftJoin('warehouse_stock', 'warehouse_stock.goods_code= replenishment_warning.code')
                        ->whereIn('warehouse_stock.virtual_warehouse_id', $warehouseIds)->group('code');
                }
            }
        }


        if (isset($params['code']) && $params['code']) {
            $query->whereLike('code', $params['code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $query->whereLike('name_ch', $params['name_ch']);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $query->whereLike('name_en', $params['name_en']);
        }

        // $warehouseId = '';
        // if (isset($params['warehouse']) && $params['warehouse']) {
        //     $warehouseId = Warehouses::whereLike('name', $params['warehouse'])->value('id');

        // }

        $products = $query->order('check_date', 'desc')->paginate();
        return CatchResponse::paginate($products);
    }

    /**
     * 库存预警（补货）导出
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return void
     */
    public function replenishmentExport(CatchRequest $request)
    {
        $params = $request->param();

        $query  = ReplenishmentWarning::field('replenishment_warning.id as id, code, name_ch, name_en,salse, stock, trans_stock AS transStock, check_date');

        $users = new Users();
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            //如果不是管理员 查询账号绑定店铺
            if ($prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->leftJoin('warehouse_stock', 'warehouse_stock.goods_code= replenishment_warning.code')
                        ->whereIn('warehouse_stock.virtual_warehouse_id', $warehouseIds)->group('code');
                }
            }
        }

        if (isset($params['code']) && $params['code']) {
            $query->whereLike('code', $params['code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $query->whereLike('name_ch', $params['name_ch']);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $query->whereLike('name_en', $params['name_en']);
        }

        // $warehouseId = '';
        // if (isset($params['warehouse']) && $params['warehouse']) {
        //     $warehouseId = Warehouses::whereLike('name', $params['warehouse'])->value('id');
        //     $query->leftJoin('warehouse_stock', 'warehouse_stock.goods_code= replenishment_warning.code')
        //         ->where('warehouse_stock.entity_warehouse_id', $warehouseId)->group('code');
        // }

        $res = $query->order('check_date', 'desc')->select()->toArray();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }


        if (isset($params['exportField'])) {
            $exportField = $params['exportField'];
        } else {
            $exportField = $this->replenishmentExportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '库存预警(补货)');
        return  CatchResponse::success($url);
    }

    public  function  replenishmentExportField()
    {
        return [

            [
                'title' => '系统sku',
                'filed' => 'code',
            ],
            [
                'title' => '中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '英文名称',
                'filed' => 'name_en',
            ],
            [
                'title' => '即时库存',
                'filed' => 'stock',
            ],
            [
                'title' => '在途库存',
                'filed' => 'transStock',
            ],
            [
                'title' => '后三月销售之和',
                'filed' => 'salse',
            ],
            [
                'title' => '预警',
                'filed' => 'check_date',
            ],
        ];
    }

    /**
     * 库存预警(滞销)
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return void
     */
    public function unsalable(CatchRequest $request, CatchAuth $auth)
    {
        $params = $request->param();

        //获取仓库内所有商品 按仓库分组
        $query = WarehouseStock::alias('ws')->leftJoin('product p', 'p.code=ws.goods_code')
            ->field('p.id, category_id, purchase_price_rmb, purchase_price_usd, ws.entity_warehouse_id,ws.virtual_warehouse_id, ws.batch_no, sum(ws.number) as number , ws.goods_code,ws.goods_type')
            ->where('p.deleted_at', 0)
            ->where('p.type', 0)
            ->group('ws.goods_code,  ws.virtual_warehouse_id');


        $users = new Users();

        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            //如果不是管理员 查询账号绑定店铺
            if ($prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->whereIn('ws.virtual_warehouse_id', $warehouseIds);
                }
            }
        }
        if (isset($params['code']) && $params['code']) {
            $query->whereLike('p.code', $params['code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $query->whereLike('p.name_ch', $params['name_ch']);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $query->whereLike('p.name_en', $params['name_en']);
        }


        // if (isset($params['warehouse']) && $params['warehouse']) {

        //     if ($warehouseId = Warehouses::whereLike('name', $params['warehouse'])->value('id')) {
        //         $query->whereRaw(" (ws.virtual_warehouse_id = $warehouseId or ws.entity_warehouse_id = $warehouseId )");
        //     } else {
        //         $query->whereRaw(" (ws.virtual_warehouse_id = 0 or ws.entity_warehouse_id = 0 )");
        //     }
        // }
        if (isset($params['warehouse']) && $params['warehouse']) {
            $warehouseId = Warehouses::where('name', $params['warehouse'])->value('id');
            $query->where('ws.virtual_warehouse_id', $warehouseId);
        }

        if (isset($params['warehouseId']) && $params['warehouseId']) {
            $query->where('ws.entity_warehouse_id', $params['warehouseId']);
        }


        $products = $query->paginate();
        $accountAge = config('const.account_age');
        // var_dump('$product', $products->toArray());
        // exit;
        foreach ($products as &$product) {
            $category = Category::where('id', $product->category_id)->find();
            $fisrtCategoryName = Category::where('id', $category->parent_id)->value('name');
            $product->category_name = $fisrtCategoryName . "-" . $category->getAttr('name');
            $product->volume_item = 0;
            if (!empty($volume_item = ProductInfo::where('product_id', $product->id)->value('volume'))) {
                $product->volume_item = $volume_item;
            }
            //总体积
            $product->volume = bcmul($product->volume_item, $product->number ?? 0, 5);

            //计算海运费及总关税
            $product->shippfee = 0;
            $product->tax_fee = 0;

            $productPrice = ProductPrice::where('product_id', $product->id)->find();
            if ($productPrice) {
                $product->shippfee = $product->number *  $productPrice->ocean_freight;
                $product->tax_fee =   $product->number *  $productPrice->all_tariff;
            }


            $price = $product->purchase_price_rmb > 0 ?  $product->purchase_price_rmb :  $product->purchase_price_usd;
            $product->amount = $price * $product->number;

            //供应商
            $p = Product::where('code', $product->goods_code)->find();
            $product->supply = $p->supply->getAttr('name');
            //获取商品所有库存批次
            $batchNo = WarehouseStock::where([
                'goods_code' => $product->goods_code,
                'virtual_warehouse_id' => $product->virtual_warehouse_id,
            ])
                ->where('number', '>', 0)
                ->select();
                // ->group('batch_no')
                // ->column('batch_no, goods_code, sum(number) as total');

            //计算账龄
            foreach ($accountAge as $key => $val) {
                $product->$key = 0;
                foreach ($batchNo as $v) {
                    $wareghouseTime =  WarehouseOrderProducts::alias('wop')->leftJoin(
                        'warehouse_orders wo',
                        'wo.id=wop.warehouse_order_id'
                    )
                        ->where([
                            'wop.batch_no' => $v['batch_no'],
                            'wo.warehousing_status' => 1,
                            'wop.goods_code' => $v['goods_code']
                        ])
                        ->whereTime('wo.warehousing_time', '>', '2022-01-01')
                        ->order('wo.id', 'asc')
                        ->value('warehousing_time');
                    if (!empty($wareghouseTime)) {
                        $warehousingTime = $wareghouseTime;
                    } else {
                        $warehousingTime = '2022-01-01';
                    }
                    $diffDays =  Carbon::parse($warehousingTime)->diffInDays();
                    if ($diffDays >= $val['start'] && $diffDays <= $val['end']) {
                        $product->$key += $v['number'];
                    }
                }
            }
        }
        return CatchResponse::paginate($products);
    }

    /**
     * 库存预警（滞销）导出
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return void
     */
    public  function  unsalableExport(CatchRequest $request, CatchAuth $auth)
    {


        $params = $request->param();
        ini_set('memory_limit', '1024M');
        //获取仓库内所有商品 按仓库分组
        $query = WarehouseStock::alias('ws')->leftJoin('product p', 'p.code=ws.goods_code')
            ->field('p.id, category_id, purchase_price_rmb, purchase_price_usd, ws.entity_warehouse_id,ws.virtual_warehouse_id, ws.batch_no, sum(ws.number) as number , ws.goods_code,ws.goods_type')
            ->where('p.deleted_at', 0)
            ->where('p.type', 0)
            ->group('ws.goods_code,  ws.virtual_warehouse_id');


        $users = new Users();

        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            //如果不是管理员 查询账号绑定店铺
            if ($prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->whereIn('ws.virtual_warehouse_id', $warehouseIds);
                }
            }
        }
        if (isset($params['code']) && $params['code']) {
            $query->whereLike('p.code', $params['code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $query->whereLike('p.name_ch', $params['name_ch']);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $query->whereLike('p.name_en', $params['name_en']);
        }


        // if (isset($params['warehouse']) && $params['warehouse']) {

        //     if ($warehouseId = Warehouses::whereLike('name', $params['warehouse'])->value('id')) {
        //         $query->whereRaw(" (ws.virtual_warehouse_id = $warehouseId or ws.entity_warehouse_id = $warehouseId )");
        //     } else {
        //         $query->whereRaw(" (ws.virtual_warehouse_id = 0 or ws.entity_warehouse_id = 0 )");
        //     }
        // }
        if (isset($params['warehouse']) && $params['warehouse']) {
            $warehouseId = Warehouses::where('name', $params['warehouse'])->value('id');
            $query->where('ws.virtual_warehouse_id', $warehouseId);
        }

        if (isset($params['warehouseId']) && $params['warehouseId']) {
            $query->where('ws.entity_warehouse_id', $params['warehouseId']);
        }


        $products = $query->select();

        $accountAge = config('const.account_age');
        foreach ($products as &$product) {

            // $category = Category::where('id', $product->category_id)->find();
            // $fisrtCategoryName = Category::where('id', $category->parent_id)->value('name');
            // $product->category_name = $fisrtCategoryName . "-" . $category->getAttr('name');
            $product->category_name = '-';

            //总体积
            // $product->volume = ProductInfo::where('product_id', $product->id)->value('volume_AS') ?? 0 * $product->number ?? 0;
            $product->volume_item = 0;
            if (!empty($volume_item = ProductInfo::where('product_id', $product->id)->value('volume'))) {
                $product->volume_item = $volume_item;
            }
            //总体积
            $product->volume = bcmul($product->volume_item, $product->number ?? 0, 5);

            //计算海运费及总关税
            $product->shippfee = 0;
            $product->tax_fee = 0;

            $productPrice = ProductPrice::where('product_id', $product->id)->find();
            if ($productPrice) {
                $product->shippfee = $product->number *  $productPrice->ocean_freight;
                $product->tax_fee =   $product->number *  $productPrice->all_tariff;
            }


            $price = $product->purchase_price_rmb > 0 ?  $product->purchase_price_rmb :  $product->purchase_price_usd;
            $product->amount = $price * $product->number;

            //供应商
            $product->supply =  Supply::where('id', $product->supplier_id)->value('name') ?? '';
            //供应商
            // $p = Product::where('code', $product->goods_code)->find();
            // $product->supply = $p->supply->getAttr('name');

            // $product->supply = '-';
            //获取商品所有库存批次
            $batchNo = WarehouseStock::where([
                'goods_code' => $product->goods_code,
                'virtual_warehouse_id' => $product->virtual_warehouse_id,
            ])
                ->where('number', '>', 0)
                ->select();
                // ->group('batch_no')
                // ->column('batch_no, goods_code, sum(number) as total');
            //计算账龄
            foreach ($accountAge as $key => $val) {
                $product->$key = 0;
                foreach ($batchNo as $v) {
                    $wareghouseTime =  WarehouseOrderProducts::alias('wop')->leftJoin(
                        'warehouse_orders wo',
                        'wo.id=wop.warehouse_order_id'
                    )
                        ->where([
                            'wop.batch_no' => $v['batch_no'],
                            'wo.warehousing_status' => 1,
                            'wop.goods_code' => $v['goods_code']
                        ])
                        ->whereTime('wo.warehousing_time', '>', '2022-01-01')
                        ->order('wo.id', 'asc')
                        ->value('warehousing_time');
                    if (!empty($wareghouseTime)) {
                        $warehousingTime = $wareghouseTime;
                    } else {
                        $warehousingTime = '2022-01-01';
                    }
                    $diffDays =  Carbon::parse($warehousingTime)->diffInDays();
                    if ($diffDays >= $val['start'] && $diffDays <= $val['end']) {
                        $product->$key += $v['number'];
                    }
                }
            }
        }
        $excel = new unsalableExport();
        $url = $excel->export($products);
        return  CatchResponse::success($url);
    }

    /**
     * 库存预警滞销详情
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return void
     */
    public function unsalableDetail(CatchRequest $request)
    {
        $code = $request->get('code', '');
        $vwId = $request->get('virtual_warehouse_id', '');

        if (!$code) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }

        if (!$vwId) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }

        //获取商品所有库存批次
        $stocks = WarehouseStock::where([
            'goods_code' => $code,
            'virtual_warehouse_id' => $vwId,
        ])
            ->where('number', '>', 0)
            ->group('batch_no')
            ->select();

        $data = [];
        if ($stocks->isEmpty()) {
            return CatchResponse::success($data);
        }

        foreach ($stocks as $stock) {

            $row['batch_no'] = $stock->batch_no;
            //查找批次入库时间
            $row['wareghouseTime']  =  WarehouseOrderProducts::alias('wop')
                ->leftJoin('warehouse_orders wo', 'wo.id=wop.warehouse_order_id')
                ->where([
                    'wop.batch_no' => $stock->batch_no,
                    'wo.warehousing_status' => 1,
                    'wop.goods_code' => $code,
                ])->whereTime('wo.warehousing_time', '>', '2022-01-01')
                ->order('wo.id', 'asc')->value('warehousing_time');
            
            if (!$row['wareghouseTime']) {
                $row['wareghouseTime'] = '2022-01-01';
            }
            //查找批次对应柜子号
            $contract = PurchaseContracts::where('batch_no', $stock->batch_no)->find();

            $row['cabinet_no'] = TranshipmentOrders::where('purchase_contract_id', $contract->id ?? 0)->column('cabinet_no');
            $product = $stock->product;

            $row['unit'] = $product->info->unit ?? ''; //计量单位
            $row['supply'] = $product->supply->getAttr('name') ?? ''; //供应商
            $row['number'] = $stock->number;
            $row['shipping_fee'] = $product->priceInfo->ocean_freight ?? 0 *  $stock->number;
            $row['tax_fee'] = $product->priceInfo->all_tariff ?? 0 * $stock->number;
            $row['currency'] = $product->purchase_price_rmb > 0 ? 'RMB' : 'USD';
            $row['price'] = $product->purchase_price_rmb > 0 ?  $product->purchase_price_rmb :  $product->purchase_price_usd;
            $row['amount'] = $row['price'] * $row['number'];
            //总体积
            // $row['volume']  = ProductInfo::where('product_id', $product->id)->value('volume_AS')  * $row['number'];
            $row['volume_item'] = 0;
            if (!empty($volume_item = ProductInfo::where('product_id', $product->id)->value('volume'))) {
                $row['volume_item'] = $volume_item;
            }
            //总体积
            $row['volume'] = bcmul($row['volume_item'], $row['number'] ?? 0, 5);
            //计算在库时长
            $row['days'] = Carbon::parse($row['wareghouseTime'])->diffInDays(); //入库时长
            $data[] = $row;
        }

        return CatchResponse::success($data);
    }


    /**
     * 库存预警滞销详情导出
     *
     * @param CatchRequest $request
     * @return void
     */
    public function unsalableDetailExport(CatchRequest $request)
    {
        $params = $request->post();

        $code = $params['code'] ?? '';
        $vwId = $params['virtual_warehouse_id'] ?? '';

        if (!$code) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }

        if (!$vwId) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }

        //获取商品所有库存批次
        $stocks = WarehouseStock::where([
            'goods_code' => $code,
            'virtual_warehouse_id' => $vwId,
        ])
            ->where('number', '>', 0)
            ->group('batch_no')
            ->select();

        $data = [];
        if ($stocks->isEmpty()) {
            return CatchResponse::success($data);
        }

        foreach ($stocks as $stock) {

            $row['batch_no'] = $stock->batch_no;
            //查找批次入库时间
            $row['wareghouseTime']  =  WarehouseOrderProducts::alias('wop')
                ->leftJoin('warehouse_orders wo', 'wo.id=wop.warehouse_order_id')
                ->where([
                    'wop.batch_no' => $stock->batch_no,
                    'wo.warehousing_status' => 1,
                    'wop.goods_code' => $code,
                ])->whereTime('wo.warehousing_time', '>', '2022-01-01')
                ->order('wo.id', 'asc')->value('warehousing_time');
            if (!$row['wareghouseTime']) {
                $row['wareghouseTime'] = '2022-01-01';
            }

            //查找批次对应柜子号
            $contract = PurchaseContracts::where('batch_no', $stock->batch_no)->find();

            $row['cabinet_no'] = TranshipmentOrders::where('purchase_contract_id', $contract->id ?? 0)->column('cabinet_no');
            $product = $stock->product;

            $row['unit'] = $product->info->unit ?? ''; //计量单位
            $row['supply'] = $product->supply->getAttr('name') ?? ''; //供应商
            $row['number'] = $stock->number;
            $row['shipping_fee'] = $product->priceInfo->ocean_freight ?? 0 *  $stock->number;
            $row['tax_fee'] = $product->priceInfo->all_tariff ?? 0 * $stock->number;
            $row['currency'] = $product->purchase_price_rmb > 0 ? 'RMB' : 'USD';
            $row['price'] = $product->purchase_price_rmb > 0 ?  $product->purchase_price_rmb :  $product->purchase_price_usd;
            $row['amount'] = $row['price'] * $row['number'];
            //总体积
            $row['volume']  = ProductInfo::where('product_id', $product->id)->value('volume_AS')  * $row['number'];

            //计算在库时长
            $row['days'] = Carbon::parse($row['wareghouseTime'])->diffInDays(); //入库时长
            $data[] = $row;
        }

        if (empty($data)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        if (isset($params['exportField'])) {
            $exportField = $params['exportField'];
        } else {
            $exportField = $this->unsalableDetailExportField();
        }


        $excel = new CommonExport();
        $url = $excel->export($data, $exportField, '库存预警（滞销）详情');
        return  CatchResponse::success($url);
    }

    /**
     * 库存预警滞销详情导出字段
     *
     * @return void
     */
    public function unsalableDetailExportField()
    {
        return [

            [
                'title' => '批次号',
                'filed' => 'batch_no',
            ],
            [
                'title' => '入库时间',
                'filed' => 'wareghouseTime',
            ],
            [
                'title' => '柜子号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => '供应商',
                'filed' => 'supply',
            ],
            [
                'title' => '计量单位',
                'filed' => 'unit',
            ],
            [
                'title' => '海运费',
                'filed' => 'shipping_fee',
            ],
            [
                'title' => '总关税',
                'filed' => 'tax_fee',
            ],
            [
                'title' => '计价币种',
                'filed' => 'currency',
            ],
            [
                'title' => '体积',
                'filed' => 'volume',
            ],
            [
                'title' => '库存商品单价（FOB单价）',
                'filed' => 'price',
            ],
            [
                'title' => '库存金额（FOB金额）',
                'filed' => 'amount',
            ],
            [
                'title' => '即时库存',
                'filed' => 'number',
            ],
            [
                'title' => '库存时长',
                'filed' => 'days',
            ],
        ];
    }
}
