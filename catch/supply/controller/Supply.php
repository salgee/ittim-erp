<?php


namespace catchAdmin\supply\controller;

use catchAdmin\permissions\model\Users;
use catchAdmin\supply\request\SupplyCreateRequest;
use catchAdmin\supply\request\SupplyUpdateRequest;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\supply\model\Supply as SupplyModel;
use catchAdmin\supply\model\SupplyBankAccounts;
use catcher\CatchUpload;
use catcher\Code;
use think\facade\Db;
use think\Request;

class Supply extends CatchController {
    protected $supplyModel;
    protected $supplyBankAccountModel;

    public function __construct (SupplyModel $supplyModel, SupplyBankAccounts $supplyBankAccounts) {
        $this->supplyModel            = $supplyModel;
        $this->supplyBankAccountModel = $supplyBankAccounts;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index () {
        return CatchResponse::paginate($this->supplyModel->getList());
    }


    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save (SupplyCreateRequest $request): \think\Response {
        try {
            $this->supplyModel->startTrans();


            $data = $request->param();

            $data['created_by'] = $data['creator_id'];
            unset($data['id']);
            $res = $this->supplyModel->storeBy($data);

            $list = [];
            if (isset($data['account'])) {
                foreach ($data['account'] as $val) {
                    $row    = [
                        'supply_id' => $res,
                        'currency' => $val['currency'] ?? '',
                        'bank' => $val['bank'] ?? '',
                        'bank_account' => $val['bank_account'] ?? '',
                    ];
                    $list[] = $row;
                }
                //组装供应商银行账号信息


                $this->supplyBankAccountModel->saveAll($list);

            }


            $this->supplyModel->commit();
            return CatchResponse::success(['id' => $res]);
        } catch (\Exception $exception) {
            $this->supplyModel->rollback();
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
    public function update (SupplyUpdateRequest $request, $id): \think\Response {
        try {
            $this->supplyModel->startTrans();

            $data                 = $request->param();
            $data['updated_by']   = $data['creator_id'];
            $data['audit_status'] = 0;  //修改供应商 审核状态置为待提交

            $res = $this->supplyModel->updateBy($id, $data);

            Db::table('supply_bank_accounts')->where('supply_id', $id)->delete();

            $list = [];
            if (isset($data['account'])) {
                //组装供应商银行账号信息
                foreach ($data['account'] as $val) {
                    $row    = [
                        'supply_id' => $id,
                        'currency' => $val['currency'] ?? '',
                        'bank' => $val['bank'] ?? '',
                        'bank_account' => $val['bank_account'] ?? '',
                    ];
                    $list[] = $row;
                }
                $this->supplyBankAccountModel->saveAll($list);

            }

            $this->supplyModel->commit();
            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->supplyModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }

    }

    /**
     * 删除
     * @time 2021年01月23日 14:55
     *
     * @param $id
     */
    public function delete ($id): \think\Response {
        return CatchResponse::success($this->supplyModel->deleteBy($id));
    }


    /**
     * 提交审核
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function SubmitAudit (CatchRequest $request) {
        $data = $request->param();
        $ids  = $data['ids'];
        foreach ($ids as $id) {
            $supply = $this->supplyModel->findBy($id);
            if ($supply) {
                $this->supplyModel->updateBy($id, ['audit_status' => 1]);
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
    public function changeAuditStatus (CatchRequest $request) {
        try {
            $data = $request->param();

            $supply = $this->supplyModel->findBy($data['id']);
            if (!$supply) {
                return CatchResponse::fail('供应商不存在', Code::FAILED);
            }

            if ($supply->audit_status == 0) {
                return CatchResponse::fail('供应商未提交审核，请先提交审核', Code::FAILED);
            }

            if ($data['audit_status'] == 2) {
                $data['cooperation_status'] = 1;
            }
            $this->supplyModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 修改合作状态
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeCooperationStatus (CatchRequest $request) {
        $data = $request->param();
        $list = [];
        foreach ($data['ids'] as $val) {
            $row['id']                 = $val;
            $row['cooperation_status'] = $data['cooperation_status'];
            $row['updated_by']         = $data['creator_id'];
            $list[]                    = $row;
        }
        $this->supplyModel->saveAll($list);
        return CatchResponse::success(true);
    }

    /**
     * 设置模板
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    public function setContractTemplate (CatchRequest $request) {
        $data = $request->param();
        $list = [];
        foreach ($data['ids'] as $val) {
            $supply = $this->supplyModel->findBy($val);
            if ($supply) {
                $row['id']                = $val;
                $row['contract_template'] =  $data['contract_template'];
                $list[]                   = $row;

            }

        }
        $this->supplyModel->saveAll($list);
        return CatchResponse::success(true);
    }


    /**
     * 上传附件
     *
     * @param Request     $request
     * @param CatchUpload $upload
     *
     * @return \think\response\Json
     */
    public function upload (Request $request, CatchUpload $upload): \think\response\Json {
        // 获取表单上传文件
        $files = $request->file();
        return CatchResponse::success($upload->checkImages($files)
                                             ->setPath('business_license')
                                             ->multiUpload($files['files']));
    }

    /**
     *获取采购员列表
     *
     * @return \think\response\Json
     */
    public function buyers () {
        $res = Users::join('user_has_roles', 'user_has_roles.uid=users.id')
                    ->where('user_has_roles.role_id', 3)
                    ->column('users.username');

        return CatchResponse::success(['buyers' => $res]);
    }
}