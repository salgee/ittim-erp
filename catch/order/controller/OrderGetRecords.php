<?php

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrderGetRecords as orderGetRecordsModel;

class OrderGetRecords extends CatchController
{
    protected $orderGetRecordsModel;

    public function __construct(OrderGetRecordsModel $orderGetRecordsModel)
    {
        $this->orderGetRecordsModel = $orderGetRecordsModel;
    }

    /**
     * 列表
     * @time 2021年02月05日 12:22
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->orderGetRecordsModel->getList()->each(function (&$item){
            $item['get_at'] = (new \Datetime())->setTimestamp($item['get_at'])->format('Y-m-d H:i:s');
            $item['get_count'] = $item['get_count'] . ' / 条';
            $item['platform_name'] = '[店铺ID:'.$item['shop_basics_id'].'] ' . $item['platform_name'];
        }));
    }

    /**
     * 保存信息
     * @time 2021年02月05日 12:22
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->orderGetRecordsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月05日 12:22
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->orderGetRecordsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月05日 12:22
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->orderGetRecordsModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年02月05日 12:22
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->orderGetRecordsModel->deleteBy($id));
    }
}
