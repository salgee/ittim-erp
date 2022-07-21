<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 10:17:10
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-13 13:31:15
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\basics\request\LforwarderRequest;
use catchAdmin\basics\model\LfCurrency as LfCurrencyModel;
use catchAdmin\basics\model\Lforwarder as lforwarderModel;

class LforwarderFreight extends CatchController
{
    protected $lforwarderModel;
    protected $lfCurrencyModel;

    public function __construct(
        LforwarderModel $lforwarderModel,
        LfCurrencyModel $lfCurrencyModel
    ) {
        $this->lforwarderModel = $lforwarderModel;
        $this->lfCurrencyModel = $lfCurrencyModel;
    }

    /**
     * 列表
     * @time 2021年02月05日 10:17
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->lforwarderModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月05日 10:17
     * @param Request $request 
     */
    public function save(LforwarderRequest $request): \think\Response

    {
        $this->lfCurrencyModel->startTrans();
        $Lforwarder = $this->lforwarderModel->storeBy($request->post());
        if (!empty($request->param('account_data'))) {
            $lists = json_decode($request->param('account_data'), true);
            $arr = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['lforwarder_company_id'] = $Lforwarder;
            }
            if (!$this->lfCurrencyModel->insertAllBy($arr)) {
                $this->lfCurrencyModel->rollback();
            } else {
                $this->lfCurrencyModel->commit();
            }
            return CatchResponse::success($Lforwarder);
        }

        return CatchResponse::success($Lforwarder);
    }

    /**
     * 读取
     * @time 2021年02月05日 10:17
     * @param $id 
     */
    public function read($id): \think\Response
    {
        $data = [];
        $dataLfData = $this->lforwarderModel->findBy($id);
        $data = $dataLfData;

        $dataUserList = $this->lfCurrencyModel->where('lforwarder_company_id', '=', $id)->select();
        $data['userList'] = $dataUserList;

        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年02月05日 10:17
     * @param Request $request 
     * @param $id
     */
    public function update(LforwarderRequest $request, $id): \think\Response
    {
        $data = $request->post();
        $data['update_by'] = $data['creator_id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->lforwarderModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年02月05日 10:17
     * @param $id
     */
    public function delete($id): \think\Response
    {
        return CatchResponse::success($this->lforwarderModel->deleteBy($id));
    }

    /**
     * 批量禁用
     * @time 2020/09/16
     * @param Request $request  
     */
    public function disable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 2
                ];
                $list[] = $row;
            }
            $this->lforwarderModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 批量启用 enable
     */
    public function enable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            $this->lforwarderModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
}
