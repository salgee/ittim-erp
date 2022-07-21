<?php

namespace catchAdmin\report\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\report\model\ReportOrderAfterSale as reportOrderAfterSaleModel;

class ReportOrderAfterSale extends CatchController
{
    protected $reportOrderAfterSaleModel;

    public function __construct(ReportOrderAfterSaleModel $reportOrderAfterSaleModel)
    {
        $this->reportOrderAfterSaleModel = $reportOrderAfterSaleModel;
    }

    /**
     * 列表
     * @time 2021年04月21日 16:45
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->reportOrderAfterSaleModel->getList());
    }

    /**
     * 读取
     * @time 2021年04月21日 16:45
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->reportOrderAfterSaleModel->findBy($id));
    }


}
