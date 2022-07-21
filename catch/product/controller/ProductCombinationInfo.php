<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductCombinationInfo as productCombinationInfoModel;

class ProductCombinationInfo extends CatchController
{
    protected $productCombinationInfoModel;
    
    public function __construct(ProductCombinationInfoModel $productCombinationInfoModel)
    {
        $this->productCombinationInfoModel = $productCombinationInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年03月08日 10:58
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productCombinationInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月08日 10:58
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productCombinationInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年03月08日 10:58
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productCombinationInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月08日 10:58
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productCombinationInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年03月08日 10:58
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productCombinationInfoModel->deleteBy($id));
    }
}