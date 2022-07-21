<?php


namespace catchAdmin\warehouse\controller;

use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\product\model\Product;
// use catchAdmin\supply\excel\CommonExport;
use catchAdmin\warehouse\excel\CommonExport;
use catchAdmin\warehouse\model\AllotOrderProducts;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catchAdmin\warehouse\WarehouseService;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Cache;
use think\facade\Db;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;


class AllotOrder extends CatchController
{

    protected $warehouseOrdersModel;
    protected $warehouseOrderProductsModel;
    protected $allotOrdersModel;
    protected $allotOrderProductsModel;
    protected $outboundOrdersModel;
    protected $warehouseStockModel;

    public function __construct(
        WarehouseOrders $warehouseOrders,
        WarehouseOrderProducts $warehouseOrderProducts,
        WarehouseStock $warehouseStock,
        AllotOrders $allotOrders,
        AllotOrderProducts $allotOrderProducts,
        OutboundOrders $outboundOrders
    ) {
        $this->warehouseOrdersModel        = $warehouseOrders;
        $this->warehouseOrderProductsModel = $warehouseOrderProducts;
        $this->allotOrdersModel            = $allotOrders;
        $this->allotOrderProductsModel     = $allotOrderProducts;
        $this->outboundOrdersModel         = $outboundOrders;
        $this->warehouseStockModel         = $warehouseStock;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {
        return CatchResponse::paginate($this->allotOrdersModel->getList('list'));
    }

    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(CatchRequest $request)
    {

        $data = $request->post();

        $res = $this->allotOrdersModel->getList('export');

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->allotOrdersModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '调拨单');
        return  CatchResponse::success($url);
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(CatchRequest $request): \think\Response
    {
        try {
            $data = $request->param();

            if (!isset($data['products']) && !isset($data['parts'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }

            $data['created_by'] = $data['creator_id'];
            $this->allotOrdersModel->startTrans();
            $this->allotOrdersModel->add($data);
            $this->allotOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getFile() . $exception->getLine()
                . $exception->getMessage(), $code);
        }
    }




    /**
     * 修改
     * @time 2021年01月23日 14:55
     *
     * @param CatchRequest $request
     */
    public function update(CatchRequest $request, $id): \think\Response
    {
        try {
            $data = $request->param();

            if (!isset($data['products']) && !isset($data['parts'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }
            $this->allotOrdersModel->startTrans();

            $order = $this->allotOrdersModel->findBy($id);
            if ($order->audit_status > 1) {
                return CatchResponse::fail('调拨单已审核，不能修改', Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $this->allotOrdersModel->updateBy($id, $data);

            DB::table('allot_order_products')->where('allot_order_id', $id)->delete();

            if (isset($data['products'])) {
                $products = [];
                foreach ($data['products'] as $val) {
                    $row        = [
                        'allot_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'type' => $val['type'],

                    ];
                    $products[] = $row;
                }
                $this->allotOrderProductsModel->saveAll($products);
                $this->allotOrdersModel->fixProduct($id);
            }

            if (isset($data['parts'])) {
                $products = [];
                foreach ($data['parts'] as $val) {
                    $row        = [
                        'allot_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->allotOrderProductsModel->saveAll($products);
                $this->allotOrdersModel->fixProduct($id);
            }

            $this->allotOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->allotOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 详情
     *
     * @param $id
     *
     * @return \think\response\Json
     * // 'created_by_name', 'updated_by_name', 'entity_warehouse',
     *  // 'transfer_in_warehouse', 'transfer_out_warehouse', 'products', 'parts'
     */
    public function read($id)
    {
        $order = $this->allotOrdersModel->find($id);
        $order['entity_warehouse'] = $this->allotOrdersModel->getEntityWarehouse($order['entity_warehouse_id']);
        $order['transfer_in_warehouse'] = $this->allotOrdersModel->getTransferInWarehouse($order['transfer_in_warehouse_id']);
        $order['transfer_out_warehouse'] = $this->allotOrdersModel->getTransferOutWarehouse($order['transfer_out_warehouse_id']);
        $order['products'] = $this->allotOrdersModel->products($id);
        $order['parts'] = $this->allotOrdersModel->parts($id);
        return CatchResponse::success($order);
    }


    /**
     * 提交审核
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function SubmitAudit(CatchRequest $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->allotOrdersModel->findBy($id);
            if ($order && $order->audit_status < 2) {
                $this->allotOrdersModel->updateBy($id, ['audit_status' => 1]);
            }
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改审核状态
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeAuditStatus(CatchRequest $request)
    {
        try {
            $data = $request->post();

            $this->allotOrdersModel->startTrans();
            $order = $this->allotOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('调拨单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('调拨单未提交审核，请先提交审核', Code::FAILED);
            }

            if ($order->audit_status == 3) {
                return CatchResponse::fail('调拨单已调拨入库审核。', Code::FAILED);
            }


            $this->allotOrdersModel->updateBy($data['id'], $data);

            $key = 'allot_products_cache_' . $order->id;
            //调入审核通过 生成出库单
            //调入审核通过 生成入库单
            if ($data['audit_status'] == 3) {


                if ($order->audit_status == 3) {
                    return CatchResponse::fail('调拨单已调拨入库审核，不能重复操作。', Code::FAILED);
                }
                //计算出库批次
                $products = $this->allotOrdersModel->products($data['id'])->toArray();
                foreach ($products as $product) {
                    // 查询实时库存
                    $num = $this->warehouseStockModel->where(
                        [
                            'goods_code' => $product['goods_code'],
                            'virtual_warehouse_id' => $order->transfer_out_warehouse_id,
                            'entity_warehouse_id' => $order->entity_warehouse_id
                        ],
                    )->sum('number');
                    if ((int)$num < (int)$product['number']) {
                        return CatchResponse::fail('出库仓库库存/批次不足, 商品编码：' . $product['goods_code'], Code::FAILED);
                    }
                }
                $products
                    = $this->allotOrdersModel->getOutboundOrderProducts($order->entity_warehouse_id, $order->transfer_out_warehouse_id, $products);
                //缓存出库商品信息 创建入库单用到
                Cache::set($key, $products);

                $orderData = [
                    'entity_warehouse_id' => $order->entity_warehouse_id,
                    'virtual_warehouse_id' => $order->transfer_out_warehouse_id,
                    'source' => 'allot',
                    'audit_status' => 2,   //调拨出库单默认已审核
                    'outbound_status' => 1, //调拨出库单默认已出库
                    'outbound_time' => date('Y-m-d H:i:s'),
                    'created_by' => $data['creator_id'],
                    'notes' => $order->notes,
                    'products' => $products,
                ];
                $orderId = $this->outboundOrdersModel->createOutOrder($orderData);

                //变更库存
                foreach ($products as $product) {
                    $this->warehouseStockModel->reduceStock(
                        $order->entity_warehouse_id,
                        $order->transfer_out_warehouse_id,
                        $product['goods_code'],
                        $product['batch_no'],
                        $product['number'],
                        $product['type'],
                        'OutboundOrder',
                        $orderId,


                    );
                }

                $products = Cache::get($key);

                if ($order->audit_status == 1) {
                    return CatchResponse::fail('调拨单未调拨出库审核。', Code::FAILED);
                }
                $orderData = [
                    'entity_warehouse_id' => $order->entity_warehouse_id,
                    'virtual_warehouse_id' => $order->transfer_in_warehouse_id,
                    'source' => 'allot',
                    'audit_status' => 2,
                    'created_by' => $data['creator_id'],
                    'notes' => $order->notes,
                    'products' => $products,
                ];
                $this->warehouseOrdersModel->createWarehouseOrder($orderData);

                Cache::delete($key);
            }
            $this->allotOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->allotOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 批量删除
     *
     * @param CatchRequest $request
     * @return void
     */
    public function batchDelete(CatchRequest $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->allotOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('调拨单不存在', Code::FAILED);
            }

            if ($order->audit_status > 1) {
                return CatchResponse::fail('调拨单已审核，不能删除', Code::FAILED);
            }
        }
        return CatchResponse::success($this->allotOrdersModel->deleteBy($ids));
    }

    /**
     * 调拨单导入
     *
     * @param CatchRequest $request
     * @param ZipCodeImport $import
     * @param \catcher\CatchUpload $upload
     * @return void
     */
    public function importOrder(CatchRequest $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $file = $request->file();
        $data = $import->read($file['file']);
        //print_r($data);exit;
        $order = [];

        foreach ($data as $val) {

            //查找实体仓id
            $enWarehouse = Warehouses::where('name', $val[0])->find();
            if (!$enWarehouse) {
                return CatchResponse::fail("实体仓{$val[0]}不存在", Code::FAILED);
            }

            $outWarehouse = Warehouses::where('name', $val[1])->find();
            if (!$outWarehouse) {
                return CatchResponse::fail("转出仓{$val[1]}不存在", Code::FAILED);
            }

            $inWarehouse = Warehouses::where('name', $val[2])->find();
            if (!$inWarehouse) {
                return CatchResponse::fail("转入仓{$val[2]}不存在", Code::FAILED);
            }
            if ($inWarehouse['parent_id'] != $outWarehouse['parent_id']) {
                return CatchResponse::fail("转入仓{$val[2]}和转出仓{$val[1]}不是同一个实体仓", Code::FAILED);
            }

            $product = Product::where('code', $val[3])->find();
            if (!$product) {
                return CatchResponse::fail("商品{$val[3]}不存在", Code::FAILED);
            }

            $stock =  WarehouseStock::where('virtual_warehouse_id', $outWarehouse->id)->where('goods_code', $val[3])->sum('number');

            if (!$stock) {
                return CatchResponse::fail("商品{$val[3]}没有库存数据", Code::FAILED);
            }

            if ($stock < $val[4]) {
                return CatchResponse::fail("商品{$val[3]}库存不足", Code::FAILED);
            }

            $orderProduct = [
                'goods_id' => $product->id,
                'goods_code' => $product->code,
                'goods_name' => $product->name_ch,
                'goods_name_en' => $product->name_en,
                'category_name' => $product->category_name,
                'goods_pic' => $product->image_url,
                'packing_method' => $product->packing_method,
                'number' => $val[4],
                'type' => 1

            ];
            if (empty($order)) {

                $order[] = [
                    'entity_warehouse_id' => $enWarehouse->id,
                    'transfer_out_warehouse_id' => $outWarehouse->id,
                    'transfer_in_warehouse_id' => $inWarehouse->id,
                    'notes' => $val[5],
                    'products' => [$orderProduct],

                ];
            } else {

                $flag = true;
                foreach ($order as $key => $v) {

                    if (
                        $v['entity_warehouse_id'] == $enWarehouse->id && $v['transfer_out_warehouse_id'] == $outWarehouse->id &&
                        $v['transfer_in_warehouse_id'] ==  $inWarehouse->id
                    ) {
                        $order[$key]['products'][] = $orderProduct;
                        $flag = false;
                    }
                }

                if ($flag) {
                    $row = [
                        'entity_warehouse_id' => $enWarehouse->id,
                        'transfer_out_warehouse_id' => $outWarehouse->id,
                        'transfer_in_warehouse_id' => $inWarehouse->id,
                        'notes' => $val[5],
                        'products' => [$orderProduct],

                    ];
                    array_push($order, $row);
                }
            }
        }

        try {
            $creator = $request->param('creator_id');
            $this->allotOrdersModel->startTrans();
            foreach ($order as $val) {
                $val['created_by'] = $creator;
                $this->allotOrdersModel->add($val);
            }
            $this->allotOrdersModel->commit();
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getFile() . $exception->getLine()
                . $exception->getMessage(), $code);
        }


        return CatchResponse::success(true);
    }
    /**
     * 调拨单模板
     */
    public function importOrderTemplate()
    {
        return download(public_path() . 'template/allotOrderImport.xlsx', 'allotOrderImport.xlsx')->force(true);
    }

    /**
     * 修正入库单生成 json 数据
     */
    public function allotOrderJsonFix(CatchRequest $request)
    {
        $id = $request->param('id') ?? '';
        $this->allotOrdersModel->fixProduct($id);
        return CatchResponse::success(true);
    }
}
