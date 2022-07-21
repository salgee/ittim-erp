<?php


namespace catchAdmin\system\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\system\model\DictionaryData as DictionaryDataModel;

class DictionaryData extends CatchController
{
    protected $model;
    
    public function __construct(DictionaryDataModel $model)
    {
        $this->model = $model;
    }
    
    /**
     * 列表
     *
     * @time 2020/09/08 18:58
     *  
     * @return \think\Response
     */
    public function index()
    {
        return CatchResponse::paginate($this->model->getList());
    }
    
    /**
     * 保存
     *
     * @time 2020/09/08 18:58
     * @param Request Request 
     * @return \think\Response
     */
    public function save(Request $request)
    {
        return CatchResponse::success($this->model->storeBy($request->param()));
    }
    
    /**
     * 读取
     *
     * @time 2020/09/08 18:58
     * @param $id 
     * @return \think\Response
     */
    public function read($id)
    {
       return CatchResponse::success($this->model->findBy($id)); 
    }
    
    /**
     * 更新
     *
     * @time 2020/09/08 18:58
     * @param Request $request 
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        return CatchResponse::success($this->model->updateBy($id, $request->param()));
    }
    
    /**
     * 删除
     *
     * @time 2020/09/08 18:58
     * @param $id 
     * @return \think\Response
     */
    public function delete($id)
    {
        return CatchResponse::success($this->model->deleteBy($id));
    }
    
    
}