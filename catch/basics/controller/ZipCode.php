<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 11:42:37
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-09 09:37:12
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\ZipCode as zipCodeModel;
use catchAdmin\basics\excel\ZipCodeImport;
use catcher\Code;

class ZipCode extends CatchController
{
    protected $zipCodeModel;

    public function __construct(ZipCodeModel $zipCodeModel)
    {
        $this->zipCodeModel = $zipCodeModel;
    }

    /**
     * 列表
     * @time 2021年02月06日 11:42
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->zipCodeModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月06日 11:42
     * @param Request $request 
     */
    public function save(Request $request): \think\Response
    {
        $data = $request->post();
        // 验证邮编是否重复
        if ($this->zipCodeModel->where(['origin' => $data['origin'], 'dest_zip' => $data['dest_zip']])->find()) {
            return  CatchResponse::fail('对应邮编已存在', Code::FAILED);
        }
        return CatchResponse::success($this->zipCodeModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月06日 11:42
     * @param $id 
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->zipCodeModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月06日 11:42
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->zipCodeModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年02月06日 11:42
     * @param $id
     */
    public function delete($id): \think\Response
    {
        return CatchResponse::success($this->zipCodeModel->deleteBy($id));
    }
    /**
     * 下载邮编分区导入模板
     */
    public function template()
    {
        return download(public_path() . 'template/zipcodeImport.xlsx')->force(true);
    }

    /**
     * 导入 excel upload
     * @param CatchRequest $request
     * @param CatchUpload $upload
     * @return \think\response\Json
     */
    public function zipImport(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $user = request()->user();
        $file = $request->file();
        $data = $import->read($file['file']);
        $dataList = [];
        foreach ($data as $value) {
            if ($this->zipCodeModel->where(['origin' => $value[0], 'dest_zip' => $value[1]])
                ->find()
            ) {
                $dataList['repeat'][] = 'origin' . $value[0] . ';dest_zip' . $value[1];
                continue;
            }
            $row = [
                'origin' => $value[0],
                'dest_zip' => $value[1],
                'state' => $value[2],
                'zone' => $value[3],
                'creator_id' => $user['id']
            ];
            $dataList['success'][] = 'origin' . $value[0] . ';dest_zip' . $value[1];
            $this->zipCodeModel->createBy($row);
        }
        return CatchResponse::success($dataList);
    }
}
