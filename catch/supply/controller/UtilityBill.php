<?php

namespace catchAdmin\supply\controller;

use Carbon\Carbon;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\finance\model\FreightBill;
use catchAdmin\product\model\Category;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\model\TranshipmentOrders;
use catchAdmin\supply\model\UtilityBills;
use catcher\base\CatchController;
use catcher\CatchResponse;
use catcher\base\CatchRequest as Request;
use catcher\Code;

class UtilityBill extends CatchController
{
    protected $transhipmentOrdersModel;
    protected $utilityBillsModel;

    public function __construct(
        TranshipmentOrders $transhipmentOrders,
        UtilityBills $utilityBills
    ) {
        $this->transhipmentOrdersModel = $transhipmentOrders;
        $this->utilityBillsModel       = $utilityBills;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {
        return CatchResponse::paginate($this->utilityBillsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(Request $request): \think\Response
    {
        try {
            $data = $request->post();
            $data['created_by'] = $data['creator_id'];
            //根据提单号获取柜号及出运单号

            $transOrder = $this->transhipmentOrdersModel->where([
                'audit_status' => 1,
                'bill_id' => 0,
                'bl_no' => $data['bl_no']
            ])
                ->column('id, cabinet_no, shipment_date, loading_date, arrive_date, supply_id, destination_port, loading_date');

            if (empty($transOrder)) {
                return CatchResponse::fail('提单号：' . $data['bl_no'] . "已开费用单，不能重复添加", Code::FAILED);
            }
            $cabinetNo   = '';
            $loadingDate = '';
            $arriveDate  = '';

            $tids = [];
            foreach ($transOrder as $val) {
                $tids[] = $val['id'];
                $cabinetNo   .= $val['cabinet_no'] . ' ';
                $loadingDate .= $val['loading_date'] . ' ';
                $arriveDate  .= $val['arrive_date'] . ' ';
            }


            //生成货代付款单
            $types = ['domestic', 'ocean', 'overseas'];
            $bill  = [
                'bl_no' => $data['bl_no'],
                'supply_id' => $transOrder[0]['supply_id'],
                'cabinet_no' => $cabinetNo,
                'loading_date' => $loadingDate,
                'shipment_date' => $transOrder[0]['shipment_date'],
                'arrive_date' => $arriveDate
            ];
            $this->updateFreightBill($bill, $transOrder, $data);


            $data['cabinet_no']     = $cabinetNo;
            $data['shipment_date']  = $transOrder[0]['shipment_date'];
            $data['destination_port'] = $transOrder[0]['destination_port'];
            $data['loading_date'] = $transOrder[0]['loading_date'];
            $data['overseas_lforwarder_id'] = $data['overseas_trans']['lforwarder_id'];
            $data['ocean_lforwarder_id'] = $data['ocean_shipping']['lforwarder_id'];
            $data['domestic_lforwarder_id'] = $data['domestic_trans']['lforwarder_id'];
            $data['domestic_trans'] = json_encode($data['domestic_trans']);
            $data['ocean_shipping'] = json_encode($data['ocean_shipping']);
            $data['overseas_trans'] = json_encode($data['overseas_trans']);
            $billId = $this->utilityBillsModel->storeBy($data);

            $this->transhipmentOrdersModel->whereIn('id', $tids)->update(['bill_id' => $billId]);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 修改费用单
     *
     * @param Request $request
     * @param         $id
     *
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->post();
            $data['updated_by'] = $data['creator_id'];
            //根据提单号获取柜号及出运单号
            $utilityBill = $this->utilityBillsModel->findBy($id);
            if (!$utilityBill) {
                return CatchResponse::fail('费用单不存在', Code::FAILED);
            }

            //判断货代账单是否已生成付款单 如果已生成  则不可以修改信息
            $fbCount = FreightBill::where([
                'bl_no' => $utilityBill->bl_no,
                'pay_status' => 1
            ])->count();

            if ($fbCount) {
                return CatchResponse::fail('货代账单已生成付款单，不可以修改信息', Code::FAILED);
            }



            $query = $this->transhipmentOrdersModel->where([
                'audit_status' => 1,
                'bl_no' => $data['bl_no']
            ]);

            if ($data['bl_no'] != $utilityBill->bl_no) {
                $query->where('bill_id', 0);
            }

            $transOrder
                = $query->column('id, cabinet_no,shipment_date, loading_date, arrive_date, supply_id, destination_port, loading_date');
            if (empty($transOrder)) {
                return CatchResponse::fail('提单号：' . $data['bl_no'] . "已开费用单，不能重复添加", Code::FAILED);
            }


            FreightBill::destroy(function ($query) use ($data) {
                $query->where('bl_no', $data['bl_no']);
            });


            $cabinetNo   = '';
            $loadingDate = '';
            $arriveDate  = '';
            $tids = [];
            foreach ($transOrder as $val) {
                $tids[] = $val['id'];
                $cabinetNo   .= $val['cabinet_no'] . ' ';
                $loadingDate .= $val['loading_date'] . ' ';
                $arriveDate  .= $val['arrive_date'] . ' ';
            }

            //生成货代付款单
            $bill  = [
                'bl_no' => $data['bl_no'],
                'supply_id' => $transOrder[0]['supply_id'],
                'cabinet_no' => $cabinetNo,
                'loading_date' => $loadingDate,
                'shipment_date' => $transOrder[0]['shipment_date'],
                'arrive_date' => $arriveDate
            ];

            $this->updateFreightBill($bill, $transOrder, $data);

            $data['cabinet_no']     = $cabinetNo;
            $data['shipment_date']  = $transOrder[0]['shipment_date'];
            $data['destination_port'] = $transOrder[0]['destination_port'];
            $data['loading_date'] = $transOrder[0]['loading_date'];
            $data['overseas_lforwarder_id'] = $data['overseas_trans']['lforwarder_id'];
            $data['ocean_lforwarder_id'] = $data['ocean_shipping']['lforwarder_id'];
            $data['domestic_lforwarder_id'] = $data['domestic_trans']['lforwarder_id'];
            $data['domestic_trans'] = json_encode($data['domestic_trans']);
            $data['ocean_shipping'] = json_encode($data['ocean_shipping']);
            $data['overseas_trans'] = json_encode($data['overseas_trans']);
            $this->utilityBillsModel->updateBy($id, $data);

            $this->transhipmentOrdersModel->where('bill_id', $id)->update(['bill_id' => 0]);
            $this->transhipmentOrdersModel->whereIn('id', $tids)->update(['bill_id' => $id]);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getLine() . $exception->getMessage(), $code);
        }
    }

    /**
     * 更新货代应付账款
     * @param  $bill
     * @param [array] $transOrder
     * @param [array] $data
     * @return void
     */
    public function updateFreightBill($bill, $transOrder, $data)
    {
        //生成货代付款单
        $types = ['domestic', 'ocean', 'overseas'];

        foreach ($types as $type) {
            $bill['domestic_fee'] = 0;
            $bill['ocean_fee'] = 0;
            $bill['overseas_fee'] = 0;
            $bill['type'] = $type;

            switch ($type) {
                case "domestic":
                    $bill['lforwarder_company'] = $data['domestic_trans']['lforwarder_company'];

                    $bill['bill_amount']
                        = $bill['domestic_fee'] = $data['domestic_trans']['amount'];
                    $lforwarder = Lforwarder::where('name', $bill['lforwarder_company'])
                        ->find();

                    $bill['estimated_pay_time'] = '';
                    foreach ($transOrder as $val) {
                        $bill['estimated_pay_time'] .= Carbon::parse($val['loading_date'])
                            ->addDays($lforwarder->settlement_cycle
                                ?? 0)
                            ->toDateString() . " ";
                    }


                    break;
                case "ocean":
                    $bill['lforwarder_company'] = $data['ocean_shipping']['lforwarder_company'];

                    $bill['bill_amount']
                        = $bill['ocean_fee'] = $data['ocean_shipping']['amount_rmb'];
                    $lforwarder
                        = Lforwarder::where('name', $bill['lforwarder_company'])
                        ->find();

                    $bill['estimated_pay_time'] = Carbon::parse($transOrder[0]['shipment_date'])
                        ->addDays($lforwarder->settlement_cycle ?? 0)
                        ->toDateString();
                    break;
                default:
                    $bill['lforwarder_company'] = $data['overseas_trans']['lforwarder_company'];
                    $bill['bill_amount']
                        =
                        $bill['overseas_fee'] = $data['overseas_trans']['other_fee'];

                    $lforwarder                 = Lforwarder::where('name', $bill['lforwarder_company'])
                        ->find();
                    $bill['estimated_pay_time'] = '';
                    foreach ($transOrder as $val) {
                        $bill['estimated_pay_time'] .= Carbon::parse($val['arrive_date'])
                            ->addDays($lforwarder->settlement_cycle
                                ?? 0)
                            ->toDateString() . " ";
                    }
            }

            FreightBill::create($bill);
        }
    }

    /**
     * 删除
     * @time 2021年01月23日 14:55
     *
     * @param $id
     */
    public function delete($id): \think\Response
    {
        //根据提单号获取柜号及出运单号
        $utilityBill = $this->utilityBillsModel->findBy($id);
        if (!$utilityBill) {
            return CatchResponse::fail('费用单不存在', Code::FAILED);
        }
        //判断货代账单是否已生成付款单 如果已生成  则不可以修改信息
        $fbCount = FreightBill::where([
            'bl_no' => $utilityBill->bl_no,
            'pay_status' => 1
        ])->count();

        if ($fbCount) {
            return CatchResponse::fail('货代账单已生成付款单，不可以删除', Code::FAILED);
        }

        $freightBill = new FreightBill;
        // 删除关联的 bl_no
        $ids = $freightBill->where('bl_no', $utilityBill->bl_no)->column('id');

        if ($this->utilityBillsModel->deleteBy($id)) {
            // 删除关联提单号的绑定的bill_id
            $this->transhipmentOrdersModel->where('bill_id', $id)
                ->update(['bill_id' => 0]);
            // 删除关联的货代应付账单
            $freightBill->deleteBy($ids);
        }
        return CatchResponse::success(true);
    }


    /**
     * 批量删除
     *
     */
    public function batchDelete(Request $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];
        $freightBill = new FreightBill;
        foreach ($ids as $id) {
            $bill = $this->utilityBillsModel->findBy($id);
            if (!$bill) {
                return CatchResponse::fail('费用单不存在', Code::FAILED);
            }
            $fbCount = FreightBill::where([
                'bl_no' => $bill->bl_no,
                'pay_status' => 1
            ])->count();

            if ($fbCount) {
                return CatchResponse::fail('货代账单已生成付款单，不可以删除', Code::FAILED);
            }
            // 删除关联的 bl_no
            $ids = $freightBill->where('bl_no', $bill->bl_no)->column('id');
            if ($this->utilityBillsModel->deleteBy($id)) {
                // 删除关联提单号的绑定的bill_id
                $this->transhipmentOrdersModel->where('bill_id', $id)
                    ->update(['bill_id' => 0]);
                // 删除关联的货代应付账单
                $freightBill->deleteBy($ids);
            }
        }
        return CatchResponse::success(true);
    }

    /**
     * 海运单
     *
     * @param Request $request
     * @param         $id
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function oceanShippingBill(Request $request, $id)
    {
        $res = $this->utilityBillsModel->oceanShippingBill($id);
        return CatchResponse::success($res);
    }

    /**
     * 托运单
     *
     * @param Request $request
     * @param         $id
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function domesticTransBill(Request $request, $id)
    {
        $res = $this->utilityBillsModel->domesticTransBill($id);
        return CatchResponse::success($res);
    }

    /**
     * 总单
     *
     * @param [type] $id
     * @return void
     */
    public function totalBill($id)
    {
        $res = $this->utilityBillsModel->totalBill($id);
        return CatchResponse::success($res);
    }

    /**
     * 总单导出
     *
     * @param  $id
     * @return void
     */
    public function totalBillExport(Request $request)
    {
        $data = $request->post();

        $exportField = [
            [
                'title' => '品名',
                'filed' => 'cates',
            ],
            [
                'title' => '提单号',
                'filed' => 'bl_no',
            ],
            [
                'title' => '柜号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => 'ETD',
                'filed' => 'shipment_date',
            ],
            [
                'title' => '海运费(USD)',
                'filed' => 'shipping_fee',
            ],
            [
                'title' => '税金(USD)',
                'filed' => 'tax_fee',
            ],
            [
                'title' => '总费用(USD)',
                'filed' => 'amount_usd',
            ],
            [
                'title' => '总费用(RMB)',
                'filed' => 'amount_rmb',
            ],
            [
                'title' => '货代(海运)',
                'filed' => 'shipment_company',
            ],
            [
                'title' => '国内路段合计（RMB）',
                'filed' => 'domestic_fee',
            ],
            [
                'title' => '货代(国内)',
                'filed' => 'domestic_company',
            ],
            [
                'title' => '国外路段合计(USD)',
                'filed' => 'overseas_fee',
            ],
            [
                'title' => '货代(国外)',
                'filed' => 'overseas_company',
            ],
        ];

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        }

        $res = $this->utilityBillsModel->totalBill($data['id'] ?? 0);
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        foreach ($res as &$val) {
            $val['cates'] = implode("-", $val['cates']);
            $val['shipping_fee'] = $val['ocean_shipping']['fee'];
            $val['tax_fee'] = $val['ocean_shipping']['tax_fee'];
            $val['amount_usd'] = $val['ocean_shipping']['amount_usd'];
            $val['amount_rmb'] = $val['ocean_shipping']['amount_rmb'];
            $val['shipment_company'] = $val['ocean_shipping']['lforwarder_company'];
            $val['domestic_fee'] =  $val['domestic_trans']['traile_fee'] +
                $val['domestic_trans']['detour_fee'] +
                $val['domestic_trans']['advance_fee'] +
                $val['domestic_trans']['declare_fee'];
            $val['domestic_company'] = $val['domestic_trans']['lforwarder_company'];

            $val['overseas_fee'] = $val['overseas_trans']['other_fee'];
            $val['overseas_company'] = $val['overseas_trans']['lforwarder_company'];
        }
        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '出货运费总表');
        return  CatchResponse::success($url);
    }


    /**
     * 托运单导出
     *
     * @param Request $request
     * @param  $id
     * @return void
     */
    public function domesticTransBillExport(Request $request)
    {
        $data = $request->post();
        $exportField = [
            [
                'title' => '品名',
                'filed' => 'name',
            ],
            [
                'title' => '提单号',
                'filed' => 'bl_no',
            ],
            [
                'title' => '柜号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => '装柜日期',
                'filed' => 'loading_date',
            ],
            [
                'title' => 'ETD',
                'filed' => 'shipment_date',
            ],
            [
                'title' => '拖车费',
                'filed' => 'traile_fee',
            ],
            [
                'title' => '绕路费',
                'filed' => 'detour_fee',
            ],
            [
                'title' => '提进费',
                'filed' => 'advance_fee',
            ],
            [
                'title' => '报关费',
                'filed' => 'declare_fee',
            ],
            [
                'title' => '合计',
                'filed' => 'amount',
            ],
            [
                'title' => '货代',
                'filed' => 'lforwarder_company',
            ],
        ];

        $res = $this->utilityBillsModel->domesticTransBill($data['id'] ?? 0);
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        foreach ($res as &$val) {
            $val['amount'] =  $val['traile_fee'] + $val['detour_fee'] + $val['advance_fee'] +  $val['declare_fee'];
        }
        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '拖车费');
        return  CatchResponse::success($url);
    }


    /**
     * 海运单导出
     *
     * @param Request $request
     * @param  $id
     * @return void
     */
    public function oceanShippingBillExport(Request $request)
    {
        $data = $request->post();
        $exportField = [
            [
                'title' => '品名',
                'filed' => 'name',
            ],
            [
                'title' => '提单号',
                'filed' => 'bl_no',
            ],
            [
                'title' => '柜号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => 'ETD',
                'filed' => 'shipment_date',
            ],
            [
                'title' => '海运费(USD)',
                'filed' => 'fee',
            ],
            [
                'title' => '税金（USD）',
                'filed' => 'tax_fee',
            ],
            [
                'title' => '总美金费用(USD)',
                'filed' => 'amount_usd',
            ],
            [
                'title' => '人民币费用(RMB)',
                'filed' => 'amount_rmb',
            ],
            [
                'title' => '货代',
                'filed' => 'lforwarder_company',
            ]
        ];

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        }

        $res = $this->utilityBillsModel->oceanShippingBill($data['id'] ?? 0);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '海运费');
        return  CatchResponse::success($url);
    }

    /**
     * 导出费用单
     */
    public function utilityBillExport(Request $request)
    {
        $data = $request->post();
        $exportField = [
            [
                'title' => '提单号',
                'filed' => 'bl_no',
            ],
            [
                'title' => '柜号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => 'ETD',
                'filed' => 'shipment_date',
            ],
            [
                'title' => '装柜日期',
                'filed' => 'loading_date',
            ],
            [
                'title' => '海运费(USD)',
                'filed' => 'fee_ocean',
            ],
            [
                'title' => '税金（USD）',
                'filed' => 'tax_fee',
            ],
            [
                'title' => '总美金费用(USD)',
                'filed' => 'amount_usd',
            ],
            [
                'title' => '人民币费用(RMB)',
                'filed' => 'amount_rmb',
            ],
            [
                'title' => '国内陆运费总和',
                'filed' => 'domestic_trans_amount',
            ],
            [
                'title' => '国外陆运费',
                'filed' => 'overseas_trans_amount',
            ],
            [
                'title' => '目的港',
                'filed' => 'destination_port_text',
            ],
            [
                'title' => '海运(货代)',
                'filed' => 'ocean_lforwarder',
            ],
            [
                'title' => '国内(货代)',
                'filed' => 'domestic_lforwarder',
            ],
            [
                'title' => '国外(货代)',
                'filed' => 'overseas_lforwarder',
            ],
        ];

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        }

        $res = $this->utilityBillsModel->getList();

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '货代费用');
        return  CatchResponse::success($url);
    }

}
