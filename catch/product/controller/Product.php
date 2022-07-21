<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-09 14:25:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-07 11:16:18
 * @Description:
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\request\ProductRequest;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\product\model\ProductAnnex;
use catchAdmin\product\model\ProductPrice;
use catcher\exceptions\FailedException;
use catchAdmin\product\request\ProductDevelopRequest;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\supply\model\PurchaseOrderProducts;
use catchAdmin\product\model\Product as productModel;
use catchAdmin\product\model\Category as categoryModel;
use catchAdmin\supply\model\Supply as supplyModel;
use catchAdmin\permissions\model\Users as usersModel;
use catchAdmin\basics\model\Company as companyModel;
use catchAdmin\product\model\ProductPlatformSku;
use catchAdmin\product\model\ProductCombination;
use catchAdmin\product\model\ProductCombinationInfo;
use catchAdmin\product\model\ProductPresaleInfo;
use catchAdmin\product\model\ProductSalesPriceInfo;
use catchAdmin\warehouse\model\WarehouseOrderProducts;
use catchAdmin\product\excel\CommonExport;
use catchAdmin\basics\model\Shop as shopModel;
use catchAdmin\supply\excel\CommonExport as commonExportModel;

use catcher\Code;
use catcher\Utils;

use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\basics\model\Currency;
use catcher\base\CatchRequest;

class Product extends CatchController
{
    protected $productModel;
    protected $productInfo;
    protected $productGroup;
    protected $productAnnex;
    protected $productPrice;
    protected $productPlatformSku;
    protected $shopModel;
    protected $productCombinationModel;
    protected $productCombinationInfoModel;

    public function __construct(
        ProductModel $productModel,
        ProductInfo $productInfo,
        ProductGroup $productGroup,
        ProductAnnex $productAnnex,
        ProductPrice $productPrice,
        ProductPlatformSku $productPlatformSku,
        shopModel $shopModel,
        ProductCombination $productCombinationModel,
        productCombinationInfo $productCombinationInfoModel
    ) {
        $this->productModel = $productModel;
        $this->productInfo = $productInfo;
        $this->productGroup = $productGroup;
        $this->productAnnex = $productAnnex;
        $this->productPrice = $productPrice;
        $this->productPlatformSku = $productPlatformSku;
        $this->shopModel = $shopModel;
        $this->productCombinationModel = $productCombinationModel;
        $this->productCombinationInfoModel = $productCombinationInfoModel;
    }

    /**
     * 列表
     * @time 2021年02月09日 14:25
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        //获取汇率 计算商品价格

        $rate = Currency::where('source_code', 'USD')->value('rate');
        $oversize = $request->param('oversize');
        return CatchResponse::paginate($this->productModel->getList($oversize)->each(function ($item) use ($rate) {
            $categoryData = categoryModel::where('id', $item->category_id)->find();
            $item['category_text'] = $categoryData['parent_name'] . '-' . $categoryData['name'];
            $item['supplier_name'] = $item['supplier_name'] ?? '-';
            $item['buy_price'] = $item->calPrice($rate);
        }));
    }

    /**
     * 保存信息
     * @time 2021年02月09日 14:25
     * @param Request $request
     */
    public function save(ProductRequest $request): \think\Response
    {

        $data = $request->post();
        $this->productModel->startTrans();
        // 如果有美元采购价格 人民币采购价格默认为0
        if (!empty($data['purchase_price_usd'])) {
            $data['purchase_price_rmb'] = 0;
        }
        if (!empty($data['purchase_price_rmb'])) {
            $data['purchase_price_usd'] = 0;
        }
        // 非开发商品默认已完成
        $data['source_status'] = 2;
        $data['status'] = 4;

        // 如果是内部员工商品
        if (empty($data['type'])) {
            $data['code'] = $this->productModel->createOrderNo($data['category_id']);
        }
        // 基础信息
        $product = $this->productModel->storeBy($data);
        // 包装信息写入
        if (!empty($request->param('packDataJson'))) {
            $lists = json_decode($request->param('packDataJson'), true);
            $arr = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['product_id'] = $product;
                $arr[$key]['creator_id'] = $data['creator_id'];
            }
            if (!$this->productInfo->insertAllBy($arr)) {
                $this->productModel->rollback();
            }
        }
        // 多箱包装包装信息详情
        if ((int)$request->param('packing_method') == 2 && !empty($request->param('groupDataJson'))) {
            $lists = json_decode($request->param('groupDataJson'), true);
            $arr = [];
            $dataSplit = $data;
            $list = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['name'] = $data['code'] . '-' . $id['name'];
                $arr[$key]['product_id'] = $product;
                $arr[$key]['creator_id'] = $data['creator_id'];
                $dataSplit['is_multi_split'] =  1;
                $dataSplit['code'] = $data['code'] . '-' . $id['name'];
                $dataSplit['packing_method'] = 1;
                $dataSplit['merge_num'] = 1;
                // 拆分多箱商品分组为单独商品
                if (!$idProduct = $this->productModel->createBy($dataSplit)) {
                    $this->productModel->rollback();
                }
                $list = $arr[$key];
                $list['product_id'] = $idProduct;

                // 生成商品详情
                if (!$this->productInfo->createBy($list)) {
                    $this->productModel->rollback();
                }
            }
            if (!$this->productGroup->insertAllBy($arr)) {
                $this->productModel->rollback();
            }
        }
        // 其他信息
        if (!empty($request->param('otherDataJson'))) {
            $lists = json_decode($request->param('otherDataJson'), true);
            $arr = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['product_id'] = $product;
                $arr[$key]['creator_id'] = $data['creator_id'];
            }
            if (!$this->productAnnex->insertAllBy($arr)) {
                $this->productModel->rollback();
            }
        }
        // 更新商品编码
        if (empty($data['type'])) {
            $this->productModel->updateOrderNo($data['category_id'], $data['code']);
        }

        $this->productModel->commit();
        return CatchResponse::success($product);
    }

    /**
     * 读取
     * @time 2021年02月09日 14:25
     * @param $id
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->productModel->findInfo($id));
    }

    /**
     * 更新
     * @time 2021年02月09日 14:25
     * @param Request $request
     * @param $id
     */
    public function update(ProductRequest $request, $id): \think\Response
    {
        $data = $request->post();
        // var_dump($data['hedge_price']); exit;
        // 不允许编辑商品编码
        unset($data['code']);
        // 不允许编辑商品分类

        // 不允许编辑客户
        // unset($data['company_id']);
        // unset($data['company_name']);
        unset($data['packDataJson']);
        unset($data['groupDataJson']);
        unset($data['otherDataJson']);
        // 默认已完成
        $data['source_status'] = 2;
        // $data['status'] = 4;
        // 查看基础信息是否存在
        $productData = $this->productModel->findBy($id);
        if (!$productData) {
            throw new FailedException('商品不存在');
        }
        $this->productModel->startTrans();
        // 修改商品
        $user = request()->user();
        $data['update_by'] = $user['id'];

        $code = [];
        if ((int)$productData['source'] == 2 && (int)$productData['status'] == 3) {
            $data['status'] = 4;
            $data['code'] = $this->productModel->createOrderNo($data['category_id']);
            $code = $data['code'];
            // 更新商品编码
            $this->productModel->updateOrderNo($data['category_id'], $data['code']);
        } else {
            // 不允许编辑类型
            unset($data['type']);
            // unset($data['category_id']);
        }
        // $this->productModel->where('id', $id)->update($data);
        $this->productModel->updateBy($id, $data);

        // 包装信息修改
        if (!empty($request->param('packDataJson'))) {
            $lists = json_decode($request->param('packDataJson'), true);
            $arr = [];
            foreach ($lists as $key => $ids) {
                $arr[$key] = $ids;
                $arr[$key]['product_id'] = $id;
            }
            // 查询是否存在商品详情信息
            $dataPack = $this->productInfo->where('product_id', $id)->find();
            if (!$dataPack) {
                // 不存在插入信息
                $this->productInfo->storeBy($arr[0]);
            } else {
                // 存在更新
                $this->productInfo->where('id', $dataPack->id)->update($arr[0]);
            }
            // 当商品类型 0-内部员工 1-客户商品
            if ((int)$productData['type'] == 0) {
                $productPriceData = $this->productPrice->where([['product_id', '=', $id], ['is_status', '=', 0]])->find();
                $arr[0]['packing_method'] = $data['packing_method'];
                $arr[0]['product_id'] = $id;
                if ((int) $productData['status'] == 1) {
                    $priceData = $this->productPrice->addPrice($data, $arr);
                    if (!empty($productPriceData['id'])) {
                        $priceData['id'] = $productPriceData['id'];
                        // 当存在未审核价格
                        $this->productPrice->updateBy($productPriceData['id'], $priceData);
                    } else {
                        // 当不存在未审核价格
                        $priceData['product_id'] = $id;
                        // 新增基准价格
                        $this->productPrice->storeBy($priceData);
                    }
                }
            }
        }
        // 删除商品多箱包装包装信息
        // $dataAll = $this->productGroup->getAllDelect($id);
        // if ($dataAll) {
        //     // 物理删除
        //     $this->productGroup->deleteBy($dataAll,  $force = true);
        // }
        // 多箱包装包装信息详情修改
        if ((int) $request->param('packing_method') == 2 && !empty($request->param('groupDataJson'))) {
            // 新增商品多箱包装
            $lists = json_decode($request->param('groupDataJson'), true);
            $arr = [];
            $dataSplit = $data;
            $list = [];
            unset($dataSplit['id']);
            // unset($dataSplit['code']);
            foreach ($lists as $key => $ids) {
                if (!empty($ids['id'])) {
                    // unset($ids['name']); // 不允许编辑分组名称
                    $arr[$key] = $ids;
                    $arr[$key]['product_id'] = $id;
                    $dataArr = $arr[$key];
                    $this->productGroup->updateBy($ids['id'], $dataArr);
                } else {
                    $arr[$key] = $ids;
                    $arr[$key]['name'] = $code ? $data['code'] . '-' . $ids['name'] : $id['name'];
                    $this->productGroup->createBy($arr[$key]);
                }
                if (!$this->productModel->where('code', $arr[$key]['name'])->find()) {
                    // 生成多箱分组商品
                    $dataSplit['is_multi_split'] =  1;
                    // $dataSplit['code'] = $data['code'] . '-' . $id['name'];
                    $dataSplit['code'] = $arr[$key]['name'];
                    $dataSplit['packing_method'] = 1;
                    $dataSplit['merge_num'] = 1;
                    $dataSplit['creator_id'] = $productData['creator_id'];
                    $dataSplit['is_disable'] = $productData['is_disable'];
                    $dataSplit['status'] = $productData['status'];

                    // 拆分多箱商品分组为单独商品
                    if (!$idProduct = $this->productModel->createBy($dataSplit)) {
                        $this->productModel->rollback();
                    }
                    $list = $arr[$key];
                    unset($list['id']);
                    $list['product_id'] = $idProduct;
                    // 生成商品详情
                    if (!$this->productInfo->createBy($list)) {
                        $this->productModel->rollback();
                    }
                }
            }
        }

        // 其他信息修改
        if (!empty($request->param('otherDataJson'))) {
            $lists = json_decode($request->param('otherDataJson'), true);
            $arr = [];
            foreach ($lists as $key => $ids) {
                $arr[$key] = $ids;
                $arr[$key]['product_id'] = $id;
            }
            $productAnnexData = $this->productAnnex->where('product_id', $id)->find();
            if ($productAnnexData) {
                $this->productAnnex->updateBy($productAnnexData['id'], $arr[0]);
            } else {
                if (!$this->productAnnex->insertAllBy($arr)) {
                    $this->productModel->rollback();
                }
            }
        }
        $this->productModel->commit();
        return CatchResponse::success('编辑成功');
    }

    /**
     * 删除
     * @time 2021年02月09日 14:25
     * @param $id
     */
    public function delete($id): \think\Response
    {
        // 查看商品是否有关联订单，有关联不可删除
        $productNum = OrderItemRecords::where('goods_id', $id)->count();
        if (!empty($productNum)) {
            return CatchResponse::fail('商品有关联销售订单不可删除', Code::FAILED);
        }
        // 关联商品组合
        $countCb = ProductCombinationInfo::where('product_id', $id)->count();
        if (!empty($countCb)) {
            return CatchResponse::fail('有关联商品组合不可删除', Code::FAILED);
        }
        // 关联商品映射
        $countSku = ProductPlatformSku::where('product_id', $id)->count();
        if (!empty($countSku)) {
            return CatchResponse::fail('有关联商品映射不可删除', Code::FAILED);
        }
        // 参加预售活动
        $countPresa = ProductPresaleInfo::where('product_id', $id)->count();
        if (!empty($countPresa)) {
            return CatchResponse::fail('有关联预售活动不可删除', Code::FAILED);
        }
        // 促销活动
        $countPrice = ProductSalesPriceInfo::where('product_id', $id)->count();
        if (!empty($countPrice)) {
            return CatchResponse::fail('有关联促销活动不可删除', Code::FAILED);
        }
        // 入库单含有
        $countWo =  WarehouseOrderProducts::where('goods_id', $id)->count();
        if (!empty($countWo)) {
            return CatchResponse::fail('有关联入库单不可删除', Code::FAILED);
        }
        // 是否关联采购单
        $num = PurchaseOrderProducts::where(['goods_id' => $id, 'type' => 1])->count();
        if (!empty($num)) {
            return CatchResponse::fail('有关联采购订单不可删除', Code::FAILED);
        }
        // 商品是否关联的
        return CatchResponse::success($this->productModel->deleteBy($id, $force = false));
    }

    /**
     * 审核
     * @time 2020/09/16
     * @param Request $request
     */
    public function examine(Request $request, $id): \think\Response
    {
        $dataParam = $request->post();
        if (!in_array($request->param('status'), [1, 2])) {
            throw new FailedException('参数不正确');
        }
        if (!$data = $this->productModel->findBy($id)) {
            return CatchResponse::fail('商品不存在', Code::FAILED);
        }
        if ((int)$data['status'] !== 0 && (int)$data['status'] !== 2) {
            return CatchResponse::fail('商品状态不正确', Code::FAILED);
        }

        $arr = $this->productInfo->where('product_id', $id)->select();
        // var_dump($data['type'], $request->param('status')); exit;
        // 当商品为多箱商品时候，审核时审核通过 拆分的分组
        $this->productModel->startTrans();
        if ((int)$data['packing_method'] == 2) {
            $productArr = $this->productGroup->where('product_id', $id)->select()->toArray();
            $codes = array_column($productArr, 'name');
            $codes_str = implode(',', $codes);
            $ids = $this->productModel->whereIn('code', $codes_str)->select()->toArray();
            foreach ($ids as $key => $value) {
                if (!$this->productModel->where('id', $value['id'])
                    ->update([
                        'status' => $dataParam['status'], 'is_disable' => $dataParam['status'],
                        'reason' => $dataParam['reason'], 'updated_at' => time()
                    ])) {
                    $this->productModel->rollback();
                    return CatchResponse::fail('分组审核失败');
                }
            }
        }
        // 如果是内部员工 添加商品 生成基准价格
        if ($data['type'] == 0) {
            if ((int)$request->param('status') == 1) {
                $arr[0]['packing_method'] = $data['packing_method'];
                $arr[0]['product_id'] = $id;
                // 获取基准价格计算
                $priceData = $this->productPrice->addPrice($data, $arr);
                $priceData['product_id'] = $id;
                // var_dump($priceData); exit;

                // 新增基准价格
                $this->productPrice->createBy($priceData);
                // 更新商品基准价格 benchmark_price
                $this->productModel->where('id', $id)->update(['benchmark_price' => $priceData['benchmark_price']]);
            }
        }
        if (!$this->productModel->examineBy($id, $request->param(), $data['type'])) {
            $this->productModel->rollback();
            return CatchResponse::fail('商品审核失败');
        }
        $this->productModel->commit();
        return CatchResponse::success('审核成功');
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
            $this->productModel->saveAll($list);
            // 修改映射关系禁用
            $this->productPlatformSku->uploadStatus($data['ids'], 2);
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
            $this->productModel->saveAll($list);
            // 修改映射关系启用
            $this->productPlatformSku->uploadStatus($data['ids'], 1);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 批量提交审核商品
     */
    public function batchExamine(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $company_id = $this->productModel->where('id', $val)->value('company_id');
                if ((int)$company_id !== 0) {
                    $row =  [
                        'id' => $val,
                        'status' => 0  //  0-待审核 1-审核通过 2-审核驳回 3-待编辑 4-待提交审核
                    ];
                    $list['success'][] = $row;
                } else {
                    $list['fail'][] = $val;
                }
            }
            $this->productModel->saveAll($list['success']);
            return CatchResponse::success($list);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 开发商品列表
     * @param Request $request
     */
    public function developList(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productModel->getDevelopList());
    }
    /**
     * 批量删除开发商品
     */
    public function delDevelop(Request $request)
    {

        $ids = $request->param('ids');
        $idsArr = explode(',', $ids);
        $count = $this->productModel->where(['source' => 2, 'source_status' => 1])
            ->whereIn('id', $ids)->count();
        if ($count != count($idsArr)) {
            return CatchResponse::fail('请检查删除数据是否正确', Code::FAILED);
        }
        $this->productModel->startTrans();
        // 删除主表
        if (!$this->productModel->deleteBy($ids, $force = false)) {
            $this->productModel->rollback();
            return CatchResponse::fail('删除失败', Code::FAILED);
        }
        // 删除其他信息
        productAnnex::destroy(function ($query) use ($ids) {
            $query->whereIn('product_id', $ids);
        });
        // 删除详情信息
        productInfo::destroy(function ($query) use ($ids) {
            $query->whereIn('product_id', $ids);
        });
        $this->productModel->commit();
        return CatchResponse::success('true');
    }
    /**
     * 添加开发商品
     */
    public function developAdd(ProductDevelopRequest $request)
    {

        $data = $request->post();
        // 开启事务
        $this->productModel->startTrans();
        // 如果有美元采购价格 人民币采购价格默认为0
        if (!empty($data['purchase_price_usd'])) {
            $data['purchase_price_rmb'] = 0;
        }
        // 开发商品未完成
        $data['source'] = 2; // 待开发商品
        $data['source_status'] = 1;
        $data['status'] = 3;  // 商品状态 待编辑
        $data['operate_type'] = 2; // 默认自营
        // 开发商品编码
        $data['code'] = $this->productModel->createOrderNoDevelopment();
        // 基础信息
        $product = $this->productModel->storeBy($data);
        // 其他信息
        if (!empty($product)) {
            $dataAnnex['product_id'] =  $product;
            $dataAnnex['size'] =  $data['size'] ?? '';
            $dataAnnex['weight'] =  $data['weight'] ?? '';
            $dataAnnex['color'] =  $data['color'] ?? '';
            $dataAnnex['material'] =  $data['material'] ?? '';
            $dataAnnex['other_remark'] =  $data['other_remark'] ?? '';
            $dataAnnex['updated_at'] = time();
            if (!$this->productAnnex->storeBy($dataAnnex)) {
                $this->productModel->rollback();
            }
        }
        // 详情信息
        if (!empty($request->param('unit')) || !empty($request->param('box_rate'))) {
            $dataInfo['product_id'] =  $product;
            $dataInfo['unit'] =  $data['unit'] ?? '';
            $dataInfo['box_rate'] =  $data['box_rate'] ?? '';
            if (!$this->productInfo->storeBy($dataInfo)) {
                $this->productModel->rollback();
            }
        }
        $this->productModel->commit();
        return CatchResponse::success($product);
    }
    /**
     * 开发商品模板下载
     * develop
     * @param Request $request
     */
    public function templateDevelop(Request $request)
    {
        return download(public_path() . 'template/productImportDevelop.xlsx', 'productImportDevelop.xlsx')->force(true);
    }
    /**
     * 批量导入开发商品
     * develop
     */
    public function developImprot(Request $request, ZipCodeImport $import)
    {
        $user = request()->user();
        $file = $request->file();
        $dataObj = $import->read($file['file']);
        $dataList = [];
        $dataAnnex = [];
        $dataInfo = [];
        $data = [];
        foreach ($dataObj as $value) {
            if (empty($value[0]) || empty($value[2]) || empty($value[4]) || empty($value[5]) || empty($value[6])) {
                throw new FailedException(sprintf('【%s】商品必填项不完整', $value[0]));
                continue;
            }
            // 获取分类id，不存在则返回异常
            if (!$category = categoryModel::where('name', trim($value[2]))->find()) {
                throw new FailedException(sprintf('【%s】商品分类【%s】不存在', $value[0], $value[2]));
            }
            // 采购员
            if (!$purchase_id = usersModel::where('username', trim($value[4]))->value('id')) {
                throw new FailedException(sprintf('【%s】采购员【%s】不存在', $value[1], $value[8]));
            }
            // 供应商
            $supplier_id = supplyModel::where('name', trim($value[3]))->value('id');

            // 开启事务
            $this->productModel->startTrans();
            if ($this->productModel->where(['name_ch' => $value[0]])
                ->find()
            ) {
                $dataList['repeat'][] = '商品名称：' . $value[0] . '重复';
                $this->productModel->rollback();
                continue;
            }
            // 开发商品未完成
            $data['source'] = 2; // 待开发商品
            $data['source_status'] = 1;
            $data['status'] = 3;  // 商品状态 待编辑
            $data['operate_type'] = 2; // 默认自营
            // 开发商品编码
            $data['code'] = $this->productModel->createOrderNoDevelopment();
            $data['name_ch'] = $value[0];
            $data['name_en'] = !empty($value[1]) ? ucwords(trim($value[1])) : '';
            // 分类
            $data['category_id'] = $category['id'];
            $data['ZH_HS'] = $category['ZH_HS']; // 国内HS
            $data['EN_HS'] = $category['EN_HS']; // 国外HS
            $data['tax_rebate_rate'] = $category['tax_rebate_rate']; // 国内退税率
            $data['tax_tariff_rate'] = $category['tax_tariff_rate']; // 国外退税率
            // 供应商 supplier_name
            $data['supplier_name'] = $value[3] ?? '';
            $data['supplier_id'] = $supplier_id ?? 0;
            // 采购员
            $data['purchase_name'] = $value[4];
            $data['purchase_id'] = $purchase_id;
            $data['creator_id'] = $user['id'];
            // 采购价格
            if ($value[5] == 'USD') {
                $data['purchase_price_usd'] = $value[6];
                $data['purchase_price_rmb'] = 0;
            } else {
                $data['purchase_price_usd'] = 0;
                $data['purchase_price_rmb'] = $value[6];
            }
            // 包装方式
            $data['packing_method'] = $value[13] == '多箱包装' ? 2 : 1;
            // 基础信息
            if (!$product = $this->productModel->storeBy($data)) {
                $dataList['empty'][] = '商品：' . $value[0] . '添加失败';
                $this->productModel->rollback();
                continue;
            }
            // 其他信息
            if (!empty($product)) {
                $dataAnnex['product_id'] =  $product;
                $dataAnnex['size'] =  $value[7] ?? '';
                $dataAnnex['weight'] =  $value[8] ?? '';
                $dataAnnex['color'] =  $value[9] ?? '';
                $dataAnnex['material'] =  $value[10] ?? '';
                $dataAnnex['other_remark'] =  $value[14] ?? '';
                $dataAnnex['updated_at'] = time();
                if (!$this->productAnnex->storeBy($dataAnnex)) {
                    $dataList['empty'][] = '商品：' . $value[0] . '添加失败';
                    $this->productModel->rollback();
                    continue;
                }
            }
            // 详情信息
            if (!empty($value[11]) || !empty($value[12])) {
                $dataInfo['product_id'] =  $product;
                $dataInfo['unit'] =  $value[11] ?? '';
                $dataInfo['box_rate'] =  $value[12] ?? '';
                if (!$this->productInfo->storeBy($dataInfo)) {
                    $dataList['empty'][] = '商品：' . $value[0] . '添加失败';
                    $this->productModel->rollback();
                    continue;
                }
            }
            $dataList['success'][] = '商品：' . $value[0];
            $this->productModel->commit();
        }
        return CatchResponse::success($dataList);
    }
    /**
     * 编辑开发商品
     */
    public function developEdit(ProductDevelopRequest $request, $id): \think\Response
    {

        $data = $request->post();
        // var_dump($data); exit;
        // 不允许编辑类型
        unset($data['type']);
        unset($data['source']);
        unset($data['code']);
        $user = request()->user();
        $data['update_by'] = $user['id'];
        // 开启事务
        $this->productModel->startTrans();
        // 如果有美元采购价格 人民币采购价格默认为0
        if (!empty($data['purchase_price_usd'])) {
            $data['purchase_price_rmb'] = 0;
        }
        // 商品编码
        // $data['code'] = $this->productModel->createOrderNo($data['category_id']);
        // 基础信息
        $product = $this->productModel->updateBy($id, $data);
        // 其他信息
        if (!empty($data['size'])) {
            $dataAnnex['size'] =  $data['size'] ?? '';
            $dataAnnex['weight'] =  $data['weight'] ?? '';
            $dataAnnex['color'] =  $data['color'] ?? '';
            $dataAnnex['material'] =  $data['material'] ?? '';
            $dataAnnex['other_remark'] =  $data['other_remark'] ?? '';
            $dataAnnex['product_id'] =  $id;
            if ($idAnnex = $this->productAnnex->where('product_id', $id)->value('id')) {
                if (!$this->productAnnex->updateBy($idAnnex, $dataAnnex)) {
                    $this->productModel->rollback();
                }
            } else {
                $this->productAnnex->storeBy($dataAnnex);
            }
        }
        // 详情信息
        if (!empty($data['unit']) || !empty($data['box_rate'])) {
            $dataInfo['unit'] =  $data['unit'] ?? '';
            $dataInfo['box_rate'] =  $data['box_rate'] ?? '';
            if ($idInfo = $this->productInfo->where('product_id', $id)->value('id')) {
                if (!$this->productInfo->updateBy($idInfo, $dataInfo)) {
                    $this->productModel->rollback();
                }
            } else {
                $dataInfo['product_id'] =  $id;
                $this->productInfo->storeBy($dataInfo);
            }
        }
        $this->productModel->commit();
        return CatchResponse::success($product);
    }

    /**
     * 开发商品转化正式商品
     */
    public function conversion(Request $request)
    {

        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'source_status' => 2  // 1-未完成 2-已完成
                ];
                $list[] = $row;
            }
            $this->productModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 订单商品列表
     */
    public function orderProductList(Request $request)
    {

        return CatchResponse::success($this->productModel->orderProductList($request->param()));
    }
    /**
     * 导出
     *
     * @time 2020年09月08日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $data = $request->post();
        $oversize = $data['oversize'] ?? '';
        $res = $this->productModel->getExportList($oversize);
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        $excel = new CommonExport();

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->productModel->exportField();
        }

        $url = $excel->export($res, $exportField, '商品导出');
        return  CatchResponse::success($url);
    }

    /**
     * 开发商品导出
     */
    public function developmentProductExport(Request $request)
    {

        $res = $this->productModel->exportList();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->productModel->developmentExportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '开发商品导出');

        return  CatchResponse::success($url);
    }

    /**
     * 商品批量导入 excel upload
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    public function productImport(Request $request, ZipCodeImport $import)
    {
        try {
            $file = $request->file();
            $data = $import->product($file['file']);
            $roleId = request()->user()->getRoles()[0]['id'];
            $this->productModel->startTrans();
            // $data[0]  商品基本信息  $data[1] 多箱包装商品分组信息
            foreach ($data[0] as &$value) {
                // 客户上传商品
                if ($roleId == config('catch.permissions.company_role')) {
                    // 保存基本信息
                    $this->productCompanyData($value);
                } else {
                    // 保存基本信息
                    $this->productData($value);
                }
            }
            if (isset($data[1]) && count($data[1]) > 0) {
                $this->productGroupData($data);
            }
            // 其他信息
            $this->productModel->commit();
        } catch (\Exception $e) {
            $this->productModel->rollback();
            $message = sprintf(
                "导入商品有误，错误信息:【%s】",
                $e->getCode() . ':' . $e->getMessage() .
                    ' in ' . $e->getFile() . ' on line ' . $e->getLine()
            );
            throw new FailedException($message);
        }
        return CatchResponse::success('导入成功');
    }

    /**
     * 商品模板下载
     * @param Request $request
     */
    public function template(Request $request)
    {
        // 获取当前用户的角色ID
        $roleId = $request->user()->getRoles()[0]['id'];
        // 客户商品
        if ($roleId == config('catch.permissions.company_role')) {
            return download(public_path() . 'template/productCompanyImport.xlsx', 'productImport.xlsx')->force(true);
        } else {
            return download(public_path() . 'template/productImport.xlsx', 'productImport.xlsx')->force(true);
        }
    }

    /**
     * 拼接存储客户商品数据
     * @param $value
     */
    public function productCompanyData(&$value)
    {
        // 获取分类id，不存在则返回异常
        if (!$category = categoryModel::where('name', $value[3])->find()) {
            throw new FailedException(sprintf('【%s】商品分类【%s】不存在', $value[1], $value[3]));
        }
        // 是否保价
        $value[10] = trim($value[10]) == '是' ? 1 : 0;
        // 包装方式
        $value[12] = trim($value[12]) == '普通包装' ? 1 : 2;
        // 商品基本信息
        $product = [
            'type' => 1, // 商品类型 0-内部员工 1-客户商品
            'source' => 1,
            'source_status' => 2,
            'status' => 0, // 商品状态 0-待审核
            'code' => $value[4], // 商品基本信息 编号
            'name_ch' => $value[1],  // 中文名称
            'name_en' => $value[2],  // 英文名称
            'category_id' => $category['id'], // 分类id
            'operate_type' => 2, // 运营类型 1-代营 2-自营
            'bar_code_upc' => $value[5], // upc编码
            'bar_code' => $value[6],  // 产品条码
            'bar_code2' => $value[7], // 产品条码2
            'purchase_price_usd' => $value[8], // 申报单价 USD
            'insured_price_usd' => $value[9], // 保险价值 USD
            'company_id' => companyModel::where('account_id', request()->user()->id)->value('id'),  // 所属客户
            'insured_price' => $value[10], // 是否保价 1-保价 0-不保价
            'hedge_price' => $value[11], // 保值
            'packing_method' => $value[12], // 包装方式 ：1-普通商品 2-多箱包装
            'image_url' => $value[35], // 封面图片
            'ZH_HS' => $category['ZH_HS'], // 国内HS
            'EN_HS' => $category['EN_HS'], // 国外HS
            'tax_rebate_rate' => $category['tax_rebate_rate'], // 国内退税率
            'tax_tariff_rate' => $category['tax_tariff_rate'], // 国外退税率
            'creator_id' => request()->user()->id,
        ];
        $product['merge_num'] = $product['packing_method'] == 1 ? $value[34] : 0; // 普通包装有可合并发货数量
        // 保存基本信息
        $value['product_id'] = $this->productModel->createBy($product);
        // 商品产品信息以及附件信息
        $this->productAnnex->createBy([
            'product_id' => $value['product_id'],
            'size' => $value[13], // 尺寸
            'weight' => $value[14], // 重量
            'color' => $value[15], // 颜色
            'material' => $value[16], // 材质
            'purpose' => $value[17], // 用途
            'other_remark' => $value[18], // 其他备注
            'pictures' => $value[36], // 整箱产品组成图片链接
            'detail_pictures' => $value[37], // 产品包装细节图链接
            'creator_id' => request()->user()->id,
        ]);
        // 普通包装商品
        if ((int)$product['packing_method'] == 1) {
            $this->productInfoCompanyData($value, $value['product_id']);
        }
    }

    /**
     * 拼接存储客户商品基本属性信息
     * @param $value
     * @param $productId
     * @return mixed
     */
    public function productInfoCompanyData($value, $productId)
    {
        // 包装类型
        $value[30] = $value[30] == '无包装' ? 0 : $value[30];
        $value[30] = $value[30] == '自带包装' ? 1 : $value[30];
        $value[30] = $value[30] == '特殊包装' ? 2 : $value[30];
        // 是否需要序列号
        $value[31] = $value[31] == '是' ? 1 : $value[31];
        $value[31] = $value[31] == '否' ? 0 : $value[31];
        // 是否带电
        $value[32] = $value[32] == '是' ? 1 : $value[32];
        $value[32] = $value[32] == '否' ? 0 : $value[32];
        // 打包设置
        $value[33] = $value[33] == '独立打包' ? 1 : $value[33];
        $value[33] = $value[33] == '不设置' ? 0 : $value[33];
        // 商品包装详情
        $info = [
            'product_id' => $productId, // 商品表主键
            'length' => $value[19], // 长cm
            'width' => $value[20], // 宽cm
            'height' => $value[21], // 高cm
            'volume' => round($value[19] * $value[20] * $value[21], 6), // 体积（m³）
            'length_AS' => round($value[19] * Utils::config('product.cm_to_in'), 6), // 美制长
            'width_AS' => round($value[20] * Utils::config('product.cm_to_in'), 6), // 美制宽
            'height_AS' => round($value[21] * Utils::config('product.cm_to_in'), 6), // 美制高
            'weight_gross' => $value[22], // 毛重 kg
            'weight_gross_AS' => round($value[22] * Utils::config('product.kg_to_pt'), 6), // 美制毛重 kg
            'weight' => $value[23], // 净重
            'weight_AS' => round($value[23] * Utils::config('product.kg_to_pt'), 6), // 美制净重
            'hq_size' => $value[24], // 40HQ装箱量
            'box_rate' => $value[25], // 箱率
            'transport_length' => $value[26], // 运输包装长cm
            'transport_width' => $value[27], // 运输包装宽cm
            'transport_height' => $value[28], // 运输包装高cm
            'unit' => $value[29], // 计量单位
            'transport_volume' => round($value[26] * $value[27] * $value[28], 6), // 运输外箱体积
            // 是否支持拆单
            'packing_type' => $value[30], // 包装类型
            'is_serial_number' => $value[31], // 是否需要序列号
            'is_electric' => $value[32], // 是否带电
            'pack_set' => $value[33], // 打包设置
            'creator_id' => request()->user()->id,
        ];
        // 美制体积
        $info['volume_AS'] = round($info['length_AS'] * $info['width_AS'] * $info['height_AS'], 6) ?? 0;
        // 体积重 体积(美制)*系数
        $info['volume_weight_AS'] = round($info['volume_AS'] / Utils::config('product.volume_factor'), 6) ?? 0;
        // oversize
        $info['oversize'] = round($info['length_AS'] + ($info['width_AS'] + $info['height_AS']) * 2, 6) ?? 0;
        return $this->productInfo->createBy($info);
    }

    /**
     * 拼接存储商品数据
     * @param $value
     */
    public function productData(&$value)
    {
        // 获取分类id，不存在则返回异常
        if (!$category = categoryModel::where('name', $value[3])->find()) {
            throw new FailedException(sprintf('【%s】商品分类【%s】不存在', $value[1], $value[3]));
        }
        // 运营类型
        $value[4] = $value[4] == '自营' ? 2 : 1;
        // 是否保价
        $value[12] = $value[12] == '是' ? 1 : 0;
        // 包装方式
        $value[14] = $value[14] == '普通包装' ? 1 : 2;
        // 供应商
        if (!$supplier_id = supplyModel::where('name', $value[7])->value('id')) {
            throw new FailedException(sprintf('【%s】所属运营商【%s】不存在', $value[1], $value[7]));
        }
        // 采购员
        if (!$purchase_id = usersModel::where('username', $value[8])->value('id')) {
            throw new FailedException(sprintf('【%s】采购员【%s】不存在', $value[1], $value[8]));
        }
        // 客户id
        if (!$company_id = companyModel::where('name', $value[9])->value('id')) {
            throw new FailedException(sprintf('【%s】所属客户【%s】不存在', $value[1], $value[9]));
        }
        // 商品基本信息
        $product = [
            'type' => 0, // 商品类型 0-内部员工 1-客户商品
            'source' => 1,
            'source_status' => 2,
            'status' => 0, // 商品状态 0-待审核
            'code' => $value[36] ?: $this->productModel->createOrderNo($category['id']), // 商品基本信息 编号
            'name_ch' => $value[1],  // 中文名称
            'name_en' => ucfirst($value[2]),  // 英文名称
            'category_id' => $category['id'], // 分类id
            'operate_type' => $value[4], // 运营类型 1-代营 2-自营
            'bar_code_upc' => $value[5], // upc编码
            'bar_code' => $value[6] ?: '',  // 产品条码
            'supplier_id' => $supplier_id, // 供应商
            'supplier_name' => $value[7],
            'purchase_name' => $value[8], // 采购员
            'purchase_id' => $purchase_id, // 采购员Id
            'company_id' => $company_id,  // 所属客户
            'purchase_price_rmb' => $value[11] == 'RMB' ? $value[10] : 0, // 采购价格RMB
            'purchase_price_usd' => $value[11] == 'USD' ? $value[10] : 0, // 采购价格USD
            'insured_price' => $value[12], // 是否保价 1-保价 0-不保价
            'hedge_price' => $value[13], // 保值
            'packing_method' => $value[14], // 包装方式 ：1-普通商品 2-多箱包装
            'image_url' => $value[33] ?: '', // 封面图片
            'ZH_HS' => $category['ZH_HS'], // 国内HS
            'EN_HS' => $category['EN_HS'], // 国外HS
            'tax_rebate_rate' => $category['tax_rebate_rate'], // 国内退税率
            'tax_tariff_rate' => $category['tax_tariff_rate'], // 国外退税率
            'creator_id' => request()->user()->id,
        ];
        $product['merge_num'] = $product['packing_method'] == 1 ? $value[32] : 0; // 普通包装有可合并发货数量

        // 保存基本信息
        $value['product_id'] = $this->productModel->createBy($product);
        $value['code'] = $product['code'];

        // 未上传商品编码时更新自动生成的商品编码
        if (empty($value[36])) {
            $this->productModel->updateOrderNo($category['id'], $product['code']);
        }
        // 商品产品信息以及附件信息
        $this->productAnnex->createBy([
            'product_id' => $value['product_id'],
            'size' => $value[15], // 尺寸
            'weight' => $value[16], // 重量
            'color' => $value[17], // 颜色
            'material' => $value[18], // 材质
            'parts' => $value[19], // 配件
            'other_remark' => $value[20], // 其他备注
            'pictures' => $value[34], // 整箱产品组成图片链接
            'detail_pictures' => $value[35], // 产品包装细节图链接
            'creator_id' => request()->user()->id,
        ]);

        // 普通包装商品
        if ($product['packing_method'] == 1) {
            $this->productInfoData($value, $value['product_id']);
        }
    }

    /**
     * 拼接存储商品基本属性信息
     * @param $value
     * @param $productId
     * @return mixed
     */
    public function productInfoData($value, $productId)
    {
        // 商品包装详情
        $info = [
            'product_id' => $productId, // 商品表主键
            'length' => $value[21] ?? 0, // 长cm
            'width' => $value[22] ?? 0, // 宽cm
            'height' => $value[23] ?? 0, // 高cm
            'volume' => round($value[21] * $value[22] * $value[23], 6) * pow(10, -6), // 体积（m³）
            'length_AS' => round($value[21] * Utils::config('product.cm_to_in'), 6), // 美制长
            'width_AS' => round($value[22] * Utils::config('product.cm_to_in'), 6), // 美制宽
            'height_AS' => round($value[23] * Utils::config('product.cm_to_in'), 6), // 美制高
            'weight_gross' => $value[24], // 毛重 kg
            'weight_gross_AS' => round($value[24] * Utils::config('product.kg_to_pt'), 6), // 美制毛重 kg
            'weight' => $value[25] ?? 0, // 净重
            // 'weight_AS' => round($value[25] * Utils::config('product.kg_to_pt'),6), // 美制净重
            'hq_size' => $value[26] ?? 0, // 40HQ装箱量
            'box_rate' => $value[27], // 箱率
            'transport_length' => $value[28] ?? 0, // 运输包装长cm
            'transport_width' => $value[29] ?? 0, // 运输包装宽cm
            'transport_height' => $value[30] ?? 0, // 运输包装高cm
            'unit' => $value[31], // 计量单位
            // 'outside_transport_volume' => round((float)$value[28] * (float)$value[29] * (float)$value[30],6)*pow(10,-6), // 运输外箱体积（m³）
            'creator_id' => request()->user()->id,
        ];
        // 美制净重
        if ((float)$info['weight'] > 0) {
            $info['weight_AS'] = round($info['weight'] * Utils::config('product.kg_to_pt'), 6); // 美制净重
        }
        if ((float)$info['transport_length'] > 0 && (float)$info['transport_width'] > 0 && (float)$info['transport_height'] > 0) {
            $info['outside_transport_volume'] = round((float)$value[28] * (float)$value[29] * (float)$value[30], 6) * pow(10, -6);
        }
        // 美制体积
        $info['volume_AS'] = round($info['length_AS'] * $info['width_AS'] * $info['height_AS'], 6);
        // 体积重 体积(美制)*系数
        $info['volume_weight_AS'] = round($info['volume_AS'] / Utils::config('product.volume_factor'), 6);
        // oversize
        $info['oversize'] = round($info['length_AS'] + ($info['width_AS'] + $info['height_AS']) * 2, 6);
        return $this->productInfo->createBy($info);
    }

    /** 导入多箱包装商品
     * @param $data
     */
    public function productGroupData($data)
    {
        if (!isset($data[1])) {
            return false;
        }
        $line = 0;
        $info = [];
        // 多箱包装商品信息拼接
        foreach ($data[1] as $value) {
            // 如果和上一个行号不一致则入库当前的多箱包装
            if (!empty($line) && $line != $value[0]) {
                $this->productInfo->createBy($info);
                $line = 0;
            }
            $group = [
                'product_id' => $data[0][$value[0]]['product_id'], // 商品表主键
                'name' => $data[0][$value[0]]['code'] . '-' . $value[1], // 名称
                'number' => 1, // 数量-默认1
                'length' => $value[2], // 长cm
                'width' => $value[3], // 宽cm
                'height' => $value[4], // 高cm
                'volume' => round($value[2] * $value[3] * $value[4], 6) * pow(10, -6), // 体积(m³)
                'length_AS' => round($value[2] * Utils::config('product.cm_to_in'), 6), // 美制长
                'width_AS' => round($value[3] * Utils::config('product.cm_to_in'), 6), // 美制宽
                'height_AS' => round($value[4] * Utils::config('product.cm_to_in'), 6), // 美制高
                'weight_gross' => $value[5], // 毛重 kg
                'weight_gross_AS' => round($value[5] * Utils::config('product.kg_to_pt'), 6), // 美制毛重 kg
                'weight' => (float)$value[6] > 0 ? $value[6] : 0, // 净重
                'weight_AS' => 0,
                'hedge_price' => $value[7] ?? 0,
                'creator_id' => request()->user()->id,
            ];
            // var_dump($group); exit;
            if ((float)$group['weight'] > 0) {
                $group['weight_AS'] = round($group['weight'] * Utils::config('product.kg_to_pt'), 6); // 美制净重
            }
            // 美制体积
            $group['volume_AS'] = round($group['length_AS'] * $group['width_AS'] * $group['height_AS'], 6);
            // 体积重 体积(美制)*系数
            $group['volume_weight_AS'] = round($group['volume_AS'] / Utils::config('product.volume_factor'), 6);
            // oversize
            $group['oversize'] = round($group['length_AS'] + ($group['width_AS'] + $group['height_AS']) * 2, 6);
            if (empty($line)) {
                //初次赋值明细信息
                $line = $value[0];
                $info['length'] = $info['width'] = $info['height'] = $info['volume'] = $info['length_AS'] =
                    $info['width_AS'] = $info['height_AS'] = $info['weight_gross'] = $info['weight_gross_AS'] =
                    $info['weight'] = $info['weight_AS'] = $info['volume_AS'] = $info['volume_weight_AS'] =
                    $info['oversize'] = 0;
            }
            if ($line == $value[0]) {
                // 若为包装方式为多箱包装时，明细分组中的尺寸相加汇总到产品信息尺寸上
                $info['length'] += $group['length'] * $group['number'];
                $info['width'] += $group['width'] * $group['number'];
                $info['height'] += $group['height'] * $group['number'];
                $info['volume'] += $group['volume'] * $group['number'];
                $info['length_AS'] += $group['length_AS'] * $group['number'];
                $info['width_AS'] += $group['width_AS'] * $group['number'];
                $info['height_AS'] += $group['height_AS'] * $group['number'];
                $info['weight_gross'] += $group['weight_gross'] * $group['number'];
                $info['weight_gross_AS'] += $group['weight_gross_AS'] * $group['number'];
                $info['weight'] += $group['weight'] * $group['number'];
                $info['weight_AS'] += $group['weight_AS'] * $group['number'];
                $info['volume_AS'] += $group['volume_AS'] * $group['number'];
                $info['volume_weight_AS'] += $group['volume_weight_AS'] * $group['number'];
                $info['oversize'] += $group['oversize'] * $group['number'];
                $info['product_id'] = $group['product_id'];
                $info['creator_id'] = request()->user()->id;
                $this->productGroup->createBy($group);
                // 最后一行数据直接入库
                if (end($data[1]) == $value) {
                    $this->productInfo->createBy($info);
                }
            }
        }
    }

    /**
     * 商品导入新模板（测试使用）
     */
    public function productImportTwo(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {

        $file = $request->file();
        $data = $import->readTwo($file['file']);
        // var_dump($data[0]); exit;
        // $data[0]  商品基本信息  $data[1] 商品包装信息  $data[2]其他信息 $data[3] 多箱包装商品分组信息
        foreach ($data[0] as $value) {
            //  var_dump($value); exit;
            // 获取分类id
            $category = categoryModel::where('name', $value[7])->find();
            // var_dump($category, $value[7]); exit;
            // $category_id = 139;
            // 运营类型
            $operate_type = $value[4] == '自营' ? '2' : '1';
            // 包装方式
            // $packing_method = $value[13] == '普通包装' ? '1' : '2';
            // 是否保价
            $insured_price = 0;
            // 供应商
            $supplier_id = supplyModel::where('name', $value[9])->value('id');
            // 采购员
            // $purchase_id = usersModel::where('username', $value[8])->value('id');
            $purchase_id = 52;
            $purchase_name = 'linda';
            // 客户id
            $company_id = 21;
            // 单位
            // $unit = DictionaryData::where('dict_data_name', $value[3])->value('id');
            if (!$this->productModel->where('code', $value[0])->find()) {
                $row = [
                    'type' => 0,
                    'source' => 1,
                    'state' => 0,
                    'source_status' => 2,
                    'code' => $value[0], // // 商品基本信息 编号
                    'name_ch' => $value[1],  // 中文名称
                    'name_en' => $value[2] ?? '',  // 英文名称
                    'category_id' => $category['id'], // 分类id
                    'operate_type' => $operate_type, // 运营类型 1-代营 2-自营
                    'bar_code_upc' => $value[10], // upc编码
                    'bar_code' => $value[6],  // 产品条码
                    'supplier_id' => $supplier_id, // 供应商
                    'purchase_name' => $purchase_name, // 采购员
                    'purchase_id' => $purchase_id, // 采购员Id
                    'company_id' => $company_id,  // 所属客户
                    'purchase_price_rmb' => $value[4] == 'CNY' ? $value[5] : '0.00', // 价格
                    'purchase_price_usd' => $value[4] == 'USD' ? $value[5] : '0.00',
                    'insured_price' => $insured_price, // 是否保价 1-保价 0-不保价
                    'packing_method' => 1, // 包装方式 ：1-普通商品 2-多箱包装
                    'image_url' => 'http://a.ittim.ltd:8809/images/20210509/5b63b35fdcccb0d4c94adc2f24fc5de8.jpg', // 封面图片
                    'creator_id' => request()->user()->id,
                    'ZH_HS' => $category['ZH_HS'], // 国内HS
                    'EN_HS' => $category['EN_HS'], // 国外HS
                    'tax_rebate_rate' => $category['tax_rebate_rate'], // 国内退税率
                    'tax_tariff_rate' => $category['tax_tariff_rate'], // 国外退税率
                ];
                // 保存基本信息

                if ($res = $this->productModel->createBy($row)) {
                    // 美制体积
                    $group['volume_AS'] = round($value[19] * $value[20] * $value[21], 6);
                    // 体积重 体积(美制)*系数
                    $group['volume_weight_AS'] = round($group['volume_AS'] / Utils::config('product.volume_factor'), 6);
                    // oversize
                    $group['oversize'] = round($value[19] + ($value[20] + $value[21]) * 2, 6);
                    // 美制毛重
                    $group['weight_gross_AS'] = round($value[17] * 2.2046226, 6);
                    // 体积
                    $group['volume'] = round($value[14] * $value[15] * $value[16], 6);

                    $rowProductInfo = [
                        'product_id' => $res,
                        'length' => $value[14], // 长cm
                        'width' => $value[15], // 宽
                        'height' => $value[16], // 高
                        'volume' => $group['volume'], // 体积
                        'length_AS' => $value[19], // 美制长
                        'width_AS' => $value[20], // 美制宽
                        'height_AS' => $value[21], // 美制高
                        'volume_AS' => $group['volume_AS'], // 美制体积
                        'volume_weight_AS' =>  $group['volume_weight_AS'], // 体积重 体积(美制)*系数
                        'oversize' => $group['oversize'], //
                        'transport_volume' => 0, //运输包装体积
                        'weight_gross' => $value[17], // 毛重 kg
                        'weight_gross_AS' => $group['weight_gross_AS'], // 美制毛重 kg
                        'weight' => $value[22], // 净重
                        'weight_AS' => $value[23], // 美制净重
                        'hq_size' => 0, // 40HQ装箱量
                        'box_rate' => $value[11], // 箱率
                        'unit' => $value[3], // 单位
                        'outside_transport_volume' => 0 // 运输外箱体积
                    ];
                    $this->productInfo->createBy($rowProductInfo);
                }
            }
            // 其他信息
        }
        return CatchResponse::success('导入成功');
    }

    /**
     * 多箱包装商品数据导入（客户模板）
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    public function productGroupTest(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->productGroupTest($file['file']);
        // 供应商
        $supplier_id = 104;
        // 采购员
        $purchase_id = 52;
        $purchase_name = 'linda';
        // 客户id
        $company_id = 21;
        $error = [];
        foreach ($data as $product) {
            // 保存商品基本信息
            if ($res = $this->productModel->createBy([
                'status' => 1, // 审核通过
                'is_disable' => 2, // 启用
                'source_status' => 2,
                'packing_method' => 2, // 多箱包装
                'code' => $product[0],
                'name_ch' => $product[1],
                'supplier_id' => $supplier_id, // 供应商
                'purchase_name' => $purchase_name, // 采购员
                'purchase_id' => $purchase_id, // 采购员Id
                'company_id' => $company_id,  // 所属客户
                'creator_id' => request()->user()->id
            ])) {
                $group = [];
                $count = 2;
                for ($x = $count; $x <= count($product); $x++) {
                    if ($x % 2 == 0 && !empty($product[$x])) {
                        array_push($group, [
                            'product_id' => $res,
                            'name' => $product[$x] ?? '',
                            'number' => 1,
                            'creator_id' => request()->user()->id
                        ]);
                    }
                }
                // 保存多箱商品
                $this->productGroup->insertAllBy($group);
            } else {
                // 添加失败的商品
                array_push($error, $product[0]);
            }
        }
        if (!empty($error)) {
            return CatchResponse::fail(implode(',', $error));
        }
        return CatchResponse::success('导入成功');
    }

    /**
     * 商品修改采购员
     */
    public function updatePurchase(Request $request, $id)
    {
        $data = $request->post();
        $productData = $this->productModel->where('id', $id)->find();
        // 判断采购员是否和创建人是一个人，如果是就同时修改创建人
        if ((int)$productData['creator_id'] == (int)$data['purchase_id']) {
            $userData['creator_id'] = $data['purchase_id'];
        }
        $userData['purchase_id'] = $data['purchase_id'];
        $usersModel  = new usersModel;
        if (!$user = $usersModel->where('id', $data['purchase_id'])->find()) {
            return CatchResponse::fail('采购员不存在');
        }
        $userData['purchase_name'] = $user['username'];

        $this->productModel->where('id', $id)->update($userData);

        return CatchResponse::success('采购员修改成功');
    }

    /**
     * 组合商品数据导入（客户模板）
     * @param Request $request
     * @param ZipCodeImport $import
     * @return \think\response\Json
     */
    // public function productCombination(Request $request, ZipCodeImport $import)
    // {
    //     $file = $request->file();
    //     $data = $import->productCombination($file['file']);
    //     $error = [];
    //     foreach ($data as $product) {
    //         // 判断店铺是否存在
    //         if (!$shopId = $this->shopModel->where(['shop_name' => trim($product[1])])->value('id')) {
    //             return CatchResponse::fail(sprintf('商家编码【%s】所在店铺【%s】不存在', $product[0], $product[1]));
    //         }
    //         // 开启事务
    //         $this->productCombinationModel->startTrans();
    //         // 添加组合商品
    //         if ($productCombinationId = $this->productCombinationModel->createBy([
    //             'code' => $product[0],
    //             'name_ch' => $product[2],
    //             'name_en' => $product[3],
    //             'price_usd' => $product[4],
    //             'shop_id' => $shopId,
    //             'creator_id' => request()->user()->id
    //         ])) {
    //             $group = [];
    //             $count = 5; // 循环起始列
    //             for ($x = $count; $x <= count($product); $x++) {
    //                 if ($x % 2 != 0 && !empty($product[$x])) {
    //                     // 判断商品是否存在
    //                     if (!$productId = $this->productModel->where(['code' => $product[$x]])->value('id')) {
    //                         // 失败
    //                         $this->productCombinationModel->rollback();
    //                         return CatchResponse::fail(sprintf('商家编码【%s】的单价SKU【%s】不存在', $product[0], $product[$x]));
    //                     }
    //                     array_push($group, [
    //                         'product_combination_id' => $productCombinationId, // 组合商品ID
    //                         'product_id' => $productId,
    //                         'price' => '', // 基准价
    //                         'number' => $product[$x + 1] ?? '',
    //                         'creator_id' => request()->user()->id
    //                     ]);
    //                 }
    //             }
    //             // 保存组合商品
    //             if ($this->productCombinationInfoModel->insertAllBy($group)) {
    //                 // 成功
    //                 $this->productCombinationModel->commit();
    //             } else {
    //                 // 失败
    //                 $this->productCombinationModel->rollback();
    //             }
    //         }
    //     }
    //     if (!empty($error)) {
    //         return CatchResponse::fail(implode(',', $error));
    //     }
    //     return CatchResponse::success('导入成功');
    // }

    /**
     * 查询商品供应商和采购价
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DbException
     */
    public function findProductPrice(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->productModel->findProductPrice());
    }

    /**
     * 商品供应商和采购价格导出
     */
    public function exportProductPrice(Request $request)
    {
        $data = $request->post();
        $res = $this->productModel->findAllProductPrice();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        $excel = new commonExportModel();

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->productModel->exportFieldProductPrice();
        }

        $url = $excel->export($res, $exportField, '商品价格供应商导出');
        return  CatchResponse::success($url);
    }
    /**
     * 查询店铺下商品（内部）
     */
    public function getShopProductList(Request $request, $id)
    {
        if (!$shop = $this->shopModel->findBy($id)) {
            return CatchResponse::success([]);
        }
        return CatchResponse::paginate($this->productModel->getShopProductList($shop['company_id']));
    }
    /**
     * 多箱商品商品列表分组信息查询
     */
    public function getMultiGroupList(Request $request)
    {

        return CatchResponse::paginate($this->productGroup->getMultiGroupList());
    }
}
