<?php

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\LogisticsFeeConfig as logisticsFeeConfigModel;
use catchAdmin\basics\model\LogisticsFeeConfigInfo;
use catchAdmin\basics\request\LogisticsFeeConfigRequest;

class LogisticsFeeConfig extends CatchController
{
    protected $logisticsFeeConfigModel;
    protected $logisticsFeeConfigInfo;
    
    public function __construct(LogisticsFeeConfigModel $logisticsFeeConfigModel, LogisticsFeeConfigInfo $logisticsFeeConfigInfo)
    {
        $this->logisticsFeeConfigModel = $logisticsFeeConfigModel;
        $this->logisticsFeeConfigInfo = $logisticsFeeConfigInfo;
    }
    
    /**
     * 列表
     * @time 2021年02月25日 11:00
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->logisticsFeeConfigModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月25日 11:00
     * @param Request $request 
     */
    public function save(LogisticsFeeConfigRequest $request) : \think\Response
    {
        try {
            $this->logisticsFeeConfigModel->startTrans();
            $data = $request->post();
            $data['is_status'] = 2; //默认禁用
            $data['is_update_price'] = 1;
            $res = $this->logisticsFeeConfigModel->storeBy($data);

            if (isset($data['logistics'])) {
                foreach ($data['logistics'] as $val) {
                    $row =  [
                        'logistics_fee_id' => $res,
                        'weight' => $val['weight'] ?? '',
                        'zone2' => $val['zone2'] ?? '',
                        'zone3' => $val['zone3'] ?? '',
                        'zone4' => $val['zone4'] ?? '',
                        'zone5' => $val['zone5'] ?? '',
                        'zone6' => $val['zone6'] ?? '',
                        'zone7' => $val['zone7'] ?? '',
                        'zone8' => $val['zone8'] ?? ''
                    ];
                    $list[] = $row;
                }
                if(!$this->logisticsFeeConfigInfo->saveAll($list)) {
                    $this->logisticsFeeConfigModel->rollback();
                }else{
                    $this->logisticsFeeConfigModel->commit();
                }
            }

            $this->logisticsFeeConfigModel->commit();
            return CatchResponse::success($res);

        } catch (\Exception $exception) {
            $this->logisticsFeeConfigModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 读取
     * @time 2021年02月25日 11:00
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        $data = $this->logisticsFeeConfigModel->findBy($id);
        $data['list'] = $this->logisticsFeeConfigInfo->where('logistics_fee_id', $id)->select();
        return CatchResponse::success($data);
    }
    
    /**
     * 更新
     * @time 2021年02月25日 11:00
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        try {
            $this->logisticsFeeConfigModel->startTrans();
            $data = $request->post();
            unset($data['created_at']);
            unset($data['creator_id']);
            $user = request()->user();
            $data['update_by'] = $user['id'];
            $data['is_update_price'] = 1;
            $res = $this->logisticsFeeConfigModel->updateBy($id, $data);

            $dataAll = $this->logisticsFeeConfigInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->logisticsFeeConfigInfo->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['logistics'])) {
                foreach ($data['logistics'] as $val) {
                    $row =  [
                        'logistics_fee_id' => $id,
                        'weight' => $val['weight'] ?? '',
                        'zone2' => $val['zone2'] ?? '',
                        'zone3' => $val['zone3'] ?? '',
                        'zone4' => $val['zone4'] ?? '',
                        'zone5' => $val['zone5'] ?? '',
                        'zone6' => $val['zone6'] ?? '',
                        'zone7' => $val['zone7'] ?? '',
                        'zone8' => $val['zone8'] ?? ''
                    ];
                    $list[] = $row;
                }
                if (!$this->logisticsFeeConfigInfo->insertAllBy($list)) {
                    $this->logisticsFeeConfigModel->rollback();
                } else {
                    $this->logisticsFeeConfigModel->commit();
                }
            }

            $this->logisticsFeeConfigModel->commit();
            return CatchResponse::success($res);

        } catch (\Exception $exception) {
            $this->logisticsFeeConfigModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 删除
     * @time 2021年02月25日 11:00
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->logisticsFeeConfigModel->deleteBy($id));
    }

    /**
     * 审核
     */
    public function verify(Request $request, $id): \think\Response
    {
        try {
            $status = $request->param('is_status');
            if (!in_array($status, [2, 1])) {
                return CatchResponse::fail('参数值错误', 301);
            }

            $this->logisticsFeeConfigModel->startTrans();

            $data = $this->logisticsFeeConfigModel->findBy($id);
            if (!empty($status)) {
                // 修改其他为禁用
                $this->logisticsFeeConfigModel->where('company_id', $data['company_id'])->update(['is_status' => 2]);
            }
            // 设置启用
            if (!$this->logisticsFeeConfigModel->updateBy($id, $request->param())) {
                $this->logisticsFeeConfigModel->rollback();
            };

            $this->logisticsFeeConfigModel->commit();
            return CatchResponse::success('操作成功');
        } catch (\Exception $exception) {
            $this->logisticsFeeConfigModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
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
            // 修改为启用
            $this->logisticsFeeConfigModel->saveAll($list);
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
                $data = $this->logisticsFeeConfigModel->findBy($val);
                // 先禁用
                $this->logisticsFeeConfigModel->isDsable($data['company_id'], 2);
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            // 批量启用
            $this->logisticsFeeConfigModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 导入模板
     * template
     */
    public function template() {

        return download(public_path().'template/logisticsFeeConfigImport.xlsx', 'logisticsFeeConfigImport.xlsx')->force(true);
    }
}