<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-09 09:56:56
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-16 11:09:48
 * @Description: 
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductSalesPrice as productSalesPriceModel;
use catchAdmin\product\model\ProductSalesPriceInfo;
use catchAdmin\product\request\ProductSalesPriceRequest;
use catcher\Code;
class ProductSalesPrice extends CatchController
{
    protected $productSalesPriceModel;
    protected $productSalesPriceInfo;
    
    public function __construct(ProductSalesPriceModel $productSalesPriceModel, 
                                ProductSalesPriceInfo $productSalesPriceInfo)
    {
        $this->productSalesPriceModel = $productSalesPriceModel;
        $this->productSalesPriceInfo = $productSalesPriceInfo;
    }
    
    /**
     * 列表
     * @time 2021年03月09日 09:56
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productSalesPriceModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月09日 09:56
     * @param Request $request 
     */
    public function save(ProductSalesPriceRequest $request) : \think\Response
    {
        try {
            $data = $request->post();
            $this->productSalesPriceModel->startTrans();
            $id = $this->productSalesPriceModel->storeBy($request->post());

            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_sales_price_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'price' => $val['price'] ?? '',
                        'sales_price' => $val['sales_price'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productSalesPriceInfo->insertAllBy($list);
            }
            $this->productSalesPriceModel->commit();
            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productSalesPriceModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 读取
     * @time 2021年03月09日 09:56
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        $data = $this->productSalesPriceModel->findByInfo($id);
        if(!$data) {
            return CatchResponse::fail('详情不存在');
        }
        $data['list'] = $this->productSalesPriceInfo->selectInfo($id);
        return CatchResponse::success($data);
        // return CatchResponse::success($this->productSalesPriceModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月09日 09:56
     * @param Request $request 
     * @param $id
     */
    public function update(ProductSalesPriceRequest $request, $id) : \think\Response
    {
        try {
            $data = $request->post();
            $data['status'] = 0; 
            $priceData = $this->productSalesPriceModel->findBy($id);
            if(!$priceData) {
                return CatchResponse::fail('价格模板不存在', Code::FAILED);
            }
            if($priceData['status'] == 1) {
                return CatchResponse::fail('审核通过不可编辑', Code::FAILED);
            }
            $user = request()->user();
            $data['update_by'] = $user['id'];
            $this->productSalesPriceModel->startTrans();
            $res = $this->productSalesPriceModel->updateBy($id, $data);
            $dataAll = $this->productSalesPriceInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->productSalesPriceInfo->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_sales_price_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'price' => $val['price'] ?? '',
                        'sales_price' => $val['sales_price'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productSalesPriceInfo->insertAllBy($list);
            }
            $this->productSalesPriceModel->commit();
            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productSalesPriceModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    
    /**
     * 删除
     * @time 2021年03月09日 09:56
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        try {
            $priceData = $this->productSalesPriceModel->findBy($id);
            if (!$priceData) {
                return CatchResponse::fail('价格模板不存在', Code::FAILED);
            }
            if ($priceData['is_disable'] == 1) {
                return CatchResponse::fail('启用状态不可删除', Code::FAILED);
            }
            // 查询关联数据
            $infoIds = $this->productSalesPriceInfo->where('product_sales_price_id', $id)->column('id');
            $ids = implode(',', $infoIds);
            // 删除关联子数据
            $this->productSalesPriceInfo->deleteBy($ids);
            return CatchResponse::success($this->productSalesPriceModel->deleteBy($id));
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        
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
                    'is_disable' => 2
                ];
                $list[] = $row;
            }
            $this->productSalesPriceModel->saveAll($list);
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
                    'is_disable' => 1
                ];
                $list[] = $row;
            }
            $this->productSalesPriceModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 提交审核
     */
    public function batchExamine(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'status' => 3
                ];
                $list[] = $row;
            }
            $this->productSalesPriceModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 审核
     * @param Request $request  
     */
    public function examine(Request $request, $id): \think\Response
    {
        if (!in_array($request->param('status'), [1, 2])) {
            return  CatchResponse::fail('参数不正确', Code::FAILED);
        }
        $priceData = $this->productSalesPriceModel->findBy($id);
        if(!$priceData) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        $data['status'] = $request->param('status');
        $data['reason'] = $request->param('reason') ?? '';
        $data['is_disable'] = $request->param('status');
        return CatchResponse::success($this->productSalesPriceModel->updateBy($id, $data));
    }
}