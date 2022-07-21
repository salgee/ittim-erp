<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-08 10:53:07
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-18 13:59:48
 * @Description: 
 */

namespace catchAdmin\product\controller;

use catchAdmin\basics\controller\Shop;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductCombination as productCombinationModel;
use catchAdmin\product\model\ProductCombinationInfo;
use catchAdmin\product\request\ProductCombinationRequest;
use catcher\Code;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\basics\model\Shop as shopModel;
use catchAdmin\product\model\Product as productModel;
use catchAdmin\product\excel\CommonExportCombination;
use catchAdmin\product\model\ProductPlatformSku;






class ProductCombination extends CatchController
{
    protected $productCombinationModel;
    protected $productCombinationInfo;
    protected $shopModel;
    protected $productModel;

    public function __construct(
        ProductCombinationModel $productCombinationModel,
        productCombinationInfo $productCombinationInfo,
        shopModel $shopModel,
        ProductModel $productModel
    ) {
        $this->productCombinationModel = $productCombinationModel;
        $this->productCombinationInfo = $productCombinationInfo;
        $this->shopModel = $shopModel;
        $this->productModel = $productModel;
    }

    /**
     * 列表
     * @time 2021年03月08日 10:53
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productCombinationModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年03月08日 10:53
     * @param Request $request 
     */
    public function save(ProductCombinationRequest $request): \think\Response
    {
        try {
            $data = $request->post();
            // 判断同一个店铺是否存在编码
            if ($this->productCombinationModel->where(['code' => $data['code'], 'shop_id' => $data['shop_id']])->find()) {
                return CatchResponse::fail('该店铺编码已存在');
            }
            $this->productCombinationModel->startTrans();
            $id = $this->productCombinationModel->storeBy($data);

            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_combination_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'price' => $val['price'] ?? '',
                        'number' => $val['number'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productCombinationInfo->insertAllBy($list);
            }
            $this->productCombinationModel->commit();
            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productCombinationModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 读取
     * @time 2021年03月08日 10:53
     * @param $id 
     */
    public function read($id): \think\Response
    {
        $data = $this->productCombinationModel->findBy($id);
        if (!$data) {
            return CatchResponse::fail('详情不存在', Code::FAILED);
        }
        $data['shop_name'] = $this->shopModel->where('id', $data['shop_id'])->value('shop_name');
        $data['list'] = $this->productCombinationInfo->selectInfo($id);
        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年03月08日 10:53
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $data = $this->productCombinationModel->findBy($id);
            if (!$data) {
                return CatchResponse::fail('数据不存在', Code::FAILED);
            }
            $data = $request->post();
            // 判断同一个店铺是否存在编码
            if ($this->productCombinationModel->where(['code' => $data['code'], 'shop_id' => $data['shop_id']])
                ->where('id', '<>', $id)
                ->find()
            ) {
                return CatchResponse::fail('该店铺编码已存在');
            }
            $user = request()->user();
            unset($data['created_at']);
            unset($data['creator_id']);
            unset($data['shop_id']);
            $data['update_by'] = $user['id'];
            $this->productCombinationModel->startTrans();
            $res = $this->productCombinationModel->updateBy($id, $data);
            $dataAll = $this->productCombinationInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->productCombinationInfo->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_combination_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'price' => $val['price'] ?? '',
                        'number' => $val['number'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productCombinationInfo->insertAllBy($list);
            }
            $this->productCombinationModel->commit();
            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productCombinationModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 删除
     * @time 2021年03月08日 10:53
     * @param $id
     */
    public function delete($id): \think\Response
    {
        try {
            // 判断商品是否有关联订单 有不可删除
            // code-----

            if (!$dataObj = $this->productCombinationModel->findBy($id)) {
                return CatchResponse::fail('商品不存在', Code::FAILED);
            }
            // 组合商品是否有映射关系
            $productPlatformSku = new ProductPlatformSku;
            if ($productPlatformSku->where(['product_id' => $id, 'product_code' => $dataObj['code']])->find()) {
                return CatchResponse::fail('组合商品映射已存在，不可删除', Code::FAILED);
            }
            $this->productCombinationModel->startTrans();
            // 删除组合商品
            $this->productCombinationModel->deleteBy($id, $force = false);
            // 删除组合商品关联-商品
            $dataAll = $this->productCombinationInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->productCombinationInfo->deleteBy($dataAll,  $force = false);
            }
            $this->productCombinationModel->commit();
            return CatchResponse::success('删除成功');
        } catch (\Exception $exception) {
            $this->productCombinationModel->rollback();
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
            $this->productCombinationModel->saveAll($list);
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
            $this->productCombinationModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 组合商品模板下载
     * @param Request $request
     */
    public function templateCombination(Request $request)
    {
        return download(public_path() . 'template/combinedProductImport.xlsx')->force(true);
    }

    /**
     * 组合商品数据导入（客户模板）
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    public function productCombination(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->productCombination($file['file']);
        $error = [];
        $list = [];
        foreach ($data as $product) {
            // 判断店铺是否存在
            if (!$shopData = $this->shopModel->where(['shop_name' => trim($product[1])])->find()) {
                return CatchResponse::fail(sprintf('商家编码【%s】所在店铺【%s】不存在', $product[0], $product[1]));
                // $list['empty'][] = $product[0];
            }
            if ($code = $this->productCombinationModel->where(['code' => trim($product[0]), 'shop_id' => $shopData['id']])->value('id')) {
                return CatchResponse::fail(sprintf('商家编码【%s】重复', $product[0], $product[1]));
                // $list['repeat'][] = $product[0];
            }
            // 开启事务
            $this->productCombinationModel->startTrans();
            // 添加组合商品
            if ($productCombinationId = $this->productCombinationModel->createBy([
                'code' => $product[0],
                'name_ch' => $product[2],
                'name_en' => $product[3],
                'price_usd' => $product[4],
                'shop_id' => $shopData['id'],
                'creator_id' => request()->user()->id
            ])) {
                $group = [];
                $count = 5; // 循环起始列
                for ($x = $count; $x <= count($product); $x++) {
                    if ($x % 2 != 0 && !empty($product[$x])) {
                        // 判断商品是否存在
                        if (!$productData = $this->productModel->where(['code' => $product[$x], 'company_id' => $shopData['company_id']])->find()) {
                            // 失败
                            $this->productCombinationModel->rollback();
                            return CatchResponse::fail(sprintf('商家编码【%s】的单价SKU【%s】不存在', $product[0], $product[$x]));
                        }
                        array_push($group, [
                            'product_combination_id' => $productCombinationId, // 组合商品ID
                            'product_id' => $productData['id'],
                            'price' => '', // 基准价
                            'number' => $product[$x + 1] ?? '',
                            'creator_id' => request()->user()->id
                        ]);
                    }
                }
                // 保存组合商品
                if ($this->productCombinationInfo->insertAllBy($group)) {
                    // 成功
                    $this->productCombinationModel->commit();
                } else {
                    // 失败
                    $this->productCombinationModel->rollback();
                }
            }
        }
        if (!empty($error)) {
            return CatchResponse::fail(implode(',', $error));
        }
        return CatchResponse::success('导入成功');
    }

    /**
     * 组合商品导出
     */
    public function export(Request $request)
    {
        $data = $request->post();
        $res = $this->productCombinationModel->getExportList();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        $excel = new CommonExportCombination();

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->productCombinationModel->exportField();
        }

        $url = $excel->export($res, $exportField, '商品导出');
        return  CatchResponse::success($url);
    }
}
