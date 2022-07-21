<?php

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderEbayItemRecords as orderEbayItemRecordsModel;

class OrderEbayItemRecords extends CatchController
{
    protected $orderEbayItemRecordsModel;

    public function __construct(OrderEbayItemRecordsModel $orderEbayItemRecordsModel)
    {
        $this->orderEbayItemRecordsModel = $orderEbayItemRecordsModel;
    }

    /**
     * 列表
     * @time 2021年02月03日 18:55
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderEbayItemRecordsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月03日 18:55
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->orderEbayItemRecordsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月03日 18:55
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayItemRecordsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月03日 18:55
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayItemRecordsModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年02月03日 18:55
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayItemRecordsModel->deleteBy($id));
    }
}
