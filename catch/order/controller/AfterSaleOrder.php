<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-25 15:33:19
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-11-26 14:57:50
 * @Description:
 */

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\AfterSaleOrder as afterSaleOrderModel;

use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\order\model\OrderItemRecords;
use catcher\base\CatchRequest;
use catcher\Code;
use catchAdmin\order\model\OrderDeliverProducts;
use catchAdmin\order\model\OrderDeliver;
use catchAdmin\warehouse\model\WarehouseOrders as warehouseOrdersModel;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\product\model\Product as productModel;
use catchAdmin\report\model\ReportOrderAfterSale;
use catchAdmin\report\model\ReportOrder;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\product\model\Parts;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\product\model\ProductPrice;
use catchAdmin\delivery\common\DeliveryUpsCommon;
use catchAdmin\store\model\Platforms;
use catchAdmin\product\model\Product;
use think\facade\Filesystem;
use setasign\Fpdi\Tcpdf\Fpdi;
use ZipArchive;


class AfterSaleOrder extends CatchController
{
    protected $afterSaleOrderModel;
    protected $orderRecords;
    protected $orderBuyerRecords;
    protected $orderItemRecords;
    protected $orderDeliverProducts;
    protected $warehouseOrdersModel;
    protected $warehouseModel;

    public function __construct(
        AfterSaleOrderModel $afterSaleOrderModel,
        OrderRecords $orderRecords,
        OrderBuyerRecords $orderBuyerRecords,
        OrderItemRecords $orderItemRecords,
        OrderDeliverProducts $orderDeliverProducts,
        WarehouseOrdersModel $warehouseOrdersModel,
        Warehouses $warehouses
    ) {
        $this->afterSaleOrderModel = $afterSaleOrderModel;
        $this->orderRecords = $orderRecords;
        $this->orderBuyerRecords = $orderBuyerRecords;
        $this->orderItemRecords = $orderItemRecords;
        $this->orderDeliverProducts = $orderDeliverProducts;
        $this->warehouseOrdersModel = $warehouseOrdersModel;
        $this->warehouseModel = $warehouses;
    }

    /**
     * 列表
     * @time 2021年03月25日 15:33
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->afterSaleOrderModel->getList()->each(function (&$item) {
            $item['type_text'] = afterSaleOrderModel::$orderType[$item->type];
            $item['status_text'] = afterSaleOrderModel::$orderStatus[$item->status];
            $item['order_type_text'] = orderRecords::$orderTypesData[$item->order_type];
        }));
    }

    /**
     * 获取售后订单详情
     */
    public function addressInfo($id)
    {
        $data = $this->afterSaleOrderModel->findByInfo($id);
        if (!$data) {
            return CatchResponse::fail('售后信息不存在', Code::FAILED);
        }
        $data['sale_reason_text'] = afterSaleOrderModel::$orderSaleReason[$data->sale_reason];
        if (!$data) {
            return CatchResponse::fail('查询数据不存在', Code::FAILED);
        }
        $data['address'] = $this->orderBuyerRecords->where(['after_sale_id' => $id, 'order_record_id' => $data['order_id']])
            ->select();

        $where  = [];
        if ((int)$data['type'] == 3) {
            $where = [
                ['type', '=', '1'],
                ['after_order_id', '=', $id]
            ];
        } else {
            $where = [
                ['order_record_id', '=', $data['order_id']]
            ];
        }
        $data['product'] = $this->orderItemRecords->where($where)
            ->select()->each(function ($item) {
                $productData = productModel::where('id', $item['goods_id'])->find();
                $item['goods_pic'] = $productData['image_url'];
                $item['name'] = $productData['name_ch'] ?? $item['name'];
            });
        // 补货发货
        if ($data['type'] == 3) {
            $data['parts'] = $this->orderItemRecords->where(['after_order_id' => $id, 'type' => 1])->select()->each(function ($item) {
                if ($item['goods_type'] == 0) {
                    $item['goods_pic'] = productModel::where('id', $item['goods_id'])->value('image_url');
                } else {
                    $item['goods_pic'] = Parts::where('id', $item['goods_id'])->value('image_url');
                }
            });
        }
        $data['product_after_order'] = $this->orderDeliverProducts->where('after_order_id', $id)->select();
        $data['refund_logistics_text'] = Lforwarder::where('id', $data['refund_logistics'])->value('name');
        // 退货退款
        if ((int)$data['type'] == 2) {
            $orderBuyerRecords = new OrderBuyerRecords;
            // 用户地址，发货地址
            $data['address_drivery'] = $orderBuyerRecords->where([
                'order_record_id' => $data['order_id'],
                'is_disable' => 1,
                'type' => 0
            ])->select();
        }
        // 暂时使用 - 优化补充之前数据
        $this->orderRecords->updateAfterNum($data['order_id']);

        return CatchResponse::success($data);
    }

