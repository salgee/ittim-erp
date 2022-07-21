<?php


namespace catchAdmin\finance\controller;


use catcher\base\CatchController;
use catchAdmin\finance\model\PurchasePayment AS PurchasePaymentModel;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;

class PurchasePayment extends CatchController {

    protected $purchasePaymentModel;

    public function __construct(PurchasePaymentModel $purchasePayment)
    {
        $this->purchasePaymentModel = $purchasePayment;
    }

    /**
     * 采购付款单
     * @param  CatchAuth $auth
     * @return void
     */
    public function  index() {
        return CatchResponse::paginate($this->purchasePaymentModel->getList());
    }


    /**
     * 采购付款单支付
     *
     * @param CatchRequest $request
     * @return void
     */
    public function  pay(CatchRequest $request) {
        try {
            $data = $request->param();

            $order = $this->purchasePaymentModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail( '付款单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail( '付款单未审核，不能录入费用', Code::FAILED);
            }

            if ($order->pay_status == 1) {
                return CatchResponse::fail( '付款单已付款，不能修改付款状态', Code::FAILED);
            }

            $data['pay_status'] = 1;
            $data['pay_time'] = date('Y-m-d H:i:s');
            $this->purchasePaymentModel->updateBy($data['id'], $data);
        }catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail( $exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 修改审核状态
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeAuditStatus(CatchRequest $request) {
        try {
            $data = $request->param();

            $order = $this->purchasePaymentModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail( '付款单不存在', Code::FAILED);
            }

            if ($order->audit_status == 1) {
                return CatchResponse::fail( '付款单已审核，不能修改审核状态', Code::FAILED);
            }

            $this->purchasePaymentModel->updateBy($data['id'], $data);
        }catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail( $exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }
}