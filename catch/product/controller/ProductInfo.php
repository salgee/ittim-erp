<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductInfo as productInfoModel;

class ProductInfo extends CatchController
{
    protected $productInfoModel;
    
    public function __construct(ProductInfoModel $productInfoModel)
    {
        $this->productInfoModel = $productInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年02月09日 14:55
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月09日 14:55
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月09日 14:55
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月09日 14:55
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月09日 14:55
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productInfoModel->deleteBy($id));
    }
}