    /**
     * 售后订单审核
     */
    public function orderCheck(Request $request, $id)
    {

        $data = $request->post();
        if (!in_array($data['status'], [1, 2])) {
            return CatchResponse::fail('请检查审核状态', Code::FAILED);
        }

        $this->afterSaleOrderModel->startTrans();
        // 更新审核状态
        if (!$this->afterSaleOrderModel->updateBy($id, $data)) {
            $this->afterSaleOrderModel->rollback();
            return  CatchResponse::fail('操作失败', Code::FAILED);
        };

        $orderData = $this->afterSaleOrderModel->findBy($id);
        //  1-仅退款
        if ($orderData->type == Code::ORDER_SALES_REFUND && (int)$data['status'] == 1) {
            // 转换类型汉字
            $typeText = afterSaleOrderModel::$orderType[$orderData->type];
            // 写入报表订单售后费用信息
            $reportOrderAfterSale = new ReportOrderAfterSale;
            if (!$reportOrderAfterSale->saveAfterSale($orderData->platform_order_no, $typeText, $orderData->refund_amount, $id)) {
                $reportOrder = new ReportOrder;
                $reportOrder->saveOrder($orderData->platform_order_no);
                $reportOrderAfterSale->saveAfterSale($orderData->platform_order_no, $typeText, $orderData->refund_amount, $id);
            }
        }
        // 审核通过
        if ($data['status'] == 1) {
            // 5-修改地址
            if ($orderData['type'] == 5) {
                // 判断是否是发货后发起的地址修改
                if(OrderDeliver::where(['order_record_id'=> $orderData['order_id']])->whereNotIn('delivery_state', '1,6')->find()) {
                     // 更新地址表中数据-启用当前审核通过的
                    $this->orderBuyerRecords->where('after_sale_id', $id)->update(['is_disable' => 2]);
                }else{
                    // 更新地址表中数据-先全部修改为禁用
                    $this->orderBuyerRecords->where('order_record_id', $orderData['order_id'])->update(['is_disable' => 2]);
                    // 更新地址表中数据-启用当前审核通过的
                    $this->orderBuyerRecords->where('after_sale_id', $id)->update(['is_disable' => 1]);
                }

            }
            // 修改原始订单售后状态
            $this->orderRecords->updateBy($orderData['order_id'], ['after_sale_status' => 1, 'update_by' => $data['creator_id']]);
            $this->orderRecords->updateAfterNum($orderData['order_id']);
            $this->afterSaleOrderModel->commit();
            return CatchResponse::success('审核成功');
        } else {
            $this->orderRecords->updateAfterNum($orderData['order_id']);
            $this->afterSaleOrderModel->commit();
            return CatchResponse::success('审核成功');
        }
    }

