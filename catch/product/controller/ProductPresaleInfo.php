<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductPresaleInfo as productPresaleInfoModel;

class ProductPresaleInfo extends CatchController
{
    protected $productPresaleInfoModel;
    
    public function __construct(ProductPresaleInfoModel $productPresaleInfoModel)
    {
        $this->productPresaleInfoModel = $productPresaleInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年03月09日 14:57
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productPresaleInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月09日 14:57
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productPresaleInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年03月09日 14:57
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productPresaleInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月09日 14:57
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productPresaleInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年03月09日 14:57
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productPresaleInfoModel->deleteBy($id));
    }
}