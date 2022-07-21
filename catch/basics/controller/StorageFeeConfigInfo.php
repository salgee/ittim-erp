<?php

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\StorageFeeConfigInfo as storageFeeConfigInfoModel;

class StorageFeeConfigInfo extends CatchController
{
    protected $storageFeeConfigInfoModel;
    
    public function __construct(StorageFeeConfigInfoModel $storageFeeConfigInfoModel)
    {
        $this->storageFeeConfigInfoModel = $storageFeeConfigInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年02月24日 15:13
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->storageFeeConfigInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月24日 15:13
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->storageFeeConfigInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月24日 15:13
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->storageFeeConfigInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月24日 15:13
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->storageFeeConfigInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月24日 15:13
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->storageFeeConfigInfoModel->deleteBy($id));
    }
}