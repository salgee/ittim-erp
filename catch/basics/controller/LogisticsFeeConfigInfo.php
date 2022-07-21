<?php

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\LogisticsFeeConfigInfo as logisticsFeeConfigInfoModel;

class LogisticsFeeConfigInfo extends CatchController
{
    protected $logisticsFeeConfigInfoModel;
    
    public function __construct(LogisticsFeeConfigInfoModel $logisticsFeeConfigInfoModel)
    {
        $this->logisticsFeeConfigInfoModel = $logisticsFeeConfigInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年02月25日 11:05
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->logisticsFeeConfigInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月25日 11:05
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->logisticsFeeConfigInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月25日 11:05
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->logisticsFeeConfigInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月25日 11:05
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->logisticsFeeConfigInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月25日 11:05
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->logisticsFeeConfigInfoModel->deleteBy($id));
    }
}