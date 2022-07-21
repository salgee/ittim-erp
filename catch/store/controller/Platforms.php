<?php
/*
 * @Description: 
 * @Author: maryna
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2021-07-27 16:25:15
 */

namespace catchAdmin\store\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\store\model\Platforms as platformsModel;

class Platforms extends CatchController
{
    protected $platformsModel;

    public function __construct(PlatformsModel $platformsModel)
    {
        $this->platformsModel = $platformsModel;
    }

    /**
     * 列表
     * @time 2021年01月23日 14:55
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->platformsModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     * @param Request $request 
     */
    public function save(Request $request): \think\Response
    {
        return CatchResponse::success($this->platformsModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年01月23日 14:55
     * @param $id 
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->platformsModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年01月23日 14:55
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        $data = $request->post();
        unset($data['platform_parameters']);
        unset($data['created_at']);
        return CatchResponse::success($this->platformsModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年01月23日 14:55
     * @param $id
     */
    public function delete($id): \think\Response
    {
        return CatchResponse::success($this->platformsModel->deleteBy($id));
    }

    /**
     * 账户绑定
     * 
     */
    public function setAccount(Request $request, $id)
    {
        $data = $request->post();
        return CatchResponse::success($this->platformsModel->updateBy($id, $data));
    }
}
