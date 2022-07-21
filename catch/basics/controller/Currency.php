<?php
/*
 * @Author:
 * @Date: 2021-02-04 18:04:37
 * @LastEditTime: 2021-05-18 18:17:54
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\controller\Currency.php
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\basics\request\CurrencyRequest;
use catchAdmin\basics\model\Currency as currencyModel;

class Currency extends CatchController
{
    protected $currencyModel;
    
    public function __construct(CurrencyModel $currencyModel)
    {
        $this->currencyModel = $currencyModel;
    }
    
    /**
     * 列表
     * @time 2021年02月04日 18:04
     * @param Request $request 
     */
    public function index()
    {
        return CatchResponse::paginate($this->currencyModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月04日 18:04
     * @param Request $request 
     */
    public function save(CurrencyRequest $request) : \think\Response
    {
        return CatchResponse::success($this->currencyModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月04日 18:04
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->currencyModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月04日 18:04
     * @param Request $request 
     * @param $id
     */
    public function update(CurrencyRequest $request, $id) : \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->currencyModel->updateBy($id, $data));
    }
    
    /**
     * 删除
     * @time 2021年02月04日 18:04
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->currencyModel->deleteBy($id));
    }

    /**
     * 批量禁用
     * @time 2020/09/16
     * @param Request $request  
     */
    public function disable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 2
                ];
                $list[] = $row;
            }
            $this->currencyModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 批量启用 enable
     */
    public function enable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            $this->currencyModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 获取所有
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll(Request $request)
    {
        $name = $request->param('name') ?? '';
        $sourceCode = $request->param('source_code') ?? '';
        return CatchResponse::success($this->currencyModel->field(['id', 'source_code', 'source_name', 'rate', 'is_status'])
            ->where(['is_status' => 1])
            ->whereLike('source_name', $name)
            ->whereLike('source_code', $sourceCode)
            ->select());
    }
}