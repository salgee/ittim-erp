<?php


namespace catchAdmin\warehouse\controller;

use app\Request;
use catchAdmin\basics\excel\StockImport;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Category;
use catchAdmin\product\model\Product;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catchAdmin\warehouse\model\ViewWarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use catcher\exceptions\FailedException;
use think\facade\Db;
use catchAdmin\basics\model\Shop;

class Stock extends CatchController
{

    protected $warehouseStockModel;
    protected $warehouseOrderModel;
    protected $viewWarehouseStockModel;

    public function __construct(
        WarehouseStock $warehouseStock,
        WarehouseOrders $warehouseOrders,
        ViewWarehouseStock $viewWarehouseStock
    ) {
        $this->warehouseStockModel = $warehouseStock;
        $this->warehouseOrderModel = $warehouseOrders;
        $this->viewWarehouseStockModel = $viewWarehouseStock;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index(CatchRequest $request)
    {

        $query = $this->warehouseStockModel::alias('ws')
            ->leftJoin('product p', 'p.code = ws.goods_code and p.is_disable = 1')
            // ->where('p.is_disable', 1)
            ->field('ws.id, ws.goods_type, ws.goods_code, ws.entity_warehouse_id, ws.virtual_warehouse_id, sum(ws.number) as number')
            ->group('ws.goods_code, ws.virtual_warehouse_id');


        $users = new Users();

        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            //如果不是管理员 查询账号绑定店铺
            if ($prowerData['is_company']) { // 如果是客户
                if ($prowerData['company_id']) {
                    // 获取客户仓库
                    $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                    if (!empty($warehouseIds)) {
                        $query->whereIn('ws.virtual_warehouse_id', $warehouseIds);
                    } else {
                        $query->whereIn('ws.virtual_warehouse_id', '');
                    }
                }
            }
            if (!$prowerData['is_company'] && $prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->whereIn('ws.virtual_warehouse_id', $warehouseIds);
                } else {
                    $query->whereIn('ws.virtual_warehouse_id', '');
                }
            }
        }

        $params = $request->param();

        if (isset($params['goods_code']) && $params['goods_code']) {

            $query->whereLike('goods_code', $params['goods_code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $goodsCodes = Product::whereLike('name_ch', $params['name_ch'])->column('code');
            $query->whereIn('goods_code', $goodsCodes);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $goodsCodes = Product::whereLike('name_en', $params['name_en'])->column('code');
            $query->whereIn('goods_code', $goodsCodes);
        }

        if (isset($params['warehouse']) && $params['warehouse']) {
            $warehouse  = Warehouses::whereLike('name', $params['warehouse'])->column('id') ?? [];

            $ids = -1;
            if (!empty($warehouse)) {
                $ids = implode(",", $warehouse);
            }
            $query->whereRaw("entity_warehouse_id in ({$ids})  or virtual_warehouse_id in ({$ids}) ");
        }


        $res = $query
            ->paginate();
        //     ->fetchSql()->find(1);
        // var_dump($res, $prowerData);
        // exit;
        return CatchResponse::paginate($res);
    }


    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(Request $request)
    {

        $query = $this->viewWarehouseStockModel::alias('ws');

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
            if (!$prowerData['is_company'] && $prowerData['shop_ids']) {
                //根据绑定店铺查询绑定仓库
                $warehouseIds = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (!empty($warehouseIds)) {
                    $query->whereIn('ws.virtual_warehouse_id', $warehouseIds);
                } else {
                    $query->whereIn('ws.virtual_warehouse_id', '');
                }
            }
        }

        $params = $request->param();
        if (isset($params['goods_code']) && $params['goods_code']) {

            $query->whereLike('goods_code', $params['goods_code']);
        }

        if (isset($params['name_ch']) && $params['name_ch']) {
            $goodsCodes = Product::whereLike('name_ch', $params['name_ch'])->column('code');
            $query->whereIn('goods_code', $goodsCodes);
        }

        if (isset($params['name_en']) && $params['name_en']) {
            $goodsCodes = Product::whereLike('name_en', $params['name_en'])->column('code');
            $query->whereIn('goods_code', $goodsCodes);
        }

        if (isset($params['warehouse']) && $params['warehouse']) {
            $warehouse  = Warehouses::whereLike('name', $params['warehouse'])->column('id') ?? 0;
            $ids = implode(",", $warehouse);
            $query->whereRaw("entity_warehouse_id in({$ids})  or virtual_warehouse_id in({$ids}) ");
        }


        $res = $query->select();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        if (isset($params['exportField'])) {
            $exportField = $params['exportField'];
        } else {
            $exportField = $this->warehouseStockModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, 'WarehouseStock');
        return  CatchResponse::success($url);
    }



