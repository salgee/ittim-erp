<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductAnnex as productAnnexModel;

class ProductAnnex extends CatchController
{
    protected $productAnnexModel;
    
    public function __construct(ProductAnnexModel $productAnnexModel)
    {
        $this->productAnnexModel = $productAnnexModel;
    }
    
    /**
     * 列表
     * @time 2021年02月09日 15:05
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productAnnexModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月09日 15:05
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productAnnexModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月09日 15:05
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productAnnexModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月09日 15:05
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productAnnexModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月09日 15:05
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productAnnexModel->deleteBy($id));
    }
}