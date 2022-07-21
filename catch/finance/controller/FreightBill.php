<?php
/**
 * 货代应付账款查询
 */

namespace catchAdmin\finance\controller;


use catchAdmin\finance\model\FreightBillOrder;
use catcher\base\CatchController;
use catchAdmin\finance\model\FreightBill as FreightBillModel;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;

class FreightBill extends CatchController {

    protected $freightBillModel;
    protected $freightBillOrderModel;

    public function __construct (FreightBillModel $freightBill,
        FreightBillOrder $freightBillOrder) {
        $this->freightBillModel      = $freightBill;
        $this->freightBillOrderModel = $freightBillOrder;
    }

    /**
     * 货代应付账款查询
     *
     * @return \think\response\Json
     */
    public function index () {
        return CatchResponse::paginate($this->freightBillModel->getList());
    }


    /**
     * 生产付款单
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function payOrder (CatchRequest $request) {
        try {
            $data = $request->param();
            if (!isset($data['ids'])) {
                return CatchResponse::fail('请选择账单', Code::FAILED);
            }

            $paymentNo = $this->freightBillOrderModel->createPaymentNo();
            foreach ($data['ids'] as $id) {
                $bill              = $this->freightBillModel->findBy($id);
                $lforwarderCompany = $bill->lforwarder_company;

                $this->freightBillModel->updateBy($id, [
                    'payment_no' => $paymentNo, 'pay_status' => 1
                ]);
            }

            $billOrder = [
                'payment_no' => $paymentNo,
                'lforwarder_company' => $lforwarderCompany,
                'creator_id' => $data['creator_id']
            ];
            $this->freightBillOrderModel->storeBy($billOrder);

        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 货代付款列表
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function orders (CatchRequest $request) {
        return CatchResponse::paginate($this->freightBillOrderModel->getList());
    }


    /**
     * 录入实际付款
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function  pay(CatchRequest $request) {
        try {
            $data = $request->param();

            $order = $this->freightBillOrderModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail( '付款单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail( '付款单未审核，不能录入付款金额', Code::FAILED);
            }

            if ($order->pay_status == 1) {
                return CatchResponse::fail( '付款单已付款，不能修改付款状态', Code::FAILED);
            }

            $data['pay_status'] = 1;
            $data['pay_time'] = date('Y-m-d H:i:s');
            $this->freightBillOrderModel->updateBy($data['id'], $data);
        }catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail( $exception->getMessage(), $code);
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
    public function changeAuditStatus (CatchRequest $request) {
        try {
            $data = $request->param();

            $order = $this->freightBillOrderModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('付款单不存在', Code::FAILED);
            }

            if ($order->audit_status == 1) {
                return CatchResponse::fail('付款单已审核，不能修改审核状态', Code::FAILED);
            }

            $this->freightBillOrderModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改付款单
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function freightOrderUpdate(CatchRequest $request) {
        try {
            $data = $request->param();

            $order = $this->freightBillOrderModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('付款单不存在', Code::FAILED);
            }

            if ($order->audit_status == 1) {
                return CatchResponse::fail('付款单已审核，不能修改审核', Code::FAILED);
            }

            $this->freightBillModel->where('payment_no', $order->payment_no)->update(['payment_no' => '', 'pay_status' => 0]);
            $this->freightBillModel->whereIn('id', $data['bill_ids'])->update(['payment_no' =>
                $order->payment_no, 'pay_status' => 1]);


        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

}