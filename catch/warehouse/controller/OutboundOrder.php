<?php


namespace catchAdmin\warehouse\controller;

use catchAdmin\warehouse\excel\CommonExport;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\warehouse\model\OutboundOrderProducts;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;


class OutboundOrder extends CatchController
{

    protected $outboundOrdersModel;
    protected $outboundOrderProductsModel;
    protected $warehouseStockModel;
    protected $allotOrdersModel;
    public function __construct(OutboundOrders $outboundOrdersModel, OutboundOrderProducts
    $outboundOrderProductsModel, WarehouseStock $warehouseStock, AllotOrders $allotOrders)
    {
        $this->outboundOrdersModel        = $outboundOrdersModel;
        $this->outboundOrderProductsModel = $outboundOrderProductsModel;
        $this->warehouseStockModel = $warehouseStock;
        $this->allotOrdersModel = $allotOrders;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {
        return CatchResponse::paginate($this->outboundOrdersModel->getList('list'));
    }


    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(CatchRequest $request)
    {

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->outboundOrdersModel->exportField();
        }
        ini_set('memory_limit', '1024M');
        $res = $this->outboundOrdersModel->getList('export');

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '出库单');
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
            $dataObj = $data;
            $rowProductempty  = [];
            $dataObj['parts'] = json_encode($rowProductempty);
            $dataObj['products'] = json_encode($rowProductempty);
            $this->outboundOrdersModel->startTrans();

            $res      = $this->outboundOrdersModel->storeBy($dataObj);
            $products = [];
            foreach ($data['products'] as $val) {
                $row        = [
                    'outbound_order_id' => $res,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'number' => $val['number']
                ];
                $products[] = $row;
            }
            $this->outboundOrderProductsModel->saveAll($products);
            $this->outboundOrdersModel->fixProduct($res);

            $this->outboundOrdersModel->commit();
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
            $this->outboundOrdersModel->startTrans();

