<?php


namespace catchAdmin\warehouse\controller;

use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\excel\CommonExport;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;
use catchAdmin\basics\model\ShopWarehouse;

class WarehouseOrder extends CatchController
{

    protected $warehouseOrdersModel;
    protected $warehouseOrderProductsModel;
    protected $warehouseStockModel;

    public function __construct(
        WarehouseOrders $warehouseOrders,
        WarehouseOrderProducts $warehouseOrderProducts,
        WarehouseStock $warehouseStock
    ) {
        $this->warehouseOrdersModel        = $warehouseOrders;
        $this->warehouseOrderProductsModel = $warehouseOrderProducts;
        $this->warehouseStockModel         = $warehouseStock;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {
        return CatchResponse::paginate(
            $this->warehouseOrdersModel->getList('list')
        );
    }


    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(CatchRequest $request)
    {
        $data = $request->post();

        $res = $this->warehouseOrdersModel->getList('export');

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }



        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->warehouseOrdersModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '入库单');
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
            $data = $request->post();

            if (!isset($data['products'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }
            $data['created_by'] = $data['creator_id'];
            $data['source']     = 'manual';
            $this->warehouseOrdersModel->createWarehouseOrder($data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
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
            $data = $request->post();

            if (!isset($data['products'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }
            $this->warehouseOrdersModel->startTrans();

            $order = $this->warehouseOrdersModel->findBy($id);
            if ($order->audit_status == 2) {
                return CatchResponse::fail('入库已审核，不能修改', Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $data['audit_status'] = $order->audit_status == -1 ? 0 : $order->audit_status;
            $this->warehouseOrdersModel->updateBy($id, $data);

            DB::table('warehouse_order_products')->where('warehouse_order_id', $id)->delete();
            $products = [];
            foreach ($data['products'] as $val) {
                $row        = [
                    'warehouse_order_id' => $id,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'number' => $val['number'],
                    'type' => $val['type'],
                    'batch_no' =>  $this->warehouseOrdersModel->createBatchNo()
                ];
                $products[] = $row;
            }
            $this->warehouseOrderProductsModel->saveAll($products);
            $this->warehouseOrdersModel->fixProduct($id);
            $this->warehouseOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
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
     */
    public function read($id)
    {
        $order = $this->warehouseOrdersModel->find($id);
        $order['virtual_warehouse'] = $this->warehouseOrdersModel->getEntityWarehouse($order['virtual_warehouse_id']);
        $order['entity_warehouse'] = $this->warehouseOrdersModel->getEntityWarehouse($order['entity_warehouse_id']);
        $order['products'] = $this->warehouseOrdersModel->products($id);
        $order['parts'] = $this->warehouseOrdersModel->parts($id);
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
        $data = $request->post();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->warehouseOrdersModel->findBy($id);


            if ($order && $order->audit_status < 2) {
                $this->warehouseOrdersModel->updateBy($id, ['audit_status' => 1]);
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

            $order = $this->warehouseOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('入库单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('入库单未提交审核，请先提交审核', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('入库单已入库, 不可以修改审核状态', Code::FAILED);
            }

            $data['audit_by'] = $data['creator_id'];
            $data['audit_time'] = date('Y-m-d H:i:s');
            $this->warehouseOrdersModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 批量删除
     *
     */
    public function batchDelete(CatchRequest $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->warehouseOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('入库单不存在', Code::FAILED);
            }

            if ($order->audit_status > 0) {
                return CatchResponse::fail('入库单已审核，不能删除', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('入库单已入库, 不能删除', Code::FAILED);
            }
        }
        return CatchResponse::success($this->warehouseOrdersModel->deleteBy($ids));
    }

    /**
     * 入库单确认入库
     * @param CatchRequest $request
     * @param              $id
     *
     * @return \think\response\Json
     */
    public function inStock(CatchRequest $request, $id)
    {
        try {
            $this->warehouseOrdersModel->startTrans();
            $order = $this->warehouseOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('入库单不存在', Code::FAILED);
            }

            if ($order->audit_status != 2) {
                return CatchResponse::fail('入库单未审核, 不能入库', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('入库单已入库, 不能重复操作', Code::FAILED);
            }

            $products = $this->warehouseOrderProductsModel->where('warehouse_order_id', $id)
                ->select();

            //变更库存
            foreach ($products as $product) {
                $this->warehouseStockModel->increaseStock(
                    $order->entity_warehouse_id,
                    $order->virtual_warehouse_id,
                    $product->goods_code,
                    $product->batch_no,
                    $product->number,
                    $product->type,
                    'warehouseOrder',
                    $order->id,
                );
            }

            //修改入库单入库状态
            $order->warehousing_status = 1;
            $order->warehousing_time   = date('Y-m-d H:i:s');
            $order->save();
            $this->warehouseOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->warehouseStockModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }


        return CatchResponse::success(true);
    }

    /**
     * 修正入库单生成 json 数据
     */
    public function orderJsonFix(CatchRequest $request)
    {
        $id = $request->param('id') ?? '';
        $this->warehouseOrdersModel->fixProduct($id);
        return CatchResponse::success(true);
    }
}
