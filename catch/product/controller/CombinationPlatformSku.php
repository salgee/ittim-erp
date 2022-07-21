<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 10:26:57
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-06 18:24:55
 * @Description:
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\request\CombinationPlatformSkuRequest;
use catchAdmin\product\model\ProductPlatformSku as productPlatformSkuModel;
use catcher\Code;
use catchAdmin\product\model\ProductCombination;
use catchAdmin\product\model\ProductCombinationInfo;
use catchAdmin\product\model\ProductPresaleInfo;
use catchAdmin\product\model\ProductSalesPriceInfo;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\basics\model\Shop;

class CombinationPlatformSku extends CatchController
{
    protected $productPlatformSkuModel;
    protected $productCombination;
    protected $productCombinationInfo;
    protected $productPresaleInfo;
    protected $productSalesPriceInfo;

    public function __construct(
        ProductPlatformSkuModel $productPlatformSkuModel,
        ProductCombination $productCombination,
        ProductCombinationInfo $productCombinationInfo,
        ProductPresaleInfo $productPresaleInfo,
        ProductSalesPriceInfo $productSalesPriceInfo
    ) {
        $this->productPlatformSkuModel = $productPlatformSkuModel;
        $this->productCombination = $productCombination;
        $this->productCombinationInfo = $productCombinationInfo;
        $this->productPresaleInfo = $productPresaleInfo;
        $this->productSalesPriceInfo = $productSalesPriceInfo;
    }

    /**
     * 组合商品列表
     * @time 2021年07月02日
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productPlatformSkuModel->getList(Code::TYPE_COMBINATION));
    }

    /**
     * 系统组合商品列表
     * @param Request $request
     */
    public function systemGoodsList(Request $request)
    {
        return CatchResponse::success($this->productCombination->getSystemGoodsList());
    }

    /**
     * 保存组合商品映射关系
     * @time 2021年07月02日
     * @param Request $request
     */
    public function save(CombinationPlatformSkuRequest $request): \think\Response
    {
        $data = $request->post();
        // $productData = $this->productCombination->findBy($data['product_id']);
        $productData = $this->productCombination->where(['id' => $data['product_id'], 'shop_id' => $data['shop_id']])->find();
        if (!$productData) {
            return CatchResponse::fail('组合商品不存在', 301);
        }
        // 判断映射已经使用
        if ($this->productPlatformSkuModel->where(
            [
                'platform_code' => $data['platform_code'],
                'shop_id' => $productData['shop_id']
            ]
        )->find()) {
            return CatchResponse::fail('该店铺此商品映射关系已存在');
        }
        $data['product_code'] = $productData['code'];
        $data['shop_id'] = $productData['shop_id'];
        $data['company_id'] = Shop::where(['id' => $data['shop_id']])->value('company_id') ?? 0;
        $data['type'] = Code::TYPE_COMBINATION; // 组合商品
        return CatchResponse::success($this->productPlatformSkuModel->storeBy($data));
    }

    /**
     * 读取组合商品详情
     * @time 2021年07月02日
     * @param $id
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->productPlatformSkuModel->where(['id' => $id, 'type' => Code::TYPE_COMBINATION])->find());
    }

    /**
     * 更新组合商品映射关系
     * @time 2021年07月02日
     * @param Request $request
     * @param $id
     */
    public function update(CombinationPlatformSkuRequest $request, $id): \think\Response
    {
        $data = $request->post();
        $productData = $this->productCombination->where(['id' => $data['product_id'], 'shop_id' => $data['shop_id']])->find();
        if (!$productData) {
            return CatchResponse::fail('组合商品不存在', 301);
        }
        $this->productPlatformSkuModel->startTrans();
        // 判断映射已经使用
        if ($this->productPlatformSkuModel->where([
            'platform_code' => $data['platform_code'],
            'type' => Code::TYPE_COMBINATION,
            'shop_id' => $data['shop_id']
        ])
            ->whereNotIn('id', $id)->find()
        ) {
            return CatchResponse::fail('该店铺此商品映射关系已存在');
        }
        // 获取当前用户信息
        $user = request()->user();
        $data['update_by'] = $user['id'];
        if (!$this->productPlatformSkuModel->updateBy($id, $data)) {
            $this->productPlatformSkuModel->rollback();
        } else {
            $this->productPlatformSkuModel->commit();
        }
        return CatchResponse::success('编辑成功');
    }

    /**
     * 删除组合商品映射关系
     * @time 2021年07月02日
     * @param $id
     */
    public function delete($id): \think\Response
    {
        $data = $this->productPlatformSkuModel->where(['id' => $id, 'type' => Code::TYPE_COMBINATION])->find();
        if (!$data) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        // todo 判断是否有关联订单

        return CatchResponse::success($this->productPlatformSkuModel->deleteBy($id));
    }

    /**
     * 导出组合商品映射关系
     * @time 2021年07月02日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $res = $this->productPlatformSkuModel->getExportList(Code::TYPE_COMBINATION);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->productPlatformSkuModel->exportField();
        }

        $excel = new CommonExport();

        $url = $excel->export($res, $exportField, '组合商品编码映射');
        return  CatchResponse::success($url);
    }

    /**
     * 组合商品sku 导入 模板下载
     * @param Request $request
     */
    public function template(Request $request)
    {
        // 获取当前用户的角色ID
        return download(public_path() . 'template/skuMapImport.xlsx', 'skuMapImport.xlsx')->force(true);
    }
    /**
     * 组合商品编码映射导入
     */
    public function importSku(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {

        $file = $request->file();
        $data = $import->read($file['file']);
        $user = request()->user();
        // 获取客户的店铺列表

        $this->productPlatformSkuModel->startTrans();
        $dataObj = [];
        foreach ($data as $value) {
            // 查询店铺信息
            if (!$shopId = Shop::where(['is_status' => 1, 'shop_name' => $value[0]])->value('id')) {
                $dataObj['empty'][] = '店铺name：' . $value[0];
                continue;
            }
            // 查询组合商品erp编码是否存在
            if (!$productData = $this->productCombination->where(['code' => $value[2], 'shop_id' => $shopId])->find()) {
                $dataObj['empty'][] = '组合商品code：' . $value[2];
                continue;
            }

            if ($this->productPlatformSkuModel->where(['shop_id' => $productData['shop_id'], 'platform_code' => $value[1]])->find()) {
                $dataObj['repeat'][] = '店铺name：' . $value[0] . '; 平台商品code：' . $value[1];
                continue;
            }
            $row = [
                'is_disable' =>  1,
                'type' =>  Code::TYPE_COMBINATION,
                'product_id' => $productData['id'],
                'product_code' => trim($value[2]),
                'platform_code' => trim($value[1]),
                'company_id' => Shop::where(['id' => $productData['shop_id']])->value('company_id') ?? 0,
                'creator_id' => $user['id'],
                'shop_id' => $productData['shop_id']
            ];
            $dataObj['success'][] = $value[2];
            $this->productPlatformSkuModel->createBy($row);
        }
        $this->productPlatformSkuModel->commit();


        return CatchResponse::success($dataObj);
    }
}