    /**
     * 更新售后订单
     * @time 2021年03月25日 15:33
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $data = $request->post();
            unset($data['type']);
            $orderData = $this->afterSaleOrderModel->findBy($id);
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($orderData['status'] == 1) {
                return CatchResponse::fail('请检查订单状态', Code::FAILED);
            }
            $this->afterSaleOrderModel->startTrans();
            // 更新售后订单表
            if (!$this->afterSaleOrderModel->updateBy($id, $data)) {
                $this->afterSaleOrderModel->rollback();
                return CatchResponse::fail('售后订单修改失败', Code::FAILED);
            };
            // 召回
            if (isset($data['product']) && $request->param('type') == 3) {
                $list = [];
                foreach ($data['product'] as $key => $value) {
                    # code...
                    $row = [
                        'id' => $value['id'],
                        'quantity_purchased' => $value['quantity_purchased']
                    ];
                    $list[] = $row;
                }
                $this->orderItemRecords->saveAll($list);
            }
            if (isset($data['address']) && ($request->param('type') == 5 || $request->param('type') == 3)) {
                $address = $data['address'][0];
                // 更新关联地址表
                if (!$this->orderBuyerRecords->updateBy($address['id'], $address)) {
                    $this->afterSaleOrderModel->rollback();
                    return CatchResponse::fail('地址修改失败', Code::FAILED);
                };
            }
            // 当数据为退款退货，召回的时候
            if (isset($data['products']) && ($request->param('type') == 2 || $request->param('type') == 4)) {
                $listProduct = [];
                foreach ($data['products'] as $key => $value) {
                    # code...
                    $row = [
                        'id' => $value['id'],
                        'return_num' => $value['return_num'],
                        'after_amount' => $value['after_amount']
                    ];
                    $listProduct[] = $row;
                }
                if (!$this->orderDeliverProducts->saveAll($listProduct)) {
                    $this->afterSaleOrderModel->rollback();
                    return CatchResponse::fail('商品信息修改失败', Code::FAILED);
                };
            }
            $this->afterSaleOrderModel->commit();
            return CatchResponse::success('修改成功');
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 删除
     * @time 2021年03月25日 15:33
     * @param $id
     */
    public function delete($id)
    {
        $orderData = $this->afterSaleOrderModel->findBy($id);

        if (!$orderData) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        if (!in_array($orderData['status'], [Code::AFTER_STATUS_WAIT, Code::AFTER_STATUS_REFUSE])) {
            return CatchResponse::fail('订单已经审核通过不可删除', Code::FAILED);
        }
        // 删除关联地址表
        if ($orderData['type'] == Code::ORDER_SALES_MODIFY_ADDRESS) {
            $address =  OrderBuyerRecords::destroy(function ($query) use ($orderData) {
                $query->where('after_sale_id', '=', $orderData['id']);
            }, false);
        }
        // 修改售后订单操作的 发货订单关联商品
        $this->orderDeliverProducts->where('after_order_id', $id)->update(['return_num' => 0, 'after_amount' => 0]);
        $res = $this->afterSaleOrderModel->deleteBy($id, $force = false);
        // $force=true 物理删除   false 软删除
        $this->orderRecords->updateAfterNum($orderData['order_id']);
        return CatchResponse::success($res);
    }

