<?php

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderEbayRecords as orderEbayRecordsModel;

class OrderEbayRecords extends CatchController
{
    protected $orderEbayRecordsModel;

    public function __construct(OrderEbayRecordsModel $orderEbayRecordsModel)
    {
        $this->orderEbayRecordsModel = $orderEbayRecordsModel;
    }

    /**
     * 列表
     * @time 2021年02月02日 18:39
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderEbayRecordsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月02日 18:39
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->orderEbayRecordsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月02日 18:39
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayRecordsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月02日 18:39
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayRecordsModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年02月02日 18:39
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->orderEbayRecordsModel->deleteBy($id));
    }
}
