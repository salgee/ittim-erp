<?php


namespace catchAdmin\supply\controller;

use Carbon\Carbon;
use catchAdmin\finance\model\PurchasePayment;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\model\PurchaseOrderProducts;
use catchAdmin\supply\model\PurchaseOrders;
use catchAdmin\supply\model\SubOrders;
use catchAdmin\supply\model\Supply;
use catchAdmin\supply\model\PurchaseContracts;
use catchAdmin\supply\model\PurchaseContractProducts;
use catchAdmin\supply\model\TranshipmentOrderProducts;
use catchAdmin\supply\model\TranshipmentOrders;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\Code;
use think\facade\Db;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\system\model\DictionaryData;


class TranshipmentOrder extends CatchController
{
    protected $purchaseOrdersModel;
    protected $purchaseOrderProductsModel;
    protected $supplyModel;
    protected $purchaseContractModel;
    protected $purchaseContractProductsModel;
    protected $transhipmentOrdersModel;
    protected $transhipmentOrderProductsModel;
    protected $subOrdersModel;
    protected $warehouseOrdersModel;
    protected $warehouseStockModel;
    protected $purchasePaymentModel;


    public function __construct(
        PurchaseOrders $purchaseOrders,
        PurchaseOrderProducts $purchaseOrderProducts,
        Supply $supplyModel,
        PurchaseContracts $purchaseContractModel,
        PurchaseContractProducts $purchaseContractProductsModel,
        TranshipmentOrders $transhipmentOrdersModel,
        TranshipmentOrderProducts $transhipmentOrderProductsModel,
        SubOrders $subOrders,
        WarehouseOrders $warehouseOrders,
        WarehouseStock $warehouseStock,
        PurchasePayment $purchasePayment
    ) {
        $this->supplyModel                    = $supplyModel;
        $this->purchaseOrdersModel            = $purchaseOrders;
        $this->purchaseOrderProductsModel     = $purchaseOrderProducts;
        $this->purchaseContractModel          = $purchaseContractModel;
        $this->purchaseContractProductsModel  = $purchaseContractProductsModel;
        $this->transhipmentOrdersModel        = $transhipmentOrdersModel;
        $this->transhipmentOrderProductsModel = $transhipmentOrderProductsModel;
        $this->subOrdersModel                 = $subOrders;
        $this->warehouseOrdersModel           = $warehouseOrders;
        $this->warehouseStockModel            = $warehouseStock;
        $this->purchasePaymentModel           = $purchasePayment;
    }

    /**
     * 列表
     * @param CatchAuth
     * @return \think\response\Json
     */
    public function index()
    {

        $list = $this->transhipmentOrdersModel->dataRange([], 'created_by')->catchSearch()->order('id', 'desc')->paginate();

        return CatchResponse::paginate($list);
    }


