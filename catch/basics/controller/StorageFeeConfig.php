<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-24 15:03:47
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-22 19:24:53
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\StorageFeeConfig as storageFeeConfigModel;
use catchAdmin\basics\model\StorageFeeConfigInfo;
use think\facade\Db;

class StorageFeeConfig extends CatchController
{
    protected $storageFeeConfigModel;
    protected $storageFeeConfigInfo;
    
    public function __construct(StorageFeeConfigModel $storageFeeConfigModel, StorageFeeConfigInfo $storageFeeConfigInfo)
    {
        $this->storageFeeConfigModel = $storageFeeConfigModel;
        $this->storageFeeConfigInfo = $storageFeeConfigInfo;
    }
    
    /**
     * 列表
     * @time 2021年02月24日 15:03
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->storageFeeConfigModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月24日 15:03
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        try{
            $this->storageFeeConfigModel->startTrans();
            $data = $request->post();
            $data['is_status'] = 2;
            $res = $this->storageFeeConfigModel->storeBy($data);
            if(isset($data['storage'])) {
                foreach ($data['storage'] as $val) {
                    $row =  [
                        'fee_config_id' => $res,
                        'min_days' => $val['min_days'] ?? '',
                        'max_days' => $val['max_days'] ?? '',
                        'fee' => $val['fee'] ?? '',
                        'warehouse_id' =>  $val['warehouse_id'] ?? '',
                    ];
                    $list[] = $row;
                }
                $this->storageFeeConfigInfo->saveAll($list);
            }
            $this->storageFeeConfigModel->commit();
            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->storageFeeConfigModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 读取
     * @time 2021年02月24日 15:03
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        $data = $this->storageFeeConfigModel->findBy($id);
        $data['list'] = $this->storageFeeConfigInfo->where('fee_config_id', $id)->select();
        return CatchResponse::success($data);
    }
    
    /**
     * 更新
     * @time 2021年02月24日 15:03
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        try{
            $this->storageFeeConfigModel->startTrans();
            $data = $request->post();
            $user = request()->user();
            $data['update_by'] = $user['id'];
            $res = $this->storageFeeConfigModel->updateBy($id, $data);
            $dataAll = $this->storageFeeConfigInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->storageFeeConfigInfo->deleteBy($dataAll,  $force = true);
            }
            if(isset($data['storage'])) {
                foreach ($data['storage'] as $val) {
                    $row =  [
                        'fee_config_id' => $id,
                        'min_days' => $val['min_days'] ?? '',
                        'max_days' => $val['max_days'] ?? '',
                        'fee' => $val['fee'] ?? '',
                        'warehouse_id' =>  $val['warehouse_id'] ?? '',
                    ];
                    $list[] = $row;
                }
                $this->storageFeeConfigInfo->insertAllBy($list);
            }
            $this->storageFeeConfigModel->commit();
            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->storageFeeConfigModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 删除
     * @time 2021年02月24日 15:03
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->storageFeeConfigModel->deleteBy($id));
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
            
            $this->storageFeeConfigModel->startTrans();

            $data = $this->storageFeeConfigModel->findBy($id);
            if(!empty($status)) {
                // 修改其他为禁用
                $this->storageFeeConfigModel->where('company_id', $data['company_id'])->update(['is_status' => 2]);
            }
            // 设置启用
            if(!$this->storageFeeConfigModel->updateBy($id, $request->param())){
                $this->storageFeeConfigModel->rollback();
            };

            $this->storageFeeConfigModel->commit();
            return CatchResponse::success('操作成功');

        } catch (\Exception $exception) {
            $this->storageFeeConfigModel->rollback();
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
            $this->storageFeeConfigModel->saveAll($list);
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
                $data = $this->storageFeeConfigModel->findBy($val);
                // 先禁用
                $this->storageFeeConfigModel->isDsable($data['company_id'], 2);
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            // 批量启用
            $this->storageFeeConfigModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
}