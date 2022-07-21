<?php

namespace catchAdmin\system\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\system\model\Notice as noticeModel;
use catcher\exceptions\FailedException;
use think\response\Json;

class Notice extends CatchController
{
    protected $noticeModel;

    public function __construct(NoticeModel $noticeModel)
    {
        $this->noticeModel = $noticeModel;
    }

    /**
     * 列表
     * @time 2021年02月19日 12:34
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->noticeModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月19日 12:34
     * @param Request $request
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->noticeModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月19日 12:34
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->noticeModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月19日 12:34
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        if ($this->noticeModel->findBy($id)['status'] == 1) {
            throw new FailedException('已发布的公告无法编辑');
        }
        $data = $request->post();
        $data['updater_id'] = $data['creator_id'];
        return CatchResponse::success($this->noticeModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年02月19日 12:34
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        if ($this->noticeModel->findBy($id)['status'] == 1) {
            throw new FailedException('已发布的公告无法删除');
        }
        return CatchResponse::success($this->noticeModel->deleteBy($id));
    }

    /**
     * 发布/取消发布
     *
     * @author Salgee
     * @time 2021/02/19
     * @param $id
     * @return Json
     */
    public function publish($id)
    {
        $this->noticeModel->publish($id);

        return CatchResponse::success();
    }
}