    /**
     * 导出
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {

        $data = $request->post();


        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->transhipmentOrdersModel->exportField();
        }

        $data = $request->param();
        $transhipmentOrderProducts = new TranshipmentOrderProducts;
        $query = $transhipmentOrderProducts->dataRange([], 'to.created_by')->alias('top')
            ->leftJoin('transhipment_orders to', 'to.id = top.trans_order_id')
            ->leftJoin('lforwarder_company lc', 'lc.id = to.lforwarder_company')
            ->where('to.deleted_at', 0)
            ->field('top.purchase_product_id, top.trans_number, to.loading_date, to.shipment_port,to.destination_port,
                 to.lcl_type, to.cabinet_no, to.seal_no,to.ships_name, to.lforwarder_company, to.shipment_date, 
                 to.arrive_date,to.bl_no, to.notes, lc.name as lforwarder_company_name');

        if (isset($data['audit_status']) && $data['audit_status']) {
            $query->where('to.audit_status', $data['audit_status']);
        }

        if (isset($data['code']) && $data['code']) {
            $query->whereLike('to.code', $data['code']);
        }

        if (isset($data['cabinet_no']) && $data['cabinet_no']) {
            $query->whereLike('to.cabinet_no', $data['cabinet_no']);
        }

        if (isset($data['bl_no']) && $data['bl_no']) {
            $query->whereLike('to.bl_no', $data['bl_no']);
        }

        if (isset($data['supply']) && $data['supply']) {
            $supply = Supply::where('name', $data['supply'])->first();
            $query->where('to.supply_id', $supply->id ?? 0);
        }

        if (isset($data['lforwarder_company']) && $data['lforwarder_company']) {
            // $query->whereLike('lc.name', $data['lforwarder_company']);
            $id = Lforwarder::whereLike('name', $data['lforwarder_company'])->column('id') ?? [];
            $query->whereIn('to.lforwarder_company', $id);
        }
        // 起运日期 
        if (isset($data['shipment_date']) && $data['shipment_date']) {
            $query->where('shipment_date', $data['shipment_date']);
        }


        $res = $query->order('top.id', 'desc')->select()->toArray();
        foreach ($res as &$val) {
            //或者商品信息
            $product = PurchaseOrderProducts::find($val['purchase_product_id']);
            $val['code'] = $product->goods_code ?? '';
            $val['name_ch'] = $product->goods_name ?? '';
            $val['container_rate'] = $product->container_rate ?? '';
            $val['shipment_port_text'] = DictionaryData::where('id', $val['shipment_port'])->value('dict_data_name') ?? '';
            $val['destination_port_text'] = DictionaryData::where('id', $val['destination_port'])->value('dict_data_name') ?? '';
            $val['lcl_type_text'] = DictionaryData::where('id', $val['lcl_type'])->value('dict_data_name') ?? '';
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '出运单');

        return  CatchResponse::success($url);
    }


    /**
     * 创建出运单
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(Request $request): \think\Response
    {
        try {
            $this->transhipmentOrdersModel->startTrans();
            $data = $request->post();

            $contract = $this->purchaseContractModel->findBy($data['purchase_contract_id']);
            if (!$contract) {
                throw new \Exception('合同不存在', Code::FAILED);
            }

            //检查相同出运单的出运日期
            $torder = $this->transhipmentOrdersModel->where('bl_no', $data['bl_no'])->find();
            if ($torder && $torder->shipment_date != $data['shipment_date']) {
                throw new \Exception($data['bl_no'] . "的起运日期与历史出运单不一致", Code::FAILED);
            }

            $data['supply_id']  = $contract->getAttr('supply_id');
            $data['code']       = $this->transhipmentOrdersModel->createTransShipmentNo();
            $data['batch_no']   = $contract->batch_no;
            $data['created_by'] = $data['creator_id'];
            $res                = $this->transhipmentOrdersModel->storeBy($data);

            if (!isset($data['products'])) {
                throw new \Exception('请选择要转运的商品', Code::FAILED);
            }

            $list = [];
            foreach ($data['products'] as $val) {
                //检查商品转运量是否超过采购量
                $product = $this->purchaseOrderProductsModel->findBy($val['id']);
                //获取所有已经转发的数量
                $transNum = $this->transhipmentOrderProductsModel
                    ->where('purchase_product_id', $val['id'])
                    ->sum('trans_number');
                if ($val['trans_number'] + $transNum > $product->number) {
                    throw new \Exception("{$product->goods_name}的转运量大于采购量，请重新输入", Code::FAILED);
                }

                $row['trans_order_id']       = $res;
                $row['purchase_product_id']  = $val['id'];
                $row['trans_number']         = $val['trans_number'];
                $row['purchase_contract_id'] = $data['purchase_contract_id'];

                $list[] = $row;
            }

            $this->transhipmentOrderProductsModel->saveAll($list);
            $this->transhipmentOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->transhipmentOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }


    /**
     * 更新
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     * @param         $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $this->transhipmentOrdersModel->startTrans();

            $data               = $request->post();
            $data['updated_by'] = $data['creator_id'];
            unset($data['created_at']);

            if (!isset($data['products'])) {
                throw new \Exception('请选择要转运的商品', Code::FAILED);
            }

            //检查相同出运单的出运日期
            $torder = $this->transhipmentOrdersModel->where('bl_no', $data['bl_no'])->whereNotIn('id', $id)->find();
            if ($torder && $torder->shipment_date != $data['shipment_date']) {
                throw new \Exception($data['bl_no'] . "的出运日期与历史出运单不一致", Code::FAILED);
            }

            $res = $this->transhipmentOrdersModel->updateBy($id, $data);


            foreach ($data['products'] as $val) {
                //检查商品转运量是否超过采购量
                $transProduct = $this->transhipmentOrderProductsModel->findBy($val['id']);

                //获取所有已经转发的数量
                $transNum = $this->transhipmentOrderProductsModel
                    ->where('purchase_product_id', $transProduct->purchase_product_id)
                    ->where('trans_order_id', '<>', $id)
                    ->sum('trans_number');

                if ($val['trans_number'] + $transNum > $transProduct->product->number) {
                    throw new \Exception("{$transProduct->product->goods_name}的转运量大于采购量，请重新输入", Code::FAILED);
                }

                $this->transhipmentOrderProductsModel->updateBy($val['id'], $val);
            }
            $this->transhipmentOrdersModel->commit();
            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->transhipmentOrderProductsModel->rollback();
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

        $order = $this->transhipmentOrdersModel->find($id);
        if (!$order) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        $order->products = $this->transhipmentOrdersModel->products($id, 1);
        $order->parts    = $this->transhipmentOrdersModel->products($id, 2);
        return CatchResponse::success($order);
    }


    /**
     * 预分仓详情
     *
     * @param  $id
     * @return \think\response\Json
     */
    public function subOrderdetail($id)
    {

        $order = $this->transhipmentOrdersModel->find($id);
        if (!$order) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        $order->products = $this->transhipmentOrdersModel->subProducts($id, 1);
        $order->parts    = $this->transhipmentOrdersModel->subProducts($id, 2);
        return CatchResponse::success($order);
    }


