<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 15:55:16
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-21 18:14:58
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
// use catcher\CatchAdmin;
use catcher\exceptions\FailedException;
// use CatchAdmin\basics\request\CompanyQuotaRequest;
use catchAdmin\basics\request\CompanyQuotaRequest;
use catchAdmin\basics\model\CompanyQuota as companyQuotaModel;
use catchAdmin\basics\model\Company as companyModel;
use catcher\Code;

class CompanyQuota extends CatchController
{
    protected $companyQuotaModel;
    protected $companyModel;
    
    public function __construct(CompanyQuotaModel $companyQuotaModel,
                                CompanyModel $companyModel)
    {
        $this->companyQuotaModel = $companyQuotaModel;
        $this->companyModel = $companyModel;
    }
    
    /**
     * 列表
     * @time 2021年02月06日 15:55
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        $id = $request->param('id');
        if (!isset($id)) {
            throw new FailedException('参数不正确');
        }
        $dataList = $this->companyQuotaModel->where('company_id', $id)->select();
        return CatchResponse::success($dataList);
    }
    
    /**
     * 保存信息
     * @time 2021年02月06日 15:55
     * @param Request $request 
     */
    public function save(CompanyQuotaRequest $request) : \think\Response
    {
        $data = $request->post();
        $user = $this->companyQuotaModel->where('company_id', $data['company_id'])->find();
        if($user) {
            if ($user['currency_id'] != $data['currency_id'] && $user['currency_name'] != $data['currency_name']) {
                return CatchResponse::fail('同一客户币别需一致', 102);
            }
        }
        $this->companyModel->startTrans();
        // $amount = bcadd($data['quota'], 0, 2);
        $amountAll = (float)$data['quota'];
        // var_dump($amountAll);exit;
        if (!$this->companyModel->findBy($data['company_id'])) {
            $this->companyModel->rollback();
            return CatchResponse::fail('添加失败', Code::FAILED);
        } else {
            // 修改额度
            $this->companyModel->where(['id' => $data['company_id']])->increment('amount', $amountAll);
            $this->companyModel->where(['id' => $data['company_id']])->increment('overage_amount', $amountAll);
            $this->companyModel->commit();
            $companyQuota = $this->companyQuotaModel->storeBy($request->post());
            return CatchResponse::success($companyQuota);
        }
        
    }
    
    /**
     * 读取
     * @time 2021年02月06日 15:55
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->companyQuotaModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月06日 15:55
     * @param Request $request 
     * @param $id
     */
    public function update(CompanyQuotaRequest $request, $id) : \think\Response
    {
        return CatchResponse::success($this->companyQuotaModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月06日 15:55
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        $this->companyModel->startTrans();
        $data = $this->companyQuotaModel->findBy($id);
        if (!$data) {
            $this->companyModel->rollback();
            return CatchResponse::fail('数据失败');
        }else{
            // 查看余额是否大于本条删除额度
            $balance = $this->companyModel->findBy($data['company_id']);
            if((float)$balance['overage_amount'] >= (float)$data['quota']) {
                // 修改客户表额度减去额度
                $this->companyModel->where(['id' => $data['company_id']])->decrement('amount', (float)$data['quota']);
                $this->companyModel->where(['id' => $data['company_id']])->decrement('overage_amount', (float)$data['quota']);
                $this->companyQuotaModel->deleteBy($id);
            }else{
                $this->companyModel->rollback();
                return CatchResponse::fail('余额不足不能删除本条额度记录', Code::FAILED);
            }
            $this->companyModel->commit();
        }
        return CatchResponse::success('删除成功');
    }
}