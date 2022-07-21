<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 10:26:57
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-10 15:15:07
 * @Description:
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\request\ProductPlatformSkuRequest;
use catchAdmin\product\model\ProductPlatformSku as productPlatformSkuModel;
use catcher\Code;
use catchAdmin\product\model\ProductCombinationInfo;
use catchAdmin\product\model\ProductPresaleInfo;
use catchAdmin\product\model\ProductSalesPriceInfo;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\product\model\Product;
use catchAdmin\basics\model\Shop;



class ProductPlatformSku extends CatchController
{
    protected $productPlatformSkuModel;
    protected $productCombinationInfo;
    protected $productPresaleInfo;
    protected $productSalesPriceInfo;

    public function __construct(
        ProductPlatformSkuModel $productPlatformSkuModel,
        ProductCombinationInfo $productCombinationInfo,
        ProductPresaleInfo $productPresaleInfo,
        ProductSalesPriceInfo $productSalesPriceInfo
    ) {
        $this->productPlatformSkuModel = $productPlatformSkuModel;
        $this->productCombinationInfo = $productCombinationInfo;
        $this->productPresaleInfo = $productPresaleInfo;
        $this->productSalesPriceInfo = $productSalesPriceInfo;
    }

    /**
     * 列表
     * @time 2021年02月24日 10:26
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        $type = $request->param('type') ?? Code::TYPE_PRODUCT;
        return CatchResponse::paginate($this->productPlatformSkuModel->getList($type));
    }

    /**
     * 系统商品列表
     * @param Request $request
     */
    public function systemGoodsList(Request $request)
    {
        $product = new Product;
        return CatchResponse::success($product->getSystemGoodsList());
    }

    /**
     * 组合商品-已经映射商品列表
     * @param Request $request
     */
    public function getProductList(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productPlatformSkuModel->getProductList());
    }

    /**
     * 保存信息
     * @time 2021年02月24日 10:26
     * @param Request $request
     */
    public function save(ProductPlatformSkuRequest $request): \think\Response
    {
        $data = $request->post();
        $productData = $this->productPlatformSkuModel->findProduct($data['product_id']);
        if (!$productData) {
            return CatchResponse::fail('商品不存在', 301);
        }
        // 判断映射已经使用
        if ($this->productPlatformSkuModel->where([
            'platform_code' => $data['platform_code'],
            'shop_id' => $data['shop_id']
        ])->find()) {
            return CatchResponse::fail('该店铺此商品映射关系已存在');
        }
        $data['product_code'] = $productData['code'];

        return CatchResponse::success($this->productPlatformSkuModel->storeBy($data));
    }

    /**
     * 读取
     * @time 2021年02月24日 10:26
     * @param $id
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->productPlatformSkuModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月24日 10:26
     * @param Request $request
     * @param $id
     */
    public function update(ProductPlatformSkuRequest $request, $id): \think\Response
    {
        $data = $request->post();
        $this->productPlatformSkuModel->startTrans();
        // 判断映射已经使用
        if ($this->productPlatformSkuModel->where([
            'platform_code' => $data['platform_code'],
            'shop_id' => $data['shop_id']
        ])
            ->whereNotIn('id', $id)->find()
        ) {
            return CatchResponse::fail('该店铺此商品映射关系已存在');
        }
        // 获取当前用户信息
        $data['update_by'] = $data['creator_id'];
        if (!$this->productPlatformSkuModel->updateBy($id, $data)) {
            $this->productPlatformSkuModel->rollback();
        } else {
            $this->productPlatformSkuModel->commit();
        }
        return CatchResponse::success('编辑成功');
    }

    /**
     * 删除
     * @time 2021年02月24日 10:26
     * @param $id
     */
    public function delete($id): \think\Response
    {
        $data = $this->productPlatformSkuModel->findBy($id);
        if (!$data) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        // $product_id = $data['product_id'];

        // 判断是否有预售活动商品
        // $productPresale = $this->productPresaleInfo->where('product_id', $product_id)->count();
        // if (!empty($productPresale)) {
        //     return CatchResponse::fail('有预售活动商品不可删除', Code::FAILED);
        // }

        // // 判断是否有促销商品
        // $productSalesPrice = $this->productSalesPriceInfo->where('product_id', $product_id)->count();
        // if (!empty($productSalesPrice)) {
        //     return CatchResponse::fail('有促销商品商品不可删除', Code::FAILED);
        // }

        // 判断是否有组合商品
        // $product_combination = $this->productCombinationInfo->where('product_id', $product_id)->count();
        // if (!empty($product_combination)) {
        //     return CatchResponse::fail('有组合商品不可删除', Code::FAILED);
        // }

        // 判断是否有关联订单

        return CatchResponse::success($this->productPlatformSkuModel->deleteBy($id));
    }

    /**
     * 导出
     * @time 2021年03月24日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request): \think\Response
    {
        $type = $request->post('type') ?? Code::TYPE_PRODUCT;
        $res = $this->productPlatformSkuModel->getExportList($type);
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

        $url = $excel->export($res, $exportField, '商品编码映射');
        return  CatchResponse::success($url);
    }
    /**
     * 商品sku 导入 模板下载
     * @param Request $request
     */
    public function template(Request $request)
    {
        // 获取当前用户的角色ID
        return download(public_path() . 'template/skuMapImport.xlsx', 'skuMapImport.xlsx')->force(true);
    }
    /**
     * 编码映射导入
     */
    public function importSku(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        ini_set('memory_limit', '1024M');
        $file = $request->file();
        $data = $import->read($file['file']);
        $user = request()->user();
        // 获取客户的店铺列表

        $this->productPlatformSkuModel->startTrans();
        $dataObj = [];
        foreach ($data as $value) {
            // 查询商品erp编码是否存在
            $product = new Product;
            // 只验证内部商品商品
            if (!$productData = $product->where(['code' => $value[2], 'type' => 0])->find()) {
                $dataObj['empty'][] = '商品code：' . $value[2];
                continue;
            }
            if (!$shopId = Shop::where(['is_status' => 1, 'shop_name' => $value[0]])->value('id')) {
                $dataObj['empty'][] = '店铺name：' . $value[0];
                continue;
            }
            if ($this->productPlatformSkuModel->where(['shop_id' => $shopId, 'platform_code' => $value[1]])->find()) {
                $dataObj['repeat'][] = '店铺name：' . $value[0] . '; 平台商品code：' . $value[1];
                continue;
            }
            $row = [
                'is_disable' =>  1,
                'product_id' => $productData['id'],
                'product_code' => $value[2],
                'platform_code' => $value[1],
                'company_id' => $productData['company_id'],
                'creator_id' => $user['id'],
                'shop_id' => $shopId
            ];
            $dataObj['success'][] = $value[2];
            $this->productPlatformSkuModel->createBy($row);
        }
        $this->productPlatformSkuModel->commit();


        return CatchResponse::success($dataObj);
    }
    /**
     * 编码映射客户模板(测试使用)
     */
    public function importSkuTwo(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        $file = $request->file();
        $data = $import->read($file['file']);
        $user = request()->user();
        // 获取客户的店铺列表

        $this->productPlatformSkuModel->startTrans();
        $dataObj = [];
        foreach ($data as $value) {
            // 查询商品erp编码是否存在
            $product = new Product;
            if (!$productData = $product->where(['code' => $value[2], 'type' => 0])->find()) {
                $dataObj['empty'][] = '商品code：' . $value[2];
                continue;
            }
            if (!$shopId = Shop::where(['is_status' => 1, 'shop_name' => $value[0]])->value('id')) {
                $dataObj['empty'][] = '店铺name：' . $value[0];
                continue;
            }
            if ($this->productPlatformSkuModel->where(['shop_id' => $shopId, 'platform_code' => $value[1]])->find()) {
                $dataObj['repeat'][] = '店铺name：' . $value[0] . '; 平台商品code：' . $value[1];
                continue;
            }
            $row = [
                'is_disable' =>  1,
                'product_id' => $productData['id'],
                'product_code' => $value[2],
                'platform_code' => $value[1],
                'company_id' => $productData['company_id'],
                'creator_id' => $user['id'],
                'shop_id' => $shopId
            ];
            $dataObj['success'][] = $value[2];
            $this->productPlatformSkuModel->createBy($row);
        }
        $this->productPlatformSkuModel->commit();

        return CatchResponse::success($dataObj);
    }
}