    /**
     * 提交审核
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function SubmitAudit(Request $request)
    {
        $data = $request->post();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $supply = $this->transhipmentOrdersModel->findBy($id);
            if ($supply && $supply->audit_status == 0) {
                $this->transhipmentOrdersModel->updateBy($id, ['audit_status' => 1]);
            }
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改审核状态
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function changeAuditStatus(Request $request)
    {
        try {
            $data = $request->post();

            $order = $this->transhipmentOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('出运单不存在', Code::FAILED);
            }

            if ($order->audit_status == 1) {
                return CatchResponse::fail('出运单已审核通过，不能修改审核状态', Code::FAILED);
            }

            //审核通过后生成付款单
            if ($data['audit_status'] == 1) {
                $supply  = $order->supply;
                $payment = [
                    'payment_no' => $this->purchasePaymentModel->createPaymentNo(),
                    'source' => '出运单付款',
                    'trans_code' => $order->code,
                    'contract_code' => $order->contract->code,
                    'supply_id' => $supply->id,
                    'supply_name' => $supply->getAttr('name'),
                    'order_amount' => $order->amount(),
                    'estimated_pay_time' => Carbon::parse($order->loading_date)->addDays($supply->billing_cycles)->toDateString(),
                    'creator_id' => $data['creator_id'],
                ];

                $this->purchasePaymentModel->storeBy($payment);
            }

            $this->transhipmentOrdersModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 删除出运单商品
     *
     * @param Request $request
     * @param         $id
     *
     * @return \think\response\Json
     */
    public function deleteProduct(Request $request, $id)
    {
        try {
            $this->transhipmentOrderProductsModel->deleteBy($id);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 预分仓
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function subOrders(Request $request)
    {
        try {
            $data = $request->param();
            if (!isset($data['suborder'])) {
                throw new \Exception('请输入分仓信息', Code::FAILED);
            }

            //判断出运单是否已确认分仓 已确认分仓则不可以再分仓
            $transOrder = $this->transhipmentOrdersModel->findBy($data['trans_order_id']);
            if (!$transOrder) {
                throw new \Exception('出运单不存在', Code::FAILED);
            }

            if ($transOrder->sub_confirm == 1) {
                throw new \Exception('出运单已分仓确认，不能修改', Code::FAILED);
            }


            DB::table('sub_orders')->where([
                'trans_order_id' => $data['trans_order_id']
            ])->delete();

            $list        = [];
            $totalNumber = 0;
            foreach ($data['suborder'] as $suborder) {

                foreach ($suborder['products'] as $val) {

                    $totalNumber += $val['number'];
                    $warehouse   = Warehouses::find($val['virtual_warehouse_id']);

                    $row    = [
                        'trans_order_id' => $data['trans_order_id'],
                        'trans_goods_id' => $val['trans_goods_id'],
                        'entity_warehouse_id' => $warehouse->parent_id,
                        'virtual_warehouse_id' => $val['virtual_warehouse_id'],
                        'number' => $val['number'],
                    ];
                    $list[] = $row;
                }
            }

            //            if ($transProduct->getAttr('trans_number') < $totalNumber) {
            //                return CatchResponse::fail('预分仓数量不能大于转运数量', Code::FAILED);
            //            }



            $this->subOrdersModel->saveAll($list);
            $this->transhipmentOrdersModel->updateBy($data['trans_order_id'], ['is_sub' => 1]);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 确认预分仓
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function confirmSubOrders(Request $request)
    {
        try {
            $data = $request->param();
            $this->subOrdersModel->startTrans();

            //判断出运单是否已确认分仓 已确认分仓则不可以再分仓
            $transOrder = $this->transhipmentOrdersModel->findBy($data['trans_order_id']);
            if (!$transOrder) {
                throw new \Exception('出运单不存在', Code::FAILED);
            }

            if ($transOrder->sub_confirm == 1) {
                throw new \Exception('出运单已分仓', Code::FAILED);
            }

            if ($transOrder->arrive_status == 0) {
                throw new \Exception('出运单未到仓确认，不能确认预分仓', Code::FAILED);
            }

            //更新出运单分仓状态
            $this->transhipmentOrdersModel->updateBy($data['trans_order_id'], ['sub_confirm' => 1]);

            //更新分仓商品数量
            if (isset($data['sub_order'])) {
                foreach ($data['sub_order'] as $val) {
                    $this->subOrdersModel->updateBy($val['sub_order_id'], ['number' => $val['number']]);
                }
            }

            //根据预分仓生成入库单
            $res = $this->subOrdersModel->where('trans_order_id', $data['trans_order_id'])
                ->select();
            foreach ($res as $val) {
                //查找实体仓信息
                $product             = $val->product->product->toArray();
                $product['number']   = $val['number'];
                $product['batch_no'] = $transOrder->batch_no;
                //查找是否有相同仓库的已分仓的，如果有则追加商品，无则创建新的入库单
                $subOrder = $this->subOrdersModel
                    ->where('trans_order_id', $data['trans_order_id'])
                    ->where('entity_warehouse_id', $val->entity_warehouse_id)
                    ->where('virtual_warehouse_id', $val->virtual_warehouse_id)
                    ->where('warehouse_order_id', '>', 0)
                    ->find();
                if ($subOrder) {
                    $orderId = $subOrder->warehouse_order_id;
                    $model = new WarehouseOrderProducts();
                    $product['warehouse_order_id'] = $orderId;
                    $row        = [
                        'warehouse_order_id' => $orderId,
                        'goods_id' => $product['goods_id'],
                        'goods_code' => $product['goods_code'],
                        'category_name' => $product['category_name'],
                        'goods_name' => $product['goods_name'],
                        'goods_name_en' => $product['goods_name_en'],
                        'goods_pic' => $product['goods_pic'],
                        'number' => $product['number'],
                        'type' => $product['type'],
                        'batch_no' => $product['batch_no']
                    ];
                    $model->createBy($row);
                    $this->warehouseOrdersModel->fixProduct($orderId);
                } else {
                    $orderData = [
                        'entity_warehouse_id' => $val->entity_warehouse_id,
                        'virtual_warehouse_id' => $val->virtual_warehouse_id,
                        'source' => 'purchase',
                        'audit_status' => 2,
                        'warehousing_status' => 1,
                        'warehousing_time' => date('Y-m-d H:i:s'),
                        'created_by' => $data['creator_id'],
                        'products' => [$product],
                    ];

                    //获取入库单id
                    $orderId = $this->warehouseOrdersModel->createWarehouseOrder($orderData);
                }


                $this->subOrdersModel->updateBy($val['id'], ['warehouse_order_id' => $orderId]);

                //如果是第一次采购的商品则创建一条记录到库存表
                $this->warehouseStockModel->increaseStock(
                    $val->entity_warehouse_id,
                    $val->virtual_warehouse_id,
                    $product['goods_code'],
                    $transOrder->batch_no,
                    $val->number,
                    $product['type'],
                    'TranshipmentSubOrder',
                    $orderId,
                );
            }


            $this->subOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->subOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改预分仓商品数量
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function modifySubNumber(Request $request)
    {
        $data = $request->param();

        $subOrder     = $this->subOrdersModel->findBy($data['sub_order_id']);
        $transProduct = $this->transhipmentOrderProductsModel->findBy($subOrder->trans_goods_id);

        $subNumber = $this->subOrdersModel->where('trans_order_id', $data['trans_order_id'])->sum('number');

        if ($transProduct->trans_number < $subNumber - $subOrder->number + $data['number']) {
            return CatchResponse::fail('预分仓数量不能大于转运数量', Code::FAILED);
        }
        $this->subOrdersModel->updateBy($data['sub_order_id'], ['number' => $data['number']]);
        return CatchResponse::success(true);
    }


    /**
     * 确认到仓
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function confirmArrive(Request $request)
    {
        try {
            $data = $request->param();
            $this->subOrdersModel->startTrans();
            $transOrder = $this->transhipmentOrdersModel->findBy($data['id']);

            if (!$transOrder) {
                throw new \Exception('出运单不存在', Code::FAILED);
            }

            if ($transOrder->arrive_status == 1) {
                throw new \Exception('出运单已到仓确认，不能重复操作', Code::FAILED);
            }

            if (!isset($data['products'])) {
                throw new \Exception('请选择要转运的商品', Code::FAILED);
            }

            //更新分仓商品数量
            // if (isset($data['sub_order'])) {
            //     foreach ($data['sub_order'] as $val) {
            //         $this->subOrdersModel->updateBy($val['sub_order_id'], ['number' => $val['number']]);
            //     }
            // }
            // //根据预分仓生成入库单
            // $res = $this->subOrdersModel->where('trans_order_id', $data['id'])
            //                             ->select();

            // foreach ($res as $val) {

            //     $product             = $val->product->product->toArray();
            //     //更新入库单状态
            //     $this->warehouseOrdersModel->updateBy($val->warehouse_order_id,
            //                                             ['warehousing_status' => 1, 'warehousing_time' => date('Y-m-d H:i:s')]
            //                                         );

            //     WarehouseOrderProducts::where('warehouse_order_id', $val->warehouse_order_id)
            //     ->where('goods_id', $product['goods_id'])
            //     ->update(['number' => $val->number]);

            //     //入库
            //     $this->warehouseStockModel->increaseStock($val->entity_warehouse_id,
            //                                               $val->virtual_warehouse_id,
            //                                               $product['goods_code'],
            //                                               $transOrder->batch_no,
            //                                               $val->number,
            //                                               $product['type']
            //     );
            // }

            foreach ($data['products'] as $val) {
                $this->transhipmentOrderProductsModel->updateBy($val['id'], $val);
            }

            $this->transhipmentOrdersModel->updateBy($data['id'], ['arrive_status' => 1]);
            $this->subOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->subOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 预分仓入库单列表
     *
     * @param Request $request
     * @param [int] $id
     * @return \think\response\Json
     */
    public function warehouseOrder(Request $request, $id)
    {

        $warehouseIds = $this->subOrdersModel->where('trans_order_id', $id)->group('')->column('warehouse_order_id');

        return CatchResponse::paginate($this->warehouseOrdersModel->whereIn('id', $warehouseIds)->paginate());
    }

    /**
     * 出运单列表所有出运单 
     */
    public function all(Request $request)
    {

        $list = $this->transhipmentOrdersModel->dataRange([], 'created_by')
            ->catchSearch()->group('cabinet_no')
            ->order('id', 'desc')->select();

        return CatchResponse::success($list);
    }
}
