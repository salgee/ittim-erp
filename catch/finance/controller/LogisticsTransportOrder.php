<?php

namespace catchAdmin\finance\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\finance\model\LogisticsTransportOrder as logisticsTransportOrderModel;
use catcher\exceptions\FailedException;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\order\model\OrderDeliver;
use catchAdmin\order\model\OrderDeliverProducts;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\report\model\ReportOrder;
use catchAdmin\basics\model\Shop;
use catcher\Code;
use catchAdmin\finance\model\LogisticsPayawayOrder;
use catchAdmin\basics\model\Company;
use catchAdmin\store\model\Platforms;
use catchAdmin\order\model\AfterSaleOrder;

class LogisticsTransportOrder extends CatchController
{
    protected $logisticsTransportOrderModel;

    public function __construct(LogisticsTransportOrderModel $logisticsTransportOrderModel)
    {
        $this->logisticsTransportOrderModel = $logisticsTransportOrderModel;
    }

    /**
     * 列表
     * @time 2021年04月22日 15:47
     * @param Request $request
     */
    public function index(Request $request)
    {
        return CatchResponse::paginate(
            $this->logisticsTransportOrderModel->getList()
                ->each(function ($item) {
                    $item['shop_name'] = Shop::where('id', $item['shop_id'])->value('shop_name');
                })
        );
    }

    /**
     * 导入物流应付账款
     */
    public function importOrderNew(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        try {
            $file = $request->file();
            $data = $import->read($file['file']);
            $user = request()->user();
            $this->logisticsTransportOrderModel->startTrans();
            $dataObj = [];
            $rows = [];
            foreach ($data as $key => $value) {
                // 运单号码查重
                $countNum = $this->logisticsTransportOrderModel->where('transport_order_no', $value[0])->count();
                if ($countNum > 0) {
                    $dataObj['repeat'][] = $value[0];
                } else {
                    // 通过运单号查询发货订单
                    if (!$orderDeliver = OrderDeliver::where('shipping_code', $value[0])->find()) {
                        $dataObj['empty'][] = $value[0];
                    } else {
                        // 获取关联订单信息
                        $orderData = OrderRecords::where('id', $orderDeliver->order_record_id)->find();
                        // 获取物流公司id
                        $logisticsId = Lforwarder::where(['type' => 1, 'name' => $value[1]])->value('id');
                        // 获取关联 客户
                        $shopData = Shop::where('id', $orderData->shop_basics_id)->find();
                        $companyName = Company::where('id', $shopData->company_id)->value('name');
                        // var_dump($companyName, $shopData->company_id); exit;
                        $row = [
                            'company_id' => $shopData->company_id,
                            'company_name' => $companyName,
                            'send_at' => $orderDeliver->send_at, // 发货日期
                            'shop_name' => $shopData->shop_name, // 店铺名称
                            'shop_id' => $orderData->shop_basics_id, // 店铺ID
                            'platform' => $orderData->platform, // 平台名称
                            'platform_order_no' => $orderData->platform_no, // 原平台订单编号
                            'order_no' => $orderData->order_no, // 订单编号
                            'invoice_order_no' => $orderDeliver->invoice_no, //所属发货单
                            'logistics_id' => $logisticsId ?? 0, // 所属物流公司ID
                            'order_type' => $orderData->order_type, // 订单类型
                            'transport_order_no' => $value[0], // 运单号
                            'logistics_company' => $value[1], // 所属物流公司
                            'zone' => $value[2],
                            'basics_fee' => $value[3], // 基础运费
                            'fuel_surchage' => $value[4], // 燃油附加费 -
                            'residential_delivery' => $value[5], // 住宅地址附加费 -
                            'DAS_comm' => $value[6], // 偏远地区附加费
                            'DAS_extended_comm' => $value[7], // 超偏远 地区附加费
                            'AHS' => $value[8], // 额外处理费 -
                            'peak_AHS_charge' => $value[9], // 高峰期额外处理费 -
                            'address_correction' => $value[10], // 地址修正 -
                            'oversize_charge' => $value[11], // 超长超尺寸费 -
                            'peak_oversize_charge' => $value[12], // 高峰期超尺寸附加费 -
                            'weekday_delivery' => $value[13], // 工作日派送 -
                            'direct_signature' => $value[14], // 签名费 -
                            'unauthorized_OS' => $value[15], // 不可发 -
                            'peak_unauth_charge' => $value[16], // 高峰期 取消授权费用 -
                            'courier_pickup_charge' => $value[17], // 快递取件费 -
                            'print_return_label' => $value[18], // 打印快递面单费用 -
                            'return_pickup_fee' => $value[19], // 退件费 -
                            'total_fee' => $value[20], // 合计
                            'increment_fee' => $value[21], // 快递增值费 合计$value[20] - 基础运费$value[3]-  偏远地区附加费$value[6] - 超偏远 地区附加费$value[7]
                            'creator_id' => $user['id']
                        ];
                        $dataObj['success'][] = $orderData->order_no;
                        $rows[] = $row;
                        $reportOrder = new ReportOrder;
                        $dataReportOrder = $reportOrder->field('shipping_code, order_no, express_fee, express_surcharge_fee ')
                            ->where('order_no', $orderData->order_no)->find();
                        $dataUpdate = [];
                        $dataUpdate['shipping_company'] = $value[1];
                        if (isset($dataReportOrder->shipping_code) && $dataReportOrder->shipping_code != '') {
                            $dataUpdate['express_fee'] = bcadd($dataReportOrder->express_fee, $value[20], 5);
                            $dataUpdate['express_surcharge_fee'] = bcadd($dataReportOrder->express_surcharge_fee, $value[21], 5);
                            $dataUpdate['shipping_code'] = $dataReportOrder->shipping_code . ',' . $value[0];
                        } else {
                            $dataUpdate['express_fee'] = $value[20];
                            $dataUpdate['express_surcharge_fee'] = $value[21];
                            $dataUpdate['shipping_code'] = $value[0];
                        }
                        // 修改订单报表中的数据费
                        $reportOrder->where('order_no', $orderData->order_no)->update($dataUpdate);
                    }
                }
            }
            $this->logisticsTransportOrderModel->saveAll($rows);
            $this->logisticsTransportOrderModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->logisticsTransportOrderModel->rollback();
            throw new FailedException($e->getMessage());
        }
    }

