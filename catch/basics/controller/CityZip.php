<?php

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\CityZip as cityZipModel;

class CityZip extends CatchController
{
    protected $cityZipModel;

    public function __construct(CityZipModel $cityZipModel)
    {
        $this->cityZipModel = $cityZipModel;
    }

    /**
     * 列表
     * @time 2021年03月22日 17:37
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->cityZipModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年03月22日 17:37
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->cityZipModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年03月22日 17:37
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->cityZipModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年03月22日 17:37
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->cityZipModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年03月22日 17:37
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->cityZipModel->deleteBy($id));
    }
}
