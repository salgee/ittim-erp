<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductGroup as productGroupModel;

class ProductGroup extends CatchController
{
    protected $productGroupModel;
    
    public function __construct(ProductGroupModel $productGroupModel)
    {
        $this->productGroupModel = $productGroupModel;
    }
    
    /**
     * 列表
     * @time 2021年02月09日 16:34
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productGroupModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月09日 16:34
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productGroupModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月09日 16:34
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productGroupModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月09日 16:34
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productGroupModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月09日 16:34
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productGroupModel->deleteBy($id));
    }
}