    /**
     * 生成付款单
     */
    public function createdPayawayOrder(Request $request)
    {
        try {
            $data = $request->post();
            $user = request()->user();
            $count = $this->logisticsTransportOrderModel
                ->where(['logistics_id' => $data['logistics_id']])
                ->whereIn('id', array_unique($data['ids']))
                ->count();
            if ($count !== count($data['ids'])) {
                return CatchResponse::fail('请确认选择运单为同一物流公司', Code::FAILED);
            }
            $this->logisticsTransportOrderModel->startTrans();
            $ids = array_unique($data['ids']);
            $idsString = implode(",", $ids);
            // 获取运单总金额
            $total = $this->logisticsTransportOrderModel->whereIn('id', $idsString)
                ->sum('total_fee');
            // 生成运单信息
            $logisticsPayawayOrder = new LogisticsPayawayOrder;
            $dataPayaway = [
                'logistics_company' => $data['logistics_company'],
                'logistics_id' => $data['logistics_id'],
                'payaway_amount' => $total,
                'creator_id' => $user['id']
            ];
            if (!$res = $logisticsPayawayOrder->storeBy($dataPayaway)) {
                $this->logisticsTransportOrderModel->rollback();
                return CatchResponse::fail('生成付款单失败', Code::FAILED);
            }
            // 获取新生成运单号码
            $payaway_order_no = $logisticsPayawayOrder->where('id', $res)->value('payaway_order_no');
            // 更新物流付款单运单信息
            if (isset($data['ids'])) {
                $list = [];
                foreach ($data['ids'] as $id) {
                    $row = [
                        'id' => $id,
                        'payaway_order_id' => $res,
                        'payaway_order_no' => $payaway_order_no,
                        'creator_id' => $user['id'],
                        'status' => 2
                    ];
                    $list[] = $row;
                }
                $this->logisticsTransportOrderModel->saveAll($list);
            }
            $this->logisticsTransportOrderModel->commit();
            return CatchResponse::success('生成付款单成功');
        } catch (\Exception $e) {
            $this->logisticsTransportOrderModel->rollback();
            throw new FailedException($e->getMessage());
        }
    }
    /**
     * 导入模板下载
     * @param Request $request
     */
    public function template(Request $request)
    {
        return download(public_path() . 'template/logisticsWaybillImportNew.xlsx', 'logisticsWaybillImportNew.xlsx')->force(true);
    }
    /**
     * 导入物流应付账单
     */
    public function importOrder(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        try {
            $file = $request->file();
            $data = $import->read($file['file']);
            $user = request()->user();
            $newData = [];
            $dataObj = [];
            $rows = [];
            asort($data);
            // 获取所有的物流运单号集合
            $dataTrack = array_column($data, '0');
            // 去重运单号
            $dataTrackNew = array_unique($dataTrack);

            $accItem = [
                'AHC', 'AHG', 'AHL', 'AHW', 'AHS', 'ALP', 'CNS', 'HIR', 'IRW', 'LDC', 'LDR', 'LPR',
                'LPS', 'NPF', 'OFW', 'OSW', 'RDC', 'RDR', 'REP', 'RES', 'SAH', 'SLP', 'OVR', 'RPR'
            ];
            $infItem = [
                '3', 'AHG', 'AHL', 'AHW', 'FSC', 'HIR', 'LDC', 'LDR', 'LPR', 'LPS', 'RDC', 'RDR', 'REP',
                'SAH', 'SLP',
            ];
            $otherItem = [
                'AHC', 'CNS',  'NPF', 'HIR', 'RPR'
            ];
            $otherItemData = [];
            foreach ($dataTrackNew as $key => $val) {
                // 根据物流单号获取订单信息
                $orderDeliver = new OrderDeliver;
                // 售后订单费用同步 logistics_fee
                $orderDeliverProducts = new OrderDeliverProducts;
                $afterSaleOrder = new AfterSaleOrder;
                $idAfter = false;
                if (!$orderData = $orderDeliver->where('shipping_code', $val)->find()) {
                    if (!$idAfter = $afterSaleOrder->where(['logistics_no' => trim($val)])
                        ->whereIn('type', '2') // 2-退货退款 3-补货
                        ->value('id')) {
                        $dataObj['empty'][] = $val;
                        continue;
                    } else {
                        $ids = $orderDeliverProducts->where('after_order_id', $idAfter)->value('order_deliver_id');
                        if (!$orderData = $orderDeliver->where('id', $ids)->find()) {
                            $dataObj['empty'][] = $val;
                            continue;
                        }
                    }
                }

                $orderDeliverProducts = new OrderDeliverProducts;
                $orderProductData = $orderDeliverProducts->where('order_deliver_id', $orderData['id'])->find();
                $newData[$key]['category_name'] = $orderProductData['category_name'];
                if ($orderProductData['goods_group_name']) {
                    $newData[$key]['sku'] = $orderProductData['goods_group_name'];
                } else {
                    $newData[$key]['sku'] = $orderProductData['goods_code'];
                }
                $newData[$key]['transport_order_no'] = $val;
                $newData[$key]['company_id'] = $orderData['company_id'];
                $newData[$key]['company_name'] = Company::where('id', $orderData['company_id'])->value('name');
                $newData[$key]['logistics_company'] = $orderData['shipping_name'];
                $newData[$key]['logistics_id'] = Lforwarder::where(['type' => 1, 'name' => $orderData['shipping_name']])->value('id') ?? 0;
                $newData[$key]['invoice_order_no'] = $orderData['invoice_no'];
                $newData[$key]['send_at'] = $orderData['send_at'];
                $newData[$key]['order_no'] = $orderData['order_no'];
                $newData[$key]['order_type'] = $orderData['order_type'];
                $newData[$key]['platform_order_no'] = $orderData['platform_no'];
                $newData[$key]['platform'] = Platforms::where('id', $orderData['platform_id'])->value('name');
                $newData[$key]['shop_id'] = $orderData['shop_basics_id'];
                $newData[$key]['shop_name'] = Shop::where('id', $orderData['shop_basics_id'])->value('shop_name');
                $newData[$key]['actual_weight'] = 0;
                $newData[$key]['billed_weight'] = 0;
                $newData[$key]['zone'] = 0;
                $newData[$key]['FRT'] = 0;
                $newData[$key]['FSC'] = 0;
                $newData[$key]['other'] = array();
                foreach ($data as $key1 => $value) {
                    if ($val == $value[0]) {
                        // 实际重量
                        if ((float)trim($value[3]) > 0) {
                            $newData[$key]['actual_weight'] = trim($value[3]);
                        }
                        // 计费重量
                        if ((float)trim($value[4]) > 0) {
                            $newData[$key]['billed_weight'] = trim($value[4]);
                        }
                        // zone值
                        if ((int)(trim($value[5])) > 0) {
                            $newData[$key]['zone'] = trim($value[5]);
                        }
                        $item8 = strtoupper(trim(preg_replace('# #', '', $value[8])));
                        $item7 = strtoupper(trim($value[7]));
                        $item6 = strtoupper(trim($value[6]));
                        // 大写去空格 $value[6] 一级费用    $value[7] 二级费用  $value[8] 三级费用
                        if (strtoupper(trim($value[6])) == 'FRT') {
                            if ((int)$item7 == 3 && ($item8 == 'GROUNDCOMMERCIAL' || $item8 == 'GROUNDRESIDENTIAL')) {
                                $newData[$key]['FRT'] =  $value[9] ?? 0;
                            } else if((int)$item7 == 3 && ($item8 != 'GROUNDCOMMERCIAL' || $item8 != 'GROUNDRESIDENTIAL')){
                                $newData[$key]['other'][$item7.$item8] =  $value[9] ?? 0;
                            }else{
                                $newData[$key]['FRT'] =  $value[9] ?? 0;
                            }
                            // 总和计数
                            $otherItemData[$key]['FRT'.$item8] =  $value[9] ?? 0;
                        } elseif (in_array($item7, $accItem) && $item6 == 'ACC') {
                            $newData[$key]['ACC' . $item7] =  $value[9] ?? 0;
                            $otherItemData[$key]['ACC' . $item7.$item8] = ($value[9] ?? 0);
                        } elseif (in_array($item7, $infItem) && $item6 == 'INF') {
                            if ((int)$item7 == 3 && ($item8 == 'GROUNDCOMMERCIAL' || $item8 == 'GROUNDRESIDENTIAL')) {
                                $newData[$key]['INFNew'][$item8] =  ($value[9] ?? 0);
                            }else if((int)$item7 == 3 && ($item8 != 'GROUNDCOMMERCIAL' && $item8 != 'GROUNDRESIDENTIAL')){
                                $newData[$key]['other'][$item7.$item8] =  ($value[9] ?? 0);
                            }else{
                                $newData[$key]['INF' . $item7] =  ($value[9] ?? 0);
                            }
                            $otherItemData[$key]['INF' . $item7.$item8] =  ($value[9] ?? 0);
                        } elseif ($item6 == 'FSC') {
                            if((int)$item7 == 'FSC') {
                                $newData[$key]['FSCnew'][$item8] =  $value[9] ?? 0;
                                $otherItemData[$key]['FSC'. $item7.$item8] =  $value[9];
                            }else{
                                $newData[$key]['other'][$item7.$item8] =  $value[9] ?? 0;
                                $otherItemData[$key]['FSC'. $item7.$item8] =  $value[9];
                            }
                        }else{
                            $newData[$key]['other'][$item7.$item8] =  $value[9] ?? 0;
                            $otherItemData[$key][$item6 . $item7.$item8] =  $value[9] ?? 0;
                        }
                        $newData[$key]['other']['empty'] = 0;
                        if (in_array($item7, $otherItem)) {
                            $newData[$key]['other'][$item7.$item8] =  $value[9] ?? 0;
                            $otherItemData[$key][$item6 . $item7.$item8] =  $value[9] ?? 0;
                        }
                    }
                }
            }
            // var_dump('.....>>>>', $newData);
            // exit;
            // 运单生成大数据拼接
            $this->logisticsTransportOrderModel->startTrans();
            foreach ($newData as $key => $value) {
                // 运单号码查重
                $countNum = $this->logisticsTransportOrderModel->where('transport_order_no', $value['transport_order_no'])->count();
                if ($countNum > 0) {
                    $dataObj['repeat'][] = $value['transport_order_no'];
                } else {
                    $arrData = [];
                    if(!empty($value['INFNew'])) {
                        $value['INF'] = array_sum($value['INFNew']) ?? 0;
                    }else{
                        $value['INF'] = 0;
                    }
                    if(!empty($value['FSCnew'])) {
                        $value['FSC'] = array_sum($value['FSCnew']) ?? 0;
                    }else{
                        $value['FSC'] = 0;
                    }
                    
                    $toalFee = array_sum($otherItemData[$key]) ?? 0;
                    $arrData[0]['otherFee'] = array_sum($value['other']) ?? 0;
                    $arrData[0]['fscFee'] = bcadd($value['FSC'] ?? '0', ($value['INFFSC'] ?? '0'), 4);
                    $arrData[0]['addressFee'] = bcadd(($value['ACCRES'] ?? 0), ($value['INFREP'] ?? 0), 4);
                    $arrData[0]['sahFee'] = bcadd(($value['ACCSAH'] ?? 0), ($value['INFSAH'] ?? 0), 4);
                    $arrData[0]['slpFee'] = $value['ACCSLP'] ?? $value['INFSLP'] ?? 0;
                    $arrData[0]['irwFee'] =  $value['ACCIRW'] ?? 0;
                    $arrData[0]['ovrFee'] = $value['ACCOVR'] ?? 0;
                    $arrData[0]['ofwFee'] = $value['ACCOFW'] ?? $value['ACCOSW'] ?? 0;
                    $arrData[0]['alpFee'] = $value['ACCALP'] ?? 0;

                    $incrementFee = array_sum($arrData[0]);
                    $row = [
                        'actual_weight' => $value['actual_weight'],
                        'billed_weight' => $value['billed_weight'],
                        'category_name' => $value['category_name'],
                        'sku' => $value['sku'],
                        'company_id' => $value['company_id'],
                        'company_name' => $value['company_name'],
                        'send_at' => $value['send_at'], // 发货日期
                        'shop_name' => $value['shop_name'], // 店铺名称
                        'shop_id' => $value['shop_id'], // 店铺ID
                        'platform' => $value['platform'], // 平台名称
                        'platform_order_no' => $value['platform_order_no'], // 原平台订单编号
                        'order_no' => $value['order_no'], // 订单编号
                        'invoice_order_no' => $value['invoice_order_no'], //所属发货单
                        'logistics_id' => $value['logistics_id'] ?? 0, // 所属物流公司ID
                        'order_type' => $value['order_type'], // 订单类型
                        'transport_order_no' => $value['transport_order_no'], // 运单号
                        'logistics_company' => $value['logistics_company'], // 所属物流公司
                        'zone' => $value['zone'],
                        'basics_fee' => bcadd(($value['FRT'] ?? 0), ($value['INF'] ?? 0), 4), // 基础运费
                        'fuel_surchage' => $arrData[0]['fscFee'], // 燃油附加费 -
                        'residential_delivery' => $arrData[0]['addressFee'], // 住宅地址附加费 -
                        'DAS_comm' => bcadd(($value['ACCRDC'] ?? 0), ($value['INFRDC'] ?? 0), 4), // 偏远地区附加费-商业
                        'DAS_extended_comm' => bcadd(($value['ACCLDC'] ?? 0), ($value['INFLDC'] ?? 0), 4), // 超偏远 地区附加费-商业
                        'DAS_reis' => bcadd(($value['ACCRDR'] ?? 0), ($value['INFRDR'] ?? 0), 4), // 偏远地区附加费-住宅
                        'DAS_extended_reis' => bcadd(($value['ACCLDR'] ?? 0), ($value['INFLDR'] ?? 0), 4), // 超偏远 地区附加费-住宅
                        'AHS' => $value['ACCAHG'] ?? $value['INFAHG'] ?? $value['ACCAHL'] ?? $value['INFAHL'] ?? $value['ACCAHW'] ?? $value['INFAHW'] ?? $value['ACCAHS'] ?? 0, // 额外处理费 -
                        'peak_AHS_charge' => $arrData[0]['sahFee'], // 高峰期额外处理费 -
                        'address_correction' => $arrData[0]['irwFee'], // 地址修正 -
                        'oversize_charge' => $value['ACCLPR'] ?? $value['INFLPR'] ?? $value['ACCLPS'] ?? $value['INFLPS'] ?? 0, // 超长超尺寸费 -
                        'peak_oversize_charge' => $arrData[0]['slpFee'], // 高峰期超尺寸附加费 -
                        'weekday_delivery' => 0, // 工作日派送 -
                        'direct_signature' => 0, // 签名费 -
                        'unauthorized_OS' => $arrData[0]['ovrFee'], // 不可发 -
                        'peak_unauth_charge' => 0, // 高峰期 取消授权费用 -
                        'courier_pickup_charge' => $arrData[0]['ofwFee'], // 快递取件费 -
                        'print_return_label' => $arrData[0]['alpFee'], // 打印快递面单费用 -
                        'return_pickup_fee' => 0, // 退件费 -
                        'other_fee' => $arrData[0]['otherFee'], // 其他费用
                        'total_fee' => $toalFee, // 合计
                        'increment_fee' => $incrementFee, // 增值费用和其他金额一样
                        'creator_id' => $user['id']
                    ];
                    $dataObj['success'][] = $value['transport_order_no'];
                    $rows[] = $row;
                    $reportOrder = new ReportOrder;
                    $dataReportOrder = $reportOrder->field('shipping_code, order_no, express_fee, express_surcharge_fee ')
                        ->where('order_no', $value['order_no'])->find();
                    $dataUpdate = [];
                    $dataUpdate['shipping_company'] = $value['logistics_company'];
                    if (isset($dataReportOrder->shipping_code) && $dataReportOrder->shipping_code != '') {
                        $dataUpdate['express_fee'] = bcadd($dataReportOrder->express_fee, $toalFee, 5);
                        $dataUpdate['express_surcharge_fee'] = bcadd($dataReportOrder->express_surcharge_fee, $incrementFee, 5);
                        $dataUpdate['shipping_code'] = $dataReportOrder->shipping_code . ',' . $value['transport_order_no'];
                    } else {
                        $dataUpdate['express_fee'] = $toalFee;
                        $dataUpdate['express_surcharge_fee'] = $incrementFee;
                        $dataUpdate['shipping_code'] = $value['transport_order_no'];
                    }
                    // 售后订单费用同步 logistics_fee
                    $afterSaleOrder = new AfterSaleOrder;
                    if ($id = $afterSaleOrder->where(['logistics_no' => $value['transport_order_no']])
                        ->whereIn('type', '2') // 2-退货退款 3-补货
                        ->value('id')
                    ) {
                        $afterSaleOrder->where('id', $id)->update(['logistics_fee' => $toalFee]);
                    }
                    $orderDeliver = new OrderDeliver;
                    // 补货
                    if ($id3 = $orderDeliver->where(['order_type_source' => 2, 'shipping_code' => $value['transport_order_no']])
                        ->value('after_order_id')
                    ) {
                        $afterSaleOrder->where('id', $id3)->update(['logistics_fee' => $toalFee, 'logistics_no' => $value['transport_order_no']]);
                    }
                    // 修改订单报表中的数据费
                    $reportOrder->where('order_no', $value['order_no'])->update($dataUpdate);
                }
            }
            $this->logisticsTransportOrderModel->saveAll($rows);
            $this->logisticsTransportOrderModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->logisticsTransportOrderModel->rollback();
            throw new FailedException($e->getMessage());
        }
    }
}
