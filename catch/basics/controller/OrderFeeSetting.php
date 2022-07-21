<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-07 14:29:52
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-26 16:40:48
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\basics\request\OrderFeeSettingRequest;
use think\Db;
use catchAdmin\basics\model\OrderFeeSetting as orderFeeSettingModel;

class OrderFeeSetting extends CatchController
{
    protected $orderFeeSettingModel;
    
    public function __construct(OrderFeeSettingModel $orderFeeSettingModel)
    {
        $this->orderFeeSettingModel = $orderFeeSettingModel;
    }
    
    /**
     * 列表
     * @time 2021年02月07日 14:29
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderFeeSettingModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月07日 14:29
     * @param Request $request 
     */
    public function save(OrderFeeSettingRequest $request) : \think\Response
    {
        $data =$request->post();
        $this->orderFeeSettingModel->startTrans();
        $data['is_update_price'] = 1;
        // 保存设置父级
        $idCompany = $this->orderFeeSettingModel->storeBy($data);
        if (!empty($request->param('dataJson'))) {
            $lists = json_decode($request->param('dataJson'), true);
            $arr = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['parent_id'] = $idCompany;
                $arr[$key]['is_status'] = 1;
            }
            // 保存自己配置
            if (!$this->orderFeeSettingModel->insertAllBy($arr)) {
                $this->orderFeeSettingModel->rollback();
            } else {
                $this->orderFeeSettingModel->commit();
            }
            return CatchResponse::success('添加成功');
        }else{
            return CatchResponse::success('参数不正确', 103);
        }
        return CatchResponse::success($idCompany);
        // return CatchResponse::success($this->orderFeeSettingModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月07日 14:29
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        $data = [];
        $field = ['id, company_id, name, is_status, parent_id, created_at, updated_at'];
        $fieldChild = ['id,parent_id, fee, max_weight, min_weight, created_at, updated_at'];
        $data = $this->orderFeeSettingModel->findBy($id, $field);

        $data['children'] = $this->orderFeeSettingModel->field($fieldChild)->where('parent_id', $data['id'])->select();
        
        return CatchResponse::success($data);
    }
    
    /**
     * 更新
     * @time 2021年02月07日 14:29
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        $this->orderFeeSettingModel->startTrans();
        $dataArr = $request->post();
        $user = request()->user();
        $dataParent = [
            'company_id' => $dataArr['company_id'],
            'name' => $dataArr['name'],
            'parent_id' => 0,
            'update_by' => $user['id'],
            'updated_at' => time(),
            'is_update_price' => 1
        ];
        $this->orderFeeSettingModel->updateBy($id, $dataParent);
        if (!empty($request->param('dataJson'))) {
            $dataAll = $this->orderFeeSettingModel->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->orderFeeSettingModel->deleteBy($dataAll,  $force = true);
            }
            $lists = json_decode($request->param('dataJson'), true);
            $arr = [];
            foreach ($lists as $key => $ids) {
                $arr[$key] = $ids;
                $arr[$key]['parent_id'] = $id;
                $arr[$key]['company_id'] = $dataArr['company_id'];
                $arr[$key]['is_status'] = 1;
            }
            if (!$this->orderFeeSettingModel->insertAllBy($arr)) {
                $this->orderFeeSettingModel->rollback();
            } else {
                $this->orderFeeSettingModel->commit();
            }
            return CatchResponse::success('编辑成功');
        }
        return CatchResponse::success('编辑成功');
    }
    
    /**
     * 删除
     * @time 2021年02月07日 14:29
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        // 物理删除
        return CatchResponse::success($this->orderFeeSettingModel->deleteBy($id, $force = true));
    }

    /**
     * 状态更新
     * @time 2020/09/16
     * @param Request $request  
     */
    public function verify(Request $request, $id): \think\Response
    {
        $is_status = $request->param('is_status');
        if (!isset($is_status)) {
            throw new FailedException('参数不正确');
        }
        if (!in_array($request->post('is_status'), [2, 1])) {
            throw new FailedException('参数不正确');
        }
        $dataUser = $this->orderFeeSettingModel->findBy($id);
        // 判断是否存在该条信息
        if(!$dataUser) {
            throw new FailedException('信息不存在');
        }else{
            if ((int) $is_status == 1) {
                //修改其他状态为禁用
                $this->orderFeeSettingModel->where([['id','<>', $id], ['parent_id', '=', 0],['company_id', '=', $dataUser['company_id']]])
                    ->update([
                        'is_status' => 2
                    ]);
            }
        }
        return CatchResponse::success($this->orderFeeSettingModel->updateBy($id, $request->param()));
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
            $this->orderFeeSettingModel->saveAll($list);
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
                $data = $this->orderFeeSettingModel->findBy($val);
                // 先禁用
                $this->orderFeeSettingModel->isDsable($data['company_id'], 2);
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            // 批量启用
            $this->orderFeeSettingModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
}