    /**
     * 库存导入
     *
     * @param Request $request
     * @param ZipCodeImport $import
     * @param \catcher\CatchUpload $upload
     * @return void
     */
    public function importStock(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $batchNo    = $this->warehouseOrderModel->createBatchNo();
        try {
            $file = $request->file();
            $data = $import->read($file['file']);

            $this->warehouseStockModel->startTrans();
            $dataObj = [];
            $rows = [];
            foreach ($data as $key => $value) {
                if ($value[1] >= 0) {
                    $row = [
                        'goods_code' => $value[0],
                        'entity_warehouse_id' => 232,
                        'virtual_warehouse_id' => 232,
                        'number' => $value[1],
                        'type' => 1,
                        'batch_no' => $batchNo
                    ];
                    $rows[] = $row;
                }
            }


            $this->warehouseStockModel->saveAll($rows);
            $this->warehouseStockModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->warehouseStockModel->rollback();
            throw new FailedException($e->getMessage());
        }
    }


    /**
     * 修改库存
     *
     * @param Request $request
     * @param StockImport $import
     * @param \catcher\CatchUpload $upload
     * @return void
     */
    public function changeStock(Request $request, StockImport $import, \catcher\CatchUpload $upload)
    {
        try {
            $allotOrdersModel = new AllotOrders();

            $file = $request->file();
            $data = $import->loadFile($file['file']);
            $this->warehouseStockModel->startTrans();
            $dataObj = [];
            $rows = [];

            foreach ($data as $key => $val) {
                if (count($val) == 1) {
                    continue;
                }

                $warehouses = [];
                //组装仓库数据
                foreach ($val[1] as $k => $v) {
                    $warehouse = Warehouses::where('name', $v)->find();
                    if ($warehouse) {
                        $warehouses[$k] = [
                            'name' => $v,
                            'entity_warehouse_id' => $warehouse->parent_id,
                            'virtual_warehouse_id' =>  $warehouse->id,
                        ];
                    }
                }
                unset($val[1]);
                //拼装库存扣减数据
                foreach ($val as  $p) {
                    foreach ($p as  $j => $s) {
                        if (is_numeric($s) && $s != 0) {
                            $product = [
                                [
                                    'goods_code' => $p[0],
                                    'number' => $s,
                                    'type' => 1
                                ]
                            ];
                            $products = [];
                            if (isset($warehouses[$j])) {
                                //计算出库批次
                                $products = $allotOrdersModel->getOutboundOrderProducts($warehouses[$j]['entity_warehouse_id'], $warehouses[$j]['virtual_warehouse_id'], $product);
                            }
                            if (!empty($products)) {

                                foreach ($products as $temp) {
                                    if ($temp['number'] < 0){
                                        // 增加库存
                                        $this->warehouseStockModel->increaseStock(
                                            $warehouses[$j]['entity_warehouse_id'],
                                            $warehouses[$j]['virtual_warehouse_id'],
                                            $temp['goods_code'],
                                            $temp['batch_no'],
                                            abs($temp['number']),
                                            $temp['type'],
                                            'manual'
                                        );
                                    }else {
                                        // 减少库存
                                        $this->warehouseStockModel->reduceStock(
                                            $warehouses[$j]['entity_warehouse_id'],
                                            $warehouses[$j]['virtual_warehouse_id'],
                                            $temp['goods_code'],
                                            $temp['batch_no'],
                                            $temp['number'],
                                            $temp['type'],
                                            'manual'
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->warehouseStockModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->warehouseStockModel->rollback();
            throw new FailedException($e->getLine() . "====" . $e->getMessage());
        }
    }

    /**
     * 修改库存(平库存)
     *
     * @param Request $request
     * @param StockImport $import
     * @param \catcher\CatchUpload $upload
     * @return void
     */
    public function initChangeStock(Request $request, StockImport $import, \catcher\CatchUpload $upload)
    {
        try {
            $allotOrdersModel = new AllotOrders();

            $file = $request->file();
            $data = $import->loadFile($file['file']);
            $this->warehouseStockModel->startTrans();
            $dataObj = [];
            $rows = [];

            foreach ($data as $key => $val) {
                if (count($val) == 1) {
                    continue;
                }

                $warehouses = [];
                //组装仓库数据
                foreach ($val[1] as $k => $v) {
                    $warehouse = Warehouses::where('name', $v)->find();
                    if ($warehouse) {
                        $warehouses[$k] = [
                            'name' => $v,
                            'entity_warehouse_id' => $warehouse->parent_id,
                            'virtual_warehouse_id' =>  $warehouse->id,
                        ];
                    }
                }
                unset($val[1]);
                //拼装库存扣减数据
                foreach ($val as  $p) {
                    foreach ($p as  $j => $s) {
//                        if (is_numeric($s) && $s != 0) {
                        if (is_numeric($s)) {
                            $product = [
                                [
                                    'goods_code' => $p[0],
                                    'number' => $s,
                                    'type' => 1
                                ]
                            ];
                            // 查到商品sku的系統現有庫存
                            if (isset($warehouses[$j])) {
                                $stock = $this->warehouseStockModel
                                    ->where('entity_warehouse_id', '=', $warehouses[$j]['entity_warehouse_id'])
                                    ->where('virtual_warehouse_id', '=', $warehouses[$j]['virtual_warehouse_id'])
                                    ->where('goods_code', '=', $product[0]['goods_code'])
                                    ->sum('number');
//                                print_r($stock);exit();
                                // 系统有剩余库存，实际库存为0，需重置为0
                                if ($stock > 0 && $product[0]['number'] <= 0){
                                    $product[0]['number'] = $stock;
                                }else{
                                    $product[0]['number'] = $stock - $product[0]['number'];
                                }
                            }
                            $products = [];
                            if (isset($warehouses[$j])) {
                                //计算出库批次
                                $products = $allotOrdersModel->getOutboundOrderProducts($warehouses[$j]['entity_warehouse_id'], $warehouses[$j]['virtual_warehouse_id'], $product);
                            }
//                            print_r($products);exit();
                            if (!empty($products)) {
                                $productsAllot = [];
                                foreach ($products as $temp) {
                                    if ($temp['number'] < 0){
                                        // 入库单
                                        $products = new Product;
                                        $productData = $products->where('code', trim($temp['goods_code']))->find();
                                        if (empty($productData)){
                                            continue;
                                        }
                                        $category = new Category;
                                        $categoryData = $category->where('id', $productData['category_id'])->find();
                                        $productsAllot[] = [
                                            'goods_id' => $productData['id'], // 多箱商品id
                                            'goods_code' => trim($temp['goods_code']), // 多箱分组code
                                            'category_name' => $categoryData['parent_name'] . $categoryData['name'],
                                            'goods_name' => $productData['name_ch'],
                                            'goods_name_en' => $productData['name_en'],
                                            'goods_pic' => $productData['image_url'],
                                            'number' => $temp['number'],
                                            'type' => 1,
                                            'batch_no' => $temp['batch_no']
                                        ];
                                        $warehouseOrders = new WarehouseOrders;
                                        $dataWarehouse = [
                                            'code' => $warehouseOrders->createOrderNo(),
                                            'entity_warehouse_id' => $warehouses[$j]['entity_warehouse_id'],
                                            'virtual_warehouse_id' => $warehouses[$j]['virtual_warehouse_id'],
                                            'source' => 'manual',
                                            'notes' => '手动修改库存入库',
                                            'audit_status' => 2,
                                            'audit_notes' => '自动通过',
                                            'audit_by' => request()->user()->id,
                                            'audit_time' => date('Y-m-d H:i:s'),
                                            'warehousing_status' => 1,
                                            'warehousing_time' => date('Y-m-d H:i:s'),
                                            'created_by' => request()->user()->id,
                                            'products' => $productsAllot
                                        ];
                                        //变更库存 增加库存 // 单入库
                                        $idWarehouseStock = $warehouseOrders->createWarehouseOrder($dataWarehouse,false);
                                        // 增加库存
                                        $this->warehouseStockModel->increaseStock(
                                            $warehouses[$j]['entity_warehouse_id'],
                                            $warehouses[$j]['virtual_warehouse_id'],
                                            $temp['goods_code'],
                                            $temp['batch_no'],
                                            abs($temp['number']),
                                            $temp['type'],
                                            'manual',
                                            $idWarehouseStock
                                        );
                                    }else {
                                        // 减少库存
                                        $this->warehouseStockModel->reduceStock(
                                            $warehouses[$j]['entity_warehouse_id'],
                                            $warehouses[$j]['virtual_warehouse_id'],
                                            $temp['goods_code'],
                                            $temp['batch_no'],
                                            $temp['number'],
                                            $temp['type'],
                                            'manual'
                                        );
                                    }
                                }
                            }else{
                                if (isset($warehouses[$j]) && $product[0]['number'] < 0) {
                                    $batch_no = $this->warehouseOrderModel->createBatchNo();
                                    // 入库单
                                    $products = new Product;
                                    $productData = $products->where('code', trim($product[0]['goods_code']))->find();
                                    if (empty($productData)){
                                        continue;
                                    }
                                    $category = new Category;
                                    $categoryData = $category->where('id', $productData['category_id'])->find();
                                    $productsAllot[] = [
                                        'goods_id' => $productData['id'], // 多箱商品id
                                        'goods_code' => trim($product[0]['goods_code']), // 多箱分组code
                                        'category_name' => $categoryData['parent_name'] . $categoryData['name'],
                                        'goods_name' => $productData['name_ch'],
                                        'goods_name_en' => $productData['name_en'],
                                        'goods_pic' => $productData['image_url'],
                                        'number' => $product[0]['number'],
                                        'type' => 1,
                                        'batch_no' => $batch_no
                                    ];
                                    $warehouseOrders = new WarehouseOrders;
                                    $dataWarehouse = [
                                        'code' => $warehouseOrders->createOrderNo(),
                                        'entity_warehouse_id' => $warehouses[$j]['entity_warehouse_id'],
                                        'virtual_warehouse_id' => $warehouses[$j]['virtual_warehouse_id'],
                                        'source' => 'manual',
                                        'notes' => '手动修改库存入库',
                                        'audit_status' => 2,
                                        'audit_notes' => '自动通过',
                                        'audit_by' => request()->user()->id,
                                        'audit_time' => date('Y-m-d H:i:s'),
                                        'warehousing_status' => 1,
                                        'warehousing_time' => date('Y-m-d H:i:s'),
                                        'created_by' => request()->user()->id,
                                        'products' => $productsAllot
                                    ];
                                    //变更库存 增加库存 // 单入库
                                    $idWarehouseStock = $warehouseOrders->createWarehouseOrder($dataWarehouse, false);
                                    //记录不存在新建
                                    $this->warehouseStockModel->increaseStock(
                                        $warehouses[$j]['entity_warehouse_id'],
                                        $warehouses[$j]['virtual_warehouse_id'],
                                        $product[0]['goods_code'],
                                        $batch_no,
                                        abs($product[0]['number']),
                                        1,
                                        'manual',
                                        $idWarehouseStock
                                    );
                                }
                            }
                        }
                    }
                }
            }
            $this->warehouseStockModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->warehouseStockModel->rollback();
            throw new FailedException($e->getLine() . "====" . $e->getMessage());
        }
    }
}
