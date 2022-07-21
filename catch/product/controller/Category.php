<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-08 15:01:31
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-01 18:29:16
 * @Description: 
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\product\request\CategoryRequest;
use catchAdmin\product\model\Category as categoryModel;
use catcher\Code;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\Parts;

class Category extends CatchController
{
    protected $categoryModel;
    
    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }
    
    /**
     * 列表
     * @time 2021年02月08日 15:01
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::success($this->categoryModel->getList());
        // return CatchResponse::paginate($this->categoryModel->getList());
    }
    /**
     * 二级分类列表
     * @time 2021年02月08日 15:01
     * @param Request $request 
     */
    public function getChildList(Request $request, $id): \think\Response
    {
        return CatchResponse::paginate($this->categoryModel->getChildList($id));
    }

    /***
     * getListTree 列表树状结构
     * @time 2021年02月08日 15:01
     * @param Request $request 
     */
    // public function getListTree(Request $request): \think\Response
    // {
    //     return CatchResponse::success($this->categoryModel->getListTree());
    // }
    /**
     * 列表树状结构
     * @time 2021年02月08日 15:01
     * @param Request $request 
     */
    public function getListTree(Request $request): \think\Response
    {
        return CatchResponse::success($this->categoryModel->getListTree());
        // return CatchResponse::paginate($this->categoryModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月08日 15:01
     * @param Request $request 
     */
    public function save(CategoryRequest $request) : \think\Response
    {
        $data = $request->post();
        if(!empty($data['parent_id'])) {
            $data['parent_name'] = $this->categoryModel->where('id', $data['parent_id'])->value('name');
        }
        return CatchResponse::success($this->categoryModel->storeBy($data));
    }
    
    /**
     * 读取
     * @time 2021年02月08日 15:01
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->categoryModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月08日 15:01
     * @param Request $request 
     * @param $id
     */
    public function update(CategoryRequest $request, $id) : \think\Response
    {
        $data = $request->post();
        $dataCategory = $this->categoryModel->findBy($id);
        if(!$dataCategory) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        $user = request()->user();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();
        
        if(empty($dataCategory['parent_id'])) {
            $arry =  $this->categoryModel->field('id')->where('parent_id', $id)->select();
            $list = [];
            foreach ($arry as $key => $value) {
                $row = [
                   'id' =>  $value['id'],
                    'parent_name' => $data['name']
                ];
                $list[] = $row;
            }
            // 修改子类的父级名称
            $this->categoryModel->saveAll($list);
        }
        
        return CatchResponse::success($this->categoryModel->updateBy($id, $data));
    }
    
    /**
     * 删除
     * @time 2021年02月08日 15:01
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        if ($this->categoryModel->where('parent_id', $id)->find()) {
            throw new FailedException('存在子分类，无法删除');
        }
        // 分类关联商品不能删除
        if(Product::where('category_id', $id)->find()) {
            throw new FailedException('分类已有关联商品数据不可删除');
        }
        // 配件
        if(Parts::where('category_id', $id)->find()) {
            throw new FailedException('分类已有关联配件数据不可删除');
        }

        return CatchResponse::success($this->categoryModel->deleteBy($id));
    }

    /**
     * 状态更新
     * @time 2020/09/16
     * @param Request $request  
     */
    public function verify(Request $request, $id): \think\Response
    {
        if (!in_array($request->post('is_status'), [0, 1])) {
            throw new FailedException('参数不正确');
        }
        
        return CatchResponse::success($this->categoryModel->updateBy($id, $request->post()));
    }
}