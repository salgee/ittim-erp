<?php


namespace catchAdmin\warehouse\controller;

use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;
// use catchAdmin\supply\excel\CommonExport;
use catchAdmin\warehouse\excel\CommonExport;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\warehouse\model\FbaAllotOrderProducts;
use catchAdmin\warehouse\model\FbaAllotOrders;
use catchAdmin\warehouse\model\OutboundOrders;
// use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Cache;
use think\facade\Db;

class FbaAllotOrder extends CatchController
{

    protected $warehouseOrdersModel;
    protected $outboundOrdersModel;
    protected $fbaAllotOrdersModel;
    protected $fbaAllotOrderProductsModel;
    protected $warehouseStockModel;
    protected $allotOrdersModel;

    public function __construct(
        WarehouseOrders $warehouseOrders,
        OutboundOrders $outboundOrdersModel,
        WarehouseStock $warehouseStock,
        FbaAllotOrders $fbaAllotOrders,
        FbaAllotOrderProducts $fbaAllotOrderProducts,
        AllotOrders $allotOrders
    ) {
        $this->warehouseOrdersModel       = $warehouseOrders;
        $this->outboundOrdersModel        = $outboundOrdersModel;
        $this->fbaAllotOrdersModel        = $fbaAllotOrders;
        $this->fbaAllotOrderProductsModel = $fbaAllotOrderProducts;
        $this->warehouseStockModel        = $warehouseStock;
        $this->allotOrdersModel           = $allotOrders;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {

        return CatchResponse::paginate($this->fbaAllotOrdersModel->getList('list'));
    }

    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(CatchRequest $request)
    {

        $data = $request->post();

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->fbaAllotOrdersModel->exportField();
        }

        $res = $this->fbaAllotOrdersModel->getList('export');

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, 'Fba调拨单');
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

