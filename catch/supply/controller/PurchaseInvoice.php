<?php
namespace catchAdmin\supply\controller;

use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\model\PurchaseInvoice AS PurchaseInvoiceModel;
use catcher\base\CatchController;
use catcher\CatchResponse;
use catcher\base\CatchRequest AS Request;
use catcher\Code;

class PurchaseInvoice extends CatchController {
    protected  $purchaseInvoiceModel;
    public function __construct (PurchaseInvoiceModel $purchaseInvoiceModel) {
        $this->purchaseInvoiceModel = $purchaseInvoiceModel;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index() {
        return CatchResponse::paginate($this->purchaseInvoiceModel->getList());
    }


        /**
     * 导出
     * @return \think\response\Json
     */
    public function export(Request $request) {

        $data = $request->post();

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->purchaseInvoiceModel->exportField();
        }


        $res = $this->purchaseInvoiceModel->catchSearch()->select()->toArray();

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '采购发票');
        return  CatchResponse::success($url);
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        try {
            $data = $request->param();
            $data['created_by'] = $data['creator_id'];
            $this->purchaseInvoiceModel->storeBy($data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 修改费用单
     * @param Request $request
     * @param         $id
     *
     * @return \think\response\Json
     */
    public function update(Request $request, $id) {
        try {
            $data = $request->param();
            $data['updated_by'] = $data['creator_id'];
            //根据提单号获取柜号及出运单号

            $this->purchaseInvoiceModel->updateBy($id, $data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 删除
     * @time 2021年01月23日 14:55
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->purchaseInvoiceModel->deleteBy($id));
    }
}