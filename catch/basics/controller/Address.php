<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-06 10:42:04
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-06 19:40:51
 * @Description:
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\request\AddressRequest;
use catchAdmin\basics\model\Address as addressModel;

class Address extends CatchController
{
    protected $addressModel;

    public function __construct(AddressModel $addressModel)
    {
        $this->addressModel = $addressModel;
    }

    /**
     * 列表
     * @time 2021年02月06日 10:42
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        phpinfo(); exit;
        return CatchResponse::paginate($this->addressModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月06日 10:42
     * @param Request $request
     */
    public function save(AddressRequest $request) : \think\Response
    {
        return CatchResponse::success($this->addressModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月06日 10:42
     * @param $id
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->addressModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月06日 10:42
     * @param Request $request
     * @param $id
     */
    public function update(AddressRequest $request, $id) : \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->addressModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年02月06日 10:42
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->addressModel->deleteBy($id));
    }
}
