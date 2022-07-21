<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 12:27:20
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-29 15:32:52
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\request\ZipCodeSpecialRequest;
use catchAdmin\basics\model\ZipCodeSpecial as zipCodeSpecialModel;
use catchAdmin\basics\excel\ZipCodeImport;


class ZipCodeSpecial extends CatchController
{
    protected $zipCodeSpecialModel;
    
    public function __construct(ZipCodeSpecialModel $zipCodeSpecialModel)
    {
        $this->zipCodeSpecialModel = $zipCodeSpecialModel;
    }
    
    /**
     * 列表
     * @time 2021年02月06日 12:27
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->zipCodeSpecialModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月06日 12:27
     * @param Request $request 
     */
    public function save(ZipCodeSpecialRequest $request) : \think\Response
    {
        return CatchResponse::success($this->zipCodeSpecialModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月06日 12:27
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->zipCodeSpecialModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月06日 12:27
     * @param Request $request 
     * @param $id
     */
    public function update(ZipCodeSpecialRequest $request, $id) : \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->zipCodeSpecialModel->updateBy($id, $data));
    }
    
    /**
     * 删除
     * @time 2021年02月06日 12:27
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->zipCodeSpecialModel->deleteBy($id));
    }

    /**
     * 导入 excel upload
     * @param CatchRequest $request
     * @param CatchUpload $upload
     * @return \think\response\Json
     */
    public function zipImport(Request $request, ZipCodeImport $import,\catcher\CatchUpload $upload)
    {
        $user = request()->user();
        $file = $request->file();
        $data = $import->read($file['file']);
        // var_dump('$data', $data); exit;
        $dataList = [];
        foreach ($data as $obj) {
            foreach ($obj as $key=>$val) {
                if(!$this->zipCodeSpecialModel->where('zipCode',$val)->value('id')) {
                    $row = [
                        'zipCode' => $val,
                        'type' => 2,
                        'creator_id' => $user['id']
                    ];
                    $this->zipCodeSpecialModel->createBy($row);
                }
            }
        }
        return CatchResponse::success($dataList);
    }
}