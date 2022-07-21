<?php
/**
 *卸柜入库管理
 */

namespace catchAdmin\settlement\controller;

use catchAdmin\settlement\model\DischargeCargoFee;
use catchAdmin\supply\excel\CommonExport;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\Exception;

class DischargeCargo extends CatchController {

    protected $dischargeCargoFeeModel;

    public function __construct (DischargeCargoFee $dischargeCargoFee) {
        $this->dischargeCargoFeeModel = $dischargeCargoFee;
    }


    /**
     * 列表
     * @return \think\response\Json
     */
    public function index () {

        return CatchResponse::paginate($this->dischargeCargoFeeModel->field('discharge_cargo_fee.*')->catchSearch()->paginate());
    }

    /**
     * 导出
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function export(CatchRequest $request) {
        $res = $this->dischargeCargoFeeModel->catchSearch()->select()->toArray();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->dischargeCargoFeeModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '卸柜入库费管理');
        return  CatchResponse::success($url);
    }

    /**
     * 新增
     *
     * @param CatchRequest $request
     * @return void
     */
    public function save(CatchRequest $request) {
        try {
            $data = $request->param();
            $data['created_by'] = $data['creator_id'];
            $this->dischargeCargoFeeModel->storeBy($data);
            return CatchResponse::success(true);
        }catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail( $exception->getMessage(), $code);
        }
    }

    /**
     * 详情
     *
     * @param $id
     *
     * @return \think\response\Json
     */
    public function read ($id) {

        $fee = $this->dischargeCargoFeeModel->find($id);
        if (!$fee) {
            return CatchResponse::fail('费用不存在', Code::FAILED);
        }

        return CatchResponse::success($fee);
    }


    /**
     * 更新
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function update (CatchRequest $request, $id): \think\Response {
        try {
            $data               = $request->param();
            $data['updated_by'] = $data['creator_id'];
            $res = $this->dischargeCargoFeeModel->findBy($id);
            if ($res->status == 1) {
                return CatchResponse::fail('已确认，不能修改', Code::FAILED);
            }

            $this->dischargeCargoFeeModel->updateBy($id, $data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }


    /**
     * 删除
     *
     * @param $id
     * @return \think\response\Json
     */
    public function delete ($id): \think\Response {
        $fee = $this->dischargeCargoFeeModel->findBy($id);
        if ($fee->status == 1) {
            return CatchResponse::fail('已确认，不能删除', Code::FAILED);
        }
        return CatchResponse::success($this->dischargeCargoFeeModel->deleteBy($id));
    }


    /**
     * 卸柜入库费确认
     *
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function confirm(CatchRequest $request) {
        try {
            $data = $request->param();
            $ids = $data['ids'];
            foreach ($ids AS $id) {
                $fee = $this->dischargeCargoFeeModel->findBy($id);
                if ($fee && $fee->status == 1) {
                    return CatchResponse::fail('已确认，不能重复确认', Code::FAILED);
                }

                $this->dischargeCargoFeeModel->updateBy($id, ['status'=> 1]);
            }
        }catch (Exception $exception) {
            return CatchResponse::fail($exception->getMessage(), Code::FAILED);
        }

        return CatchResponse::success(true);
    }
}