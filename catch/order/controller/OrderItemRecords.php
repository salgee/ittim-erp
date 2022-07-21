<?php

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderItemRecords as orderItemRecordsModel;

class OrderItemRecords extends CatchController
{
    protected $orderItemRecordsModel;

    public function __construct(OrderItemRecordsModel $orderItemRecordsModel)
    {
        $this->orderItemRecordsModel = $orderItemRecordsModel;
    }

    /**
     * 列表
     * @time 2021年02月05日 22:02
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderItemRecordsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月05日 22:02
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->orderItemRecordsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月05日 22:02
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->orderItemRecordsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月05日 22:02
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->orderItemRecordsModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年02月05日 22:02
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->orderItemRecordsModel->deleteBy($id));
    }
}
