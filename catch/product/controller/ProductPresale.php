<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-09 14:50:37
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-01 15:52:22
 * @Description:
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\ProductPresale as productPresaleModel;
use catchAdmin\product\model\ProductPresaleInfo;
use catchAdmin\product\request\ProductPresaleRequest;
use catcher\Code;
use think\facade\Cache;

class ProductPresale extends CatchController
{
    protected $productPresaleModel;
    protected $productPresaleInfo;

    public function __construct(ProductPresaleModel $productPresaleModel, ProductPresaleInfo $productPresaleInfo)
    {
        $this->productPresaleModel = $productPresaleModel;
        $this->productPresaleInfo = $productPresaleInfo;
    }

    /**
     * 列表
     * @time 2021年03月09日 14:50
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productPresaleModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年03月09日 14:50
     * @param Request $request
     */
    public function save(ProductPresaleRequest $request): \think\Response
    {
        try {
            $data = $request->post();
            $this->productPresaleModel->startTrans();
            $id = $this->productPresaleModel->storeBy($request->post());

            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_presale_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'estimated_delivery_time' => $val['estimated_delivery_time'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productPresaleInfo->insertAllBy($list);
            }
            // 预售活动写入缓存
            $this->productPresaleInfo->createProductPreSale();
            $this->productPresaleModel->commit();

            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productPresaleModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        // return CatchResponse::success($this->productPresaleModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年03月09日 14:50
     * @param $id
     */
    public function read($id): \think\Response
    {
        $data = $this->productPresaleModel->findByInfo($id);
        if (!$data) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        $data['list'] = $this->productPresaleInfo->selectInfo($id);
        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年03月09日 14:50
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $data = $request->post();
            $data['status'] = 0;
            $priceData = $this->productPresaleModel->findBy($id);
            if (!$priceData) {
                return CatchResponse::fail('预售模板不存在', Code::FAILED);
            }
            if ($priceData['is_disable'] == 1) {
                return CatchResponse::fail('启用状态不可编辑', Code::FAILED);
            }
            $user = request()->user();
            $data['update_by'] = $user['id'];
            $this->productPresaleModel->startTrans();
            $res = $this->productPresaleModel->updateBy($id, $data);
            $dataAll = $this->productPresaleInfo->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->productPresaleInfo->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['product'])) {
                foreach ($data['product'] as $val) {
                    $row =  [
                        'product_presale_id' => $id,
                        'product_id' => $val['product_id'] ?? '',
                        'estimated_delivery_time' => $val['estimated_delivery_time'] ?? ''
                    ];
                    $list[] = $row;
                }
                $this->productPresaleInfo->insertAllBy($list);
            }
            // 预售活动写入缓存
            $this->productPresaleInfo->createProductPreSale();
            $this->productPresaleModel->commit();
            return CatchResponse::success($id);
        } catch (\Exception $exception) {
            $this->productPresaleModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        // return CatchResponse::success($this->productPresaleModel->updateBy($id, $request->post()));
    }

    /**
     * 删除
     * @time 2021年03月09日 14:50
     * @param $id
     */
    public function delete($id): \think\Response
    {
        try {
            $priceData = $this->productPresaleModel->findBy($id);
            if (!$priceData) {
                return CatchResponse::fail('预售模板不存在', Code::FAILED);
            }
            if ($priceData['is_disable'] == 1) {
                return CatchResponse::fail('启用状态不可删除', Code::FAILED);
            }
            // 查询关联数据
            $infoIds = $this->productPresaleInfo->where('product_presale_id', $id)->column('id');
            $ids = implode(',', $infoIds);
            // 删除关联子数据
            $this->productPresaleInfo->deleteBy($ids);
            $res = $this->productPresaleModel->deleteBy($id);
            // if ($res){
            //     // 同步删除预售活动的缓存
            //     Cache::delete(Code::CACHE_PRESALE.$priceData['shop_id'].'_'.$priceData['product_id']);
            // }
            // 预售活动写入缓存
            $this->productPresaleInfo->createProductPreSale();

            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        // return CatchResponse::success($this->productPresaleModel->deleteBy($id));
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
            $this->productPresaleModel->saveAll($list);
            // 预售活动写入缓存
            $this->productPresaleInfo->createProductPreSale();
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
            $this->productPresaleModel->saveAll($list);
            // 预售活动写入缓存
            $this->productPresaleInfo->createProductPreSale();
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
}
