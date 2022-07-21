<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductSalesPriceInfo as productSalesPriceInfoModel;

class ProductSalesPriceInfo extends CatchController
{
    protected $productSalesPriceInfoModel;
    
    public function __construct(ProductSalesPriceInfoModel $productSalesPriceInfoModel)
    {
        $this->productSalesPriceInfoModel = $productSalesPriceInfoModel;
    }
    
    /**
     * 列表
     * @time 2021年03月09日 10:01
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productSalesPriceInfoModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月09日 10:01
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productSalesPriceInfoModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年03月09日 10:01
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productSalesPriceInfoModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月09日 10:01
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productSalesPriceInfoModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年03月09日 10:01
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productSalesPriceInfoModel->deleteBy($id));
    }
}