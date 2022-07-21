<?php


namespace catchAdmin\system\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\system\request\DictionaryRequest;
use catchAdmin\system\model\Dictionary as DictionaryModel;

class Dictionary extends CatchController
{
    protected $model;
    
    public function __construct(DictionaryModel $model)
    {
        $this->model = $model;
    }
    
    /**
     * 列表
     *
     * @time 2020/09/08 18:50
     *  
     * @return \think\Response
     */
    public function index()
    {
        return CatchResponse::paginate($this->model->getList());
    }

    /**
     * 字典及数据列表
     */
    public function listWithData() {
        return CatchResponse::success($this->model->getDictionariesWithData());
    }

    /**
     * 删除数据字典值
     */
    public function delData(Request $request) {
        return CatchResponse::success($this->model->delDictionary($request));
    }

    /**
     * 数据字典数据详情
     */
    public function detail($dictValue) {
        return CatchResponse::success($this->model->getDictionaryDataDetail($dictValue));
    }

    /**
     * 添加数据字典
     */
    public function addDict(DictionaryRequest $request) {
        return CatchResponse::success($this->model->addDictionary($request));
    }
    
    /**
     * 更新数据字典
     */
    public function updateDict($dictValue, DictionaryRequest $request) {
        return CatchResponse::success($this->model->updateDictionary($dictValue, $request));
    }

    /**
     * 保存
     *
     * @time 2020/09/08 18:50
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
     * @time 2020/09/08 18:50
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
     * @time 2020/09/08 18:50
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
     * @time 2020/09/08 18:50
     * @param $id 
     * @return \think\Response
     */
    public function delete($id)
    {
        return CatchResponse::success($this->model->deleteBy($id));
    }

    /**
     * 根据类型查询数据字段
     */
    public function getListType($id) {
        return CatchResponse::success($this->model->getDictionaryDataDetailByType($id));
    }
    
    
}