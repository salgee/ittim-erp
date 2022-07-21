<?php

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\basics\request\SenderRequest;
use catchAdmin\basics\model\Sender as senderModel;

class Sender extends CatchController
{
    protected $senderModel;
    
    public function __construct(SenderModel $senderModel)
    {
        $this->senderModel = $senderModel;
    }
    
    /**
     * 列表
     * @time 2021年02月04日 11:22
     * @param Request $request 
     */
    public function index() 
    {
        return CatchResponse::paginate($this->senderModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月04日 11:22
     * @param Request $request 
     */
    public function save(SenderRequest $request) : \think\Response
    {
        return CatchResponse::success($this->senderModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月04日 11:22
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->senderModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月04日 11:22
     * @param Request $request 
     * @param $id
     */
    public function update(SenderRequest $request, $id) : \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->senderModel->updateBy($id, $data));
    }
    
    /**
     * 删除
     * @time 2021年02月04日 11:22
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->senderModel->deleteBy($id));
    }
}