            if (!isset($data['products']) && !isset($data['parts'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }

            $data['created_by'] = $data['creator_id'];
            $dataObj = $data;
            $rowProductempty = [];
            $dataObj['parts'] = json_encode($rowProductempty);
            $dataObj['products'] = json_encode($rowProductempty);
            $this->fbaAllotOrdersModel->startTrans();

            $res      = $this->fbaAllotOrdersModel->storeBy($dataObj);

            if (isset($data['products'])) {
                $products = [];
                foreach ($data['products'] as $val) {
                    $row        = [
                        'fba_allot_order_id' => $res,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'pallet_number' => $val['pallet_number'],
                        'label_price' => $val['label_price'],
                        'pallet_price' => $val['pallet_price'],
                        'outbound_price' => $val['outbound_price'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->fbaAllotOrderProductsModel->saveAll($products);
                $this->fbaAllotOrdersModel->fixProduct($res);
            }

            if (isset($data['parts'])) {
                $products = [];
                foreach ($data['parts'] as $val) {
                    $row        = [
                        'fba_allot_order_id' => $res,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'pallet_number' => $val['pallet_number'],
                        'label_price' => $val['label_price'],
                        'pallet_price' => $val['pallet_price'],
                        'outbound_price' => $val['outbound_price'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->fbaAllotOrderProductsModel->saveAll($products);
                $this->fbaAllotOrdersModel->fixProduct($res);
            }

            $this->fbaAllotOrdersModel->commit();
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
            $data = $request->param();

            if (!isset($data['products']) && !isset($data['parts'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }
            $this->fbaAllotOrdersModel->startTrans();

            $order = $this->fbaAllotOrdersModel->findBy($id);
            if ($order->audit_status > 1) {
                return CatchResponse::fail('调拨单已审核，不能修改', Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $this->fbaAllotOrdersModel->updateBy($id, $data);

            DB::table('fba_allot_order_products')->where('fba_allot_order_id', $id)->delete();

            if (isset($data['products'])) {
                $products = [];
                foreach ($data['products'] as $val) {
                    $row        = [
                        'fba_allot_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'pallet_number' => $val['pallet_number'],
                        'label_price' => $val['label_price'],
                        'pallet_price' => $val['pallet_price'],
                        'outbound_price' => $val['outbound_price'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->fbaAllotOrderProductsModel->saveAll($products);
                $this->fbaAllotOrdersModel->fixProduct($id);
            }

            if (isset($data['parts'])) {
                $products = [];
                foreach ($data['parts'] as $val) {
                    $row        = [
                        'fba_allot_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'packing_method' => $val['packing_method'],
                        'number' => $val['number'],
                        'pallet_number' => $val['pallet_number'],
                        'label_price' => $val['label_price'],
                        'pallet_price' => $val['pallet_price'],
                        'outbound_price' => $val['outbound_price'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->fbaAllotOrderProductsModel->saveAll($products);
                $this->fbaAllotOrdersModel->fixProduct($id);
            }

            $this->fbaAllotOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->fbaAllotOrdersModel->rollback();
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
        $order = $this->fbaAllotOrdersModel->with(['product'])->find($id);
        $order['virtual_warehouse'] = $this->fbaAllotOrdersModel->getEntityWarehouse($order['virtual_warehouse_id']);
        $order['entity_warehouse'] = $this->fbaAllotOrdersModel->getEntityWarehouse($order['entity_warehouse_id']);
        $order['fba_warehouse'] = $this->fbaAllotOrdersModel->getFbaWarehouse($order['fba_warehouse_id']);
        $order['products'] = $this->fbaAllotOrdersModel->products($id);
        $order['parts'] = $this->fbaAllotOrdersModel->parts($id);

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
            $order = $this->fbaAllotOrdersModel->findBy($id);
            if ($order && $order->audit_status < 2) {
                $this->fbaAllotOrdersModel->updateBy($id, ['audit_status' => 1]);
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

            $order = $this->fbaAllotOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('调拨单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('调拨单未提交审核，请先提交审核', Code::FAILED);
            }


            if ($order->audit_status == 2) {
                return CatchResponse::fail('调拨单已通过审核，不能重复操作', Code::FAILED);
            }

            if ($data['audit_status'] == 2) {

                //计算出库批次
                $products = $this->fbaAllotOrdersModel->products($data['id'])->toArray();
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
                    = $this->allotOrdersModel->getOutboundOrderProducts($order->entity_warehouse_id, $order->virtual_warehouse_id, $products);


                //审核通过 生成出库单
                $orderData = [
                    'entity_warehouse_id' => $order->entity_warehouse_id,
                    'virtual_warehouse_id' => $order->virtual_warehouse_id,
                    'source' => 'allot',
                    'audit_status' => 2,   //调拨出库单默认已审核
                    'outbound_status' => 1, //调拨出库单默认已出库
                    'outbound_time' => date('Y-m-d H:i:s'),
                    'created_by' => $data['creator_id'],
                    'products' => $products,
                ];
                $this->outboundOrdersModel->createOutOrder($orderData);

                //变更库存
                foreach ($products as $product) {
                    $this->warehouseStockModel->reduceStock(
                        $order->entity_warehouse_id,
                        $order->virtual_warehouse_id,
                        $product['goods_code'],
                        $product['batch_no'],
                        $product['number'],
                        $product['type'],
                        'FbaAllotOrder',
                        $order->id,
                    );
                }


                //审核通过 生成入库单
                $orderData = [
                    'entity_warehouse_id' => $order->fba_warehouse_id,
                    'virtual_warehouse_id' => $order->fba_warehouse_id,
                    'source' => 'allot',
                    'audit_status' => 2,
                    'created_by' => $data['creator_id'],
                    'products' => $products,
                ];
                $this->warehouseOrdersModel->createWarehouseOrder($orderData);
            }

            $this->fbaAllotOrdersModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     *  批量删除
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function batchDelete(CatchRequest $request)
    {
        $data = $request->post();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->fbaAllotOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('调拨单不存在', Code::FAILED);
            }

            if ($order->audit_status > 1) {
                return CatchResponse::fail('调拨单已审核，不能删除', Code::FAILED);
            }
        }
        return CatchResponse::success($this->fbaAllotOrdersModel->deleteBy($ids));
    }

    public function serviceFee()
    {
        $data =  config('catch.system_service_fee');
        return CatchResponse::success($data);
    }

    /**
     * 修正入库单生成 json 数据
     */
    public function fbaAllotOrderJsonFix(CatchRequest $request)
    {
        $id = $request->param('id') ?? '';
        $this->fbaAllotOrdersModel->fixProduct($id);
        return CatchResponse::success(true);
    }
}