    /**
     * 地址,召回
     * 设置修改金额
     */
    public function setModifyAmount(Request $request, $id)
    {

        $data = $request->post();

        $orderData = $this->afterSaleOrderModel->findBy($id);

        if (!$orderData) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        if ((int)$orderData['order_type'] !== 2 &&  (int)$orderData['order_type'] !== 3) {
            return CatchResponse::fail('订单类型不正确', Code::FAILED);
        }
        if ((int)$orderData['status'] !== 1) {
            return CatchResponse::fail('订单未审核通过不可录入', Code::FAILED);
        }

        if (!in_array($orderData['type'], [
            Code::ORDER_SALES_REFUNDALL, Code::ORDER_SALES_MODIFY_ADDRESS,
            Code::ORDER_SALES_RECALL, Code::ORDER_SALES_CPFR
        ])) {
            return CatchResponse::fail('订单类型不能够设置修改金额', Code::FAILED);
        }
        // 转换类型汉字
        $typeText = afterSaleOrderModel::$orderType[$orderData->type];

        // 写入报表订单售后费用信息
        $reportOrderAfterSale = new ReportOrderAfterSale;

        if (!$reportOrderAfterSale->saveAfterSale($orderData->platform_order_no, $typeText, $data['amount'], $id)) {
            $reportOrder = new ReportOrder;
            $reportOrder->saveOrder($orderData->platform_order_no);
            $reportOrderAfterSale->saveAfterSale($orderData->platform_order_no, $typeText, $data['amount'], $id);
        }

        return CatchResponse::success($this->afterSaleOrderModel->updateBy(
            $id,
            [
                'modify_amount' => $data['amount'],
                'updated_at' => time()
            ]
        ));
    }
    /**
     * 退货入库
     * @param $id  售后订单id
     */
    public function returnsWarehous(Request $request, $id)
    {
        try {
            $data = $request->post();
            $type = $data['type'];
            $orderData = $this->afterSaleOrderModel->findBy($id);
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($orderData['type'] !== 2) {
                return CatchResponse::fail('订单不状态不正确', Code::FAILED);
            }
            if ($orderData['is_warehousing'] == 1 && !empty($orderData['warehous_order_id'])) {
                return CatchResponse::fail('订单已入库请勿重复入库', Code::FAILED);
            }
            $data['created_by'] = $data['creator_id'];
            $data['source'] = 'returned'; // 退货
            // 仓库id
            $warehouses_id = $data['warehouses_id'];
            $warehouses = $this->warehouseModel->where('id', $warehouses_id)->find();
            if (!$warehouses) {
                return CatchResponse::fail('仓库不存在');
            }
            $goods_id =  $this->orderDeliverProducts->where(['after_order_id' => $id])->value('goods_id');
            // $porductData
            $porductData = ProductPrice::where(['product_id' => $goods_id, 'is_status' => 1, 'status' => 1])->find();
            // 采购基准价格 purchase_benchmark_price
            $porductDataFee = bcadd(bcadd($porductData['ocean_freight'], $porductData['purchase_benchmark_price'], 2), $porductData['all_tariff'], 2);
            // 计算退货入库金额 良品
            if ((int)$type == 1) {
                $data['fill_amount'] = bcsub(bcadd($orderData['refund_amount'], $orderData['logistics_fee'], 2), $porductDataFee);
            } else { // 残品
                $data['fill_amount'] = bcadd($orderData['refund_amount'], $orderData['logistics_fee'], 2);
            }
            // 实体仓库
            $data['entity_warehouse_id'] = $warehouses['parent_id'];
            // 虚拟仓库
            $data['virtual_warehouse_id'] = $warehouses_id;
            $data['audit_status'] = 2; // 审核通过
            $data['warehousing_status'] = 0; // 待入库
            $data['notes'] = '退货入库';
            // 获取售后订单商品
            $products = $this->orderDeliverProducts->where('after_order_id', $id)->select();
            if ($products) {
                foreach ($products as $val) {
                    $row = [
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'number' => $val['return_num'],
                        'type' => $val['type'],
                        'batch_no' => $val['batch_no']
                    ];
                    $data['products'][] = $row;
                }
            }
            // $fee = $this->goodsWarehousFee($data['type'], $orderData);
            $fee = 0;

            // 生成入库单
            $warehous_order_id = $this->warehouseOrdersModel->createWarehouseOrder($data);
            // 修改售后订单 入库状态
            $this->afterSaleOrderModel->updateBy($id, [
                'is_warehousing' => 1, 'goods_warehous_type' => $type,
                'warehous_order_id' => $warehous_order_id,
                'fill_amount' => $data['fill_amount']
            ]);

            // 报表
            $reportOrderAfterSale = new ReportOrderAfterSale;
            $reportOrderAfterSale->saveAfterSale($orderData->platform_order_no, '退款退货', $fee, $id);


            return CatchResponse::success('退货入库操作成功');
        } catch (\Exception $exception) {
            $this->afterSaleOrderModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 获取商品如理残品和良品不同费用计算
     * @param
     * */
    public function goodsWarehousFee($type, $id)
    {
        // 良品
        // 生成退货入库的金额
        // 若退货类型为良品，则售后产生费用=退款金额+退货物流单号对应的物流费用
        //（取自财务管理>物流应付账款查询中导入的物流单号对应的费用）-（采购基准价*数量+海运费*数量+关税*数量)；
        if ((int)$type == 1) {
        } else {
            //
            // 若退货类型为残品，则售后产生费用=退款金额+退货物流单号对应的物流费用（取自财务管理>物流应付账款查询中导入的物流单号对应的费用）
            // 写入报表订单售后费用信息

        }
    }
    /**
     * 召回入库
     */
    public function recallWarehous(Request $request, $id)
    {
        try {
            $data = $request->post();
            $type = $data['type'];
            if (!in_array($data['type'], [1, 2])) {
                return CatchResponse::fail('类型参数不正确', Code::FAILED);
            }
            $orderData = $this->afterSaleOrderModel->findBy($id);
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($orderData['is_warehousing'] == 1 && !empty($orderData['warehous_order_id'])) {
                return CatchResponse::fail('订单已入库请勿重复入库', Code::FAILED);
            }
            $data['created_by'] = $data['creator_id'];
            $data['source'] = 'returned'; // 退货
            // 仓库id
            $warehouses_id = $data['warehouses_id'];
            $warehouses = $this->warehouseModel->where('id', $warehouses_id)->find();
            if (!$warehouses) {
                return CatchResponse::fail('仓库不存在');
            }
            // $goods_id =  $this->orderItemRecords->where(['after_order_id' => $id])->value('goods_id');
            // $porductData
            // $porductData = ProductPrice::where(['product_id' => $goods_id, 'is_status' => 1, 'status' => 1])->find();
            // $porductDataFee = bcadd(bcadd($porductData['ocean_freight'], $porductData['benchmark_price'], 2), $porductData['order_operation_fee'], 2);
            // // 计算退货入库金额 良品
            // if ((int)$type == 1) {
            //     $data['fill_amount'] = bcsub(bcadd($orderData['modify_amount'], $orderData['logistics_fee'], 2), $porductDataFee);
            // } else { // 残品
            //     $data['fill_amount'] = bcadd($orderData['modify_amount'], $orderData['logistics_fee'], 2);
            // }
            // 实体仓库
            $data['entity_warehouse_id'] = $warehouses['parent_id'];
            // 虚拟仓库
            $data['virtual_warehouse_id'] = $warehouses_id;
            $data['audit_status'] = 2; // 审核通过
            $data['warehousing_status'] = 0; // 待入库
            $data['notes'] = '召回入库';
            // 获取售后订单商品
            $products = $this->orderDeliverProducts->where('after_order_id', $id)->select();
            if ($products) {
                foreach ($products as $val) {
                    $row = [
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'goods_pic' => $val['goods_pic'],
                        'number' => $val['return_num'],
                        'type' => $val['type'],
                        'batch_no' => $val['batch_no']
                    ];
                    $data['products'][] = $row;
                }
            }
            // 生成入库单
            $warehous_order_id = $this->warehouseOrdersModel->createWarehouseOrder($data);
            // 修改售后订单 入库状态
            $this->afterSaleOrderModel->updateBy($id, [
                'is_warehousing' => 1, 'goods_warehous_type' => $type,
                'warehous_order_id' => $warehous_order_id
                // 'fill_amount' => $data['fill_amount']
            ]);

            return CatchResponse::success('召回入库操作成功');
        } catch (\Exception $exception) {
            $this->afterSaleOrderModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 补货发货
     */
    public function reissueWarehous()
    {
        return CatchResponse::success('补货发货暂未实现');
    }

    /**
     * 获取当前虚拟仓库 上级实体仓库 以及残品仓库
     * @param $id 实体仓库id
     */
    public function warehousesSubclass(Request $request)
    {
        $shop_id = $request->param('shop_id');
        $storage_id = $request->param('storage_id');
        return CatchResponse::success($this->afterSaleOrderModel->warehousesSubclass($storage_id, $shop_id));
    }

    /**
     * 获取店铺下的实体仓库
     * @param $
     */
    public function warehouseShop(Request $request)
    {
        $shop_id = $request->param('shop_id');
        return CatchResponse::success($this->afterSaleOrderModel->warehouseShop($shop_id));
    }

    /**
     * 售后订单导出
     *
     * @time 2021年03月24日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $res = $this->afterSaleOrderModel->getExportList();

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->afterSaleOrderModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '售后订单导出');

        return  CatchResponse::success($url);
    }

    /**
     * 售后产生费用
     * @param id 售后订单id
     * @param warehouseFee 售后物流费用
     */
    public function warehouseFee(Request $request)
    {
        $id = $request->param('id');
        $warehouseFee = $request->param('warehouseFee');

        return $this->afterSaleOrderModel->warehouseFee($id, $warehouseFee);
    }

    /**
     * 获取物流单号
     *
     */
    public function deliveryUps(Request $request)
    {
        try {
            $data = $request->post();
            if (!isset($data['id'])) {
                return CatchResponse::fail('参数异常', Code::FAILED);
            }
            $id = $data['id'];
            // 退款退货订单
            if (!$afterData = $this->afterSaleOrderModel->where(['id' => $data['id'], 'type' => 2])->find()) {
                return CatchResponse::fail('请检查售后订单类型', Code::FAILED);
            }
            if (!$afterData['storage_id']) {
                return CatchResponse::fail('请选择售后订单退回仓库', Code::FAILED);
            }
            $orderData = [];
            if (!$product = $this->orderDeliverProducts->field('order_deliver_id,goods_group_id, goods_code,goods_group_name,number')
                ->where('after_order_id', $id)->find()) {
                return CatchResponse::fail('售后商品不存在', Code::FAILED);
            }
            // 获取发货单
            $orderDeliver = new OrderDeliver;
            $orderDelivery = $orderDeliver->field('id, order_record_id, platform_id,platform_no, 
                order_no,weight_AS_total,height_AS_total,length_AS_total, width_AS_total')
                ->where(['id' => $product['order_deliver_id']])->find();
            // 获取平台信息
            $paltforms = Platforms::where('id', $orderDelivery['platform_id'])->find();

            $orderBuyerRecords = new OrderBuyerRecords;
            // 用户地址，发货地址
            $orderBuyRecord = $orderBuyerRecords->field('address_street1 as street, address_postalcode as zipcode,
                address_cityname as city, address_stateorprovince as state, address_email as email, 
                address_phone as phone, address_name')
                ->where('order_record_id', $orderDelivery['order_record_id'])->find();
            // var_dump('$orderBuyRecord', $orderBuyRecord, $orderDelivery['order_record_id']);
            // exit;
            // 获取仓库地址  storage_id
            $warehouses = new Warehouses;
            $warehouse = $warehouses->field('street as address_street1, zipcode as address_postalcode,
                city as address_cityname, state as address_stateorprovince')
                ->where('id', $afterData['storage_id'])->find();

            $warehouse['address_name'] = $orderBuyRecord['address_name'];

            $warehouse['phone'] = ' ';
            $warehouse['address'] = ' ';

            $orderData = $orderDelivery;
            $orderData['product'] = $product;
            $orderData['paltforms'] = $paltforms;

            // 收货地址
            $orderData['orderBuyRecord'] = $warehouse;
            if (!empty($orderBuyRecord['phone'])) {
                $orderBuyRecord['phone'] = (substr($orderBuyRecord['phone'], 0, 15));
            }
            // 发货地址
            $orderData['warehouse'] = $orderBuyRecord;

            // $orderData->product->product = Product::where(['code' => $product['goods_code']])->find();
            // var_dump($orderData->product->product); exit;
            $ups = new DeliveryUpsCommon([]);
            $shippingCode = $ups->shippment($orderData, true);

            $order['logistics_no'] = $shippingCode['trackingNumber'];
            $order['refund_logistics'] = $data['shipping_id'];
            $order['shipping_name'] = $data['shipping_name'];
            $order['tracking_date'] = $shippingCode['tracking_date'];


            return  CatchResponse::success($this->afterSaleOrderModel->updateBy($data['id'], $order));

        } catch (\Exception $e) {
            return CatchResponse::fail($e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }

    }

    /**
     * 打印物流面单
     */
    public function printLableUps(Request $request)
    {
        $data = $request->param();
        if (!isset($data['after_id'])) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }
        // $html2pdf = new Html2Pdf('L', [380, 220]);

        $codes = $this->afterSaleOrderModel->where('id', $data['after_id'])->column('logistics_no, tracking_date');
        $res = [];
        foreach ($codes as $codeNew) {
            $datePath = '';
            $code = $codeNew['logistics_no'];
            if (!empty($codeNew['tracking_date'])) {
                $datePath = $codeNew['tracking_date']. '/';
            }

            $imagePath = Filesystem::disk('local')->path("upslabel/{$datePath}{$code}.png");
            $res[] = env('APP.DOMAIN') . '/images/upslabel/' . $datePath . $code . '.png';
            $img = new \Imagick($imagePath);
            $newpath = Filesystem::disk('local')->path("upslabel/{$datePath}{$code}.pdf");
            $img->setImageFormat('pdf');
            $img->writeImage($newpath);

            $pdfs[] = $newpath;
        }


        $pdf = new Fpdi();
        // 載入現在 PDF 檔案
        for ($i = 0; $i < count($pdfs); $i++) {
            $page_count = $pdf->setSourceFile($pdfs[$i]);
            for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {
                //一页一页的读取PDF，添加到新的PDF
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], $size);
                $pdf->useTemplate($templateId);
                $pdf->SetFont('Helvetica');
                $pdf->SetXY(5, 5);
            }
        }

        /**
         * 默认是I：在浏览器中打开，D：下载，F：在服务器生成pdf
         * S：只返回pdf的字符串，个人感觉无实在意义
         */
        if ($codes[0]['tracking_date']) {
            $datePath = $codes[0]['tracking_date'].'/';
        } else {
            $datePath = '';
        }

        $mergePdf = $codes[0]['logistics_no'] . ".pdf";
        // $mergePdf = "merge_"  . time() . ".pdf";
        $mergePdfPath = Filesystem::disk('local')->path('upslabel/' .$datePath. $mergePdf);

        $pdf->output($mergePdfPath, "F");

        $mergePdfUrl = env('APP.DOMAIN') . '/images/upslabel/' .$datePath. $mergePdf;

        // 結束 FPDI 剖析器
        // $pdf->closeParsers();
        return CatchResponse::success(['images' => $res, 'pdf' => $mergePdfUrl]);
    }

    /**
     * 获取物流信息详情
     */
    public function getUpsInfo(Request $request)
    {
        try {
            $shippingCode = $request->param('shipping_code', '');
            $ups = new DeliveryUpsCommon([]);
            $res = $ups->tracking($shippingCode);

            $result = $res;
            if (is_array($result)) {
                $result = $res[0];
            }

            return CatchResponse::success($res);
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage(), Code::FAILEDO);
        }
    }
}