            $order = $this->outboundOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('出库单不存在', Code::FAILED);
            }
            if ($order->audit_status == 2) {
                return CatchResponse::fail('出库单已审核，不能修改', Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $this->outboundOrdersModel->updateBy($id, $data);

            DB::table('outbound_order_products')->where('outbound_order_id', $id)->delete();

            $products = [];
            foreach ($data['products'] as $val) {
                $row        = [
                    'outbound_order_id' => $id,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'number' => $val['number']
                ];
                $products[] = $row;
            }
            $this->outboundOrderProductsModel->saveAll($products);
            $this->outboundOrdersModel->fixProduct($id);
            $this->outboundOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 详情
     * @param $id
     *
     * @return \think\response\Json
     */
    public function read($id)
    {
        $order = $this->outboundOrdersModel->find($id);
        $order['virtual_warehouse'] = $this->outboundOrdersModel->getEntityWarehouse($order['virtual_warehouse_id']);
        $order['entity_warehouse'] = $this->outboundOrdersModel->getEntityWarehouse($order['entity_warehouse_id']);
        $order['products'] = $this->outboundOrdersModel->products($id);
        $order['parts'] = $this->outboundOrdersModel->parts($id);

        return CatchResponse::success($order);
    }


    /**
     * 提交审核
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function SubmitAudit(CatchRequest $request)
    {
        $data = $request->param();
        $ids = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->outboundOrdersModel->findBy($id);
            if ($order && $order->audit_status <  2) {
                $this->outboundOrdersModel->updateBy($id, ['audit_status' => 1]);
            }
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改审核状态
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeAuditStatus(CatchRequest $request)
    {
        try {
            $data = $request->post();

            $order = $this->outboundOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('出库单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('出库单未提交审核，请先提交审核', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('出库单已出库, 不可以修改审核状态', Code::FAILED);
            }

            if ($order->audit_status == 1 && $data['audit_status'] == 2) {

                $products = $this->outboundOrderProductsModel->where('outbound_order_id', $data['id'])->select()->toArray();
                foreach ($products as $product) {
                    // 查询实时库存
                    $num = $this->warehouseStockModel->where(
                        [
                            'goods_code' => $product['goods_code'],
                            'virtual_warehouse_id' => $order->virtual_warehouse_id,
                            'entity_warehouse_id' => $order->entity_warehouse_id
                        ],
                    )->sum('number');
                    if ((int)$num < (int)$product['number']) {
                        return CatchResponse::fail('出库仓库库存/批次不足, 商品编码：' . $product['goods_code'], Code::FAILED);
                    }
                }

                $products
                    = $this->allotOrdersModel->getOutboundOrderProductsOut($order->entity_warehouse_id, $order->virtual_warehouse_id, $products);
                if(count($products) == 0) {
                    return CatchResponse::fail('出库仓库库存/批次不足, 商品编码：' . $product['goods_code'], Code::FAILED);
                }
                $this->outboundOrdersModel->startTrans();
                // 删除出库单原始关联商品
                DB::table('outbound_order_products')->where('outbound_order_id', $data['id'])->delete();
                // 重新组装出库单关联商品信息
                $productsArr = [];
                foreach ($products as $val) {
                    $row = [
                        'outbound_order_id' => $data['id'],
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'number' => $val['number'],
                        'batch_no' => $val['batch_no']
                    ];
                    $productsArr[] = $row;
                }
                $this->outboundOrderProductsModel->saveAll($productsArr);
                $this->outboundOrdersModel->fixProduct($data['id']);

                //变更库存
                foreach ($products as $product) {

                    $this->warehouseStockModel->reduceStock(
                        $order->entity_warehouse_id,
                        $order->virtual_warehouse_id,
                        $product['goods_code'],
                        $product['batch_no'],
                        $product['number'],
                        $product['type'],
                        'OutboundOrder',
                        $order->id,
                    );
                }
                $this->outboundOrdersModel->commit();
            }

            $this->outboundOrdersModel->updateBy($data['id'], $data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->outboundOrdersModel->rollback();

            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 批量删除
     *
     */
    public function batchDelete(CatchRequest $request)
    {
        $data = $request->param();
        $ids = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->outboundOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('出库单不存在', Code::FAILED);
            }

            if ($order->audit_status > 0) {
                return CatchResponse::fail('出库单已审核，不能删除', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('出库单已出库, 不能删除', Code::FAILED);
            }
        }
        return CatchResponse::success($this->outboundOrdersModel->deleteBy($ids));
    }

    /**
     * 出库
     * @param CatchRequest $request
     * @param $id
     * @return \think\response\Json
     */
    public function outStock(CatchRequest $request, $id)
    {
        try {
            $this->outboundOrdersModel->startTrans();
            $order = $this->outboundOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('出库单不存在', Code::FAILED);
            }

            if ($order->audit_status != 2) {
                return CatchResponse::fail('出库单未审核, 不能出库', Code::FAILED);
            }

            if ($order->warehousing_status == 1) {
                return CatchResponse::fail('出库单已出库, 不能重复操作', Code::FAILED);
            }

            $products = $this->outboundOrderProductsModel->where('out_order_id', $id)->select();

            //变更库存
            foreach ($products as $product) {

                $stock = WarehouseStock::where('entity_warehouse_id', $order->entity_warehouse_id)
                    ->where('virtual_warehouse_id', $order->virtual_warehouse_id)
                    ->where('goods_code', $product->goods_code)->find();
                if (!$stock) {
                    continue;
                } else {
                    //记录存在则更新
                    $stock->number =  $stock->number - $product->number;
                    $stock->save();
                }
            }

            //修改出库单入库状态
            $order->outbound_status = 1;
            $order->outbound_time = date('Y-m-d H:i:s');
            $order->save();
            $this->outboundOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->outboundOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }


        return CatchResponse::success(true);
    }

    /**
     * 出库单修正数据
     */
    public function OutboundOrderJsonFix(CatchRequest $request)
    {
        $id = $request->param('id') ?? '';
        $this->outboundOrdersModel->fixProduct($id);
        return CatchResponse::success(true);
    }
}
