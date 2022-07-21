<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 15:16:33
 * @LastEditors:
 * @LastEditTime: 2021-02-05 19:01:56
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\request\LfCurrencyRequest;
use catchAdmin\basics\model\LfCurrency as lfCurrencyModel;

class LfCurrency extends CatchController
{
    protected $lfCurrencyModel;
    
    public function __construct(LfCurrencyModel $lfCurrencyModel)
    {
        $this->lfCurrencyModel = $lfCurrencyModel;
    }
    
    /**
     * 列表
     * @time 2021年02月05日 15:16
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->lfCurrencyModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月05日 15:16
     * @param Request $request 
     */
    public function save(LfCurrencyRequest $request) : \think\Response
    {
        return CatchResponse::success($this->lfCurrencyModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月05日 15:16
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->lfCurrencyModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月05日 15:16
     * @param Request $request 
     * @param $id
     */
    public function update(LfCurrencyRequest $request, $id) : \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->lfCurrencyModel->updateBy($id, $data));
    }
    
    /**
     * 删除
     * @time 2021年02月05日 15:16
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->lfCurrencyModel->deleteBy($id));
    }
}