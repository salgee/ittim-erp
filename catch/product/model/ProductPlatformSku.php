<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 10:26:57
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-11-20 11:03:30
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;

use catchAdmin\basics\model\Company;
use catchAdmin\basics\model\Shop;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\product\model\search\ProductPlatformSkuSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catcher\Code;


class ProductPlatformSku extends Model
{
    use DataRangScopeTrait;
    use ProductPlatformSkuSearch;
    // 表名
    public $name = 'product_platform_sku';
    // 数据库字段映射
    public $field = array(
        'id',
        // 1-启用 2-禁用
        'is_disable',
        // 产品id
        'product_id',
        // 产品编码
        'product_code',
        // 第三方平台商品编码
        'platform_code',
        // 客户（公司）id 关联表 company
        'company_id',
        // 关联商品类型（0-商品 1-组合商品）
        'type',
        // 店铺id
        'shop_id',
        // 修改人id
        'update_by',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );
    /**
     * 根据id查询商品
     */
    public function findProduct($id)
    {
        return Product::where('id', $id)->find();
    }

    /**
     * 商品列表
     * @param int $type
     * @return \think\Paginator
     */
    public function getList($type = Code::TYPE_PRODUCT)
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'pps.company_id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $where = [
                    ['pps.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $whereBasics = [];
        if ($type != Code::TYPE_PRODUCT_ALL) {
            $whereBasics = ['pps.type' => $type];
        }

        $select = $this->dataRange()
            ->catchJoin(Shop::class, 'id', 'shop_id', ['shop_name'])
            ->catchJoin(Company::class, 'id', 'company_id', ['name as company_name'])
            ->where($whereBasics)
            ->catchSearch()
            // 非全部SKU商品
            // ->whereOr($where)
            ->whereOr(function ($query) use ($where, $whereBasics) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where($whereBasics)
                        ->catchSearch();
                }
            })
            ->field('pps.*, u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('pps')
            ->order('pps.id', 'desc')
            ->leftJoin('users u', 'u.id = pps.creator_id')
            ->leftJoin('users us', 'us.id = pps.update_by')
            ->paginate();
        return $select;
    }

    /**
     * 获取 已经映射商品列表
     * @return \think\Paginator
     */
    public function getProductList()
    {
        $lists = $this
            ->catchJoin(Company::class, 'id', 'company_id', ['name as company_name'])
            ->catchSearch()
            ->field('pps.*, u.username as creator_name, IFNULL(us.username, "-") as update_name, p.unit,
            pi.image_url, pi.benchmark_price, pi.name_ch, pi.name_en, pi.type, pi.code, s.shop_name, 
            pi.supplier_id, sp.name as supplier_name, cg.name as category_name, cg.parent_name,
            pi.packing_method, pi.purchase_price_usd, pi.category_id, pi.purchase_price_rmb, pi.benchmark_price,
            pi.operate_type, pi.bar_code_upc, pi.bar_code, pi.status, pi.is_disable')
            ->alias('pps')
            ->order('pps.id', 'desc')
            ->leftJoin('shop_basics s', 's.id = pps.shop_id')
            ->leftJoin('product pi', 'pi.id = pps.product_id')
            ->leftJoin('supplies sp', 'sp.id = pi.supplier_id')
            ->leftJoin('category cg', 'cg.id = pi.category_id')
            ->leftJoin('product_info p', 'p.product_id = pps.product_id')
            ->leftJoin('users u', 'u.id = pps.creator_id')
            ->leftJoin('users us', 'us.id = pps.update_by')
            ->group('pps.product_id')
            ->paginate();
        return $lists;
    }

    /**
     * 获取店铺下已经映射组合商品
     * @param $shopId
     */
    public function getShopGoodsList($shopId)
    {
        return $this
            ->field('p.id, psku.id as sku_id,p.code, p.is_disable, p.name_ch, p.name_en, p.shop_id, p.price_usd, psku.platform_code as sku')
            ->alias('psku')
            ->where('p.shop_id', $shopId)
            ->where('psku.type', 1)
            ->order('p.id', 'desc')
            ->leftJoin('product_combination p', 'psku.product_id = p.id')
            // ->group('p.id')
            ->select();
    }

    /**
     * @param int $type
     * 导出数据
     */
    public function getExportList($type = Code::TYPE_PRODUCT)
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'pps.company_id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $where = [
                    ['pps.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $whereBasics = [];
        if ($type != Code::TYPE_PRODUCT_ALL) {
            $whereBasics = ['pps.type' => $type];
        }
        $fileList = [
            'pps.product_code', 'pps.platform_code',
            //  's.shop_name', 'c.name as company_name'
        ];
        $select = $this->dataRange()
            ->catchJoin(Shop::class, 'id', 'shop_id', ['shop_name'])
            ->catchJoin(Company::class, 'id', 'company_id', ['name as company_name'])
            ->field($fileList)
            ->alias('pps')
            ->catchSearch()
            ->where($whereBasics)
            // ->whereOr($where)
            ->whereOr(function ($query) use ($where, $whereBasics) {
                if (count($where) > 0) {
                    $query->where($where)
                        // ->where(['pps.type' => $type])
                        ->where($whereBasics)
                        ->catchSearch();
                }
            })
            // ->leftJoin('product pi', 'pps.product_id= pi.id')
            // ->leftJoin('shop_basics s', 's.id = pps.shop_id')
            // ->leftJoin('company c', 'c.id = pps.company_id')
            ->order('pps.id', 'desc')
            ->select()
            ->toArray();
        return $select;
    }

    /**
     * 通过店铺和sku查询映射的平台启用的商品
     * @param $shopId
     * @param $item
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductBySku($shopId, &$item)
    {
        if ($product = $this->field('sku.product_id, sku.product_code, sku.company_id, sku.type')->alias('sku')
            ->where(['shop_id' => $shopId, 'platform_code' => $item['sku'], $this->aliasField('is_disable') => 1])
            ->select()->toArray()
        ) {
            $item['goods_id'] = $product[0]['product_id'] ?? 0;
            $item['goods_code'] = $product[0]['product_code'] ?? '';
            //                // 商品类型 0-内部员工 1-客户商品
            //                $product[0]['product_type'] ?? 0;
            // 所属客户id 关联 company
            $item['company_id'] = $product[0]['company_id'] ?? 0;
            // 关联商品类型（0-商品 1-组合商品）
            $item['goods_type'] = $product[0]['type'] ?? 0;
        }
    }

    /**
     * 批量修改商品启用禁用状态
     * @param $ids 商品ID集合
     * @param $type 1-启用 2-禁用
     */
    public function uploadStatus($ids, $type)
    {
        $idsString = implode(",", $ids);
        return $this->whereIn('product_id', $idsString)->update(['is_disable' => $type]);
    }

    /**
     * 查询店铺中是否有此商品，并返回商品id
     * @param $shopId
     * @param $sku
     * @param $platformSku 第三方平台sku
     * @return mixed
     */
    public function checkProductSKUByShopId($shopId, $sku, $platformSku = '')
    {
        return $this->field('platform_code, product_id')
            ->distinct()
            ->where('platform_code', $platformSku)
            ->where('product_code', $sku)
            ->where('shop_id', $shopId)
            ->where('is_disable', 1)
            ->find();
    }
    /**
     * 查询店铺商品映射是否存在
     */
    public function checkProductSKUByShopIdNew($shopId, $platformSku)
    {
        return $this->field('platform_code, product_id, product_code, type')
            ->distinct()
            ->where('platform_code', $platformSku)
            ->where('shop_id', $shopId)
            ->where('is_disable', 1)
            ->find();
    }


    /**
     * 商品编码映射导出数据
     */
    public function exportField()
    {
        return [
            [
                'title' => 'ERP编码',
                'filed' => 'product_code',
            ],
            [
                'title' => '店铺对应编码',
                'filed' => 'platform_code',
            ],
            [
                'title' => '所属公司',
                'filed' => 'company_name',
            ],
            [
                'title' => '所属店铺',
                'filed' => 'shop_name',
            ]
        ];
    }

}
