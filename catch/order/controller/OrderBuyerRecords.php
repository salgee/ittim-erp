<?php

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderBuyerRecords as orderBuyerRecordsModel;

class OrderBuyerRecords extends CatchController
{
    protected $orderBuyerRecordsModel;

    public function __construct(OrderBuyerRecordsModel $orderBuyerRecordsModel)
    {
        $this->orderBuyerRecordsModel = $orderBuyerRecordsModel;
    }

    /**
     * 列表
     * @time 2021年02月05日 19:43
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderBuyerRecordsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月05日 19:43
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->orderBuyerRecordsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月05日 19:43
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->orderBuyerRecordsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月05日 19:43
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->orderBuyerRecordsModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年02月05日 19:43
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->orderBuyerRecordsModel->deleteBy($id));
    }
}
