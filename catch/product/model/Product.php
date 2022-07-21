<?php
/*
 * @Version: 1.0
 * @Date: 2021-02-09 14:25:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-01 16:29:13
 * @Description:
 */

namespace catchAdmin\product\model;

use catchAdmin\product\model\ProductInfo;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\product\model\ProductAnnex;
use catchAdmin\product\model\ProductPrice;
use catchAdmin\product\model\search\ProductSearch;
use catchAdmin\supply\model\Supply;
use catchAdmin\basics\model\Company;
use catchAdmin\product\model\Category;
use catcher\base\CatchModel as Model;
use catchAdmin\product\model\ProductPresale;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\basics\model\Shop;
use catchAdmin\product\model\ProductSalesPrice;
use catchAdmin\product\model\ProductSalesPriceInfo;
use catchAdmin\permissions\model\Users;
use catcher\Code;
use think\facade\Cache;




class Product extends Model
{
    use DataRangScopeTrait;
    use ProductSearch;
    // 表名
    public $name = 'product';
    // 数据库字段映射
    public $field = array(
        'id',
        // 状态 2-禁用 1-启用
        'is_disable',
        // 商品类型 0-内部员工 1-客户商品
        'type',
        // 1-常规商品  2-待开发商品 来源
        'source',
        // 当 source=2 时候 建档状态  1-未完成 2-已完成
        'source_status',
        // 审核状态， 0-待审核 1-审核通过 2-审核驳回 3-待编辑 4-待提交审核
        'status',
        // 驳回原因
        'reason',
        // 封面图
        'image_url',
        // 二级分类id 关联 category
        'category_id',
        // 编码
        'code',
        // 中文名称
        'name_ch',
        // 英文名称
        'name_en',
        // 保险价值
        'insured_price_usd',
        // 运营类型：1-代营 2-自营
        'operate_type',
        // 国内(HS)
        'ZH_HS',
        // 国外(HS)
        'EN_HS',
        // 国内退税率
        'tax_rebate_rate',
        // 国外关税税率
        'tax_tariff_rate',
        // upc条码
        'bar_code_upc',
        // 产品条码
        'bar_code',
        // 产品条码2
        'bar_code2',
        // 供应商名称
        'supplier_name',
        // 供应商id 关联 supplier
        'supplier_id',
        // 采购员
        'purchase_name',
        // 采购员id 关联 users
        'purchase_id',
        // 所属客户id 关联 company
        'company_id',
        // 采购价格-rmb
        'purchase_price_rmb',
        // 采购价格-usd
        'purchase_price_usd',
        // 基准价格
        'benchmark_price',
        // 是否保价：1-保价 0-不保价
        'insured_price',
        // 包装方式 ：1-普通商品 2-多箱包装
        'packing_method',
        // 普通商品 可合并发货数量
        'merge_num',
        // 保值
        'hedge_price',
        // 是否多箱拆分商品 0-否 1-是
        'is_multi_split',
        // 修改人
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

    protected $append = [
        // 'packing_method_text',
        'category_name'
    ];

    // public function getPackingMethodTextAttr() {
    //     return $this->getAttr('packing_method') == 1 ? '普通商品' : '多箱包装';
    // }

    public function getCategoryNameAttr()
    {
        return Category::where('id', $this->getAttr('category_id'))->value('name') ?? '';
    }

    /**
     * 商品列表
     * @return \think\Paginator
     */
    public function getList($oversize = '')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 客户角色
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
                // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $where = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $where = ['cp.user_type' => 0];
                    }
                }
            }
        }
        // var_dump('>>>>>', $where);
        // exit;
        $whereAll = [];
        if ($oversize == 1) {
            $whereAll = ('CONVERT ( pi.oversize USING utf8 ) >= 130');
        }
        if ($oversize == 2) {
            $whereAll = ('CONVERT ( pi.oversize USING utf8 ) <= 130');
        }
        $list = $this
            ->dataRange()
            ->catchSearch()
            ->field('p.*, pi.unit,  pi.oversize, pi.box_rate, s.name as supplier_names, c.name as category_name, u.username as creator_name,
            IFNULL(us.username, "-") as update_name, IFNULL(cp.name, "-") as company_name, cp.user_type')
            ->alias('p')
            // ->where($where)
            ->where($whereAll)
            ->where('source_status', 2)
            ->catchSearch()
            // return  $list
            ->whereOr(function ($query) use ($where, $whereAll) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where($whereAll)
                        ->where('source_status', 2)
                        ->catchSearch();
                }
            })
            ->order('p.id', 'desc')
            // ->where('source_status', 2)
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->leftJoin('product_info pi', 'pi.product_id = p.id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
        // ->fetchSql()->find(1);
        // var_dump($list);
        // exit;
        return $list;
    }
    /**
     * 所有商品列表 getSystemGoodsList
     */
    public function getSystemGoodsList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 客户角色
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
                // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $where = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $where = ['cp.user_type' => 0];
                    }
                }
            }
        }
        return $this
            ->dataRange()
            ->catchSearch()
            ->field('p.id, p.code, p.is_disable, p.type, p.name_ch, p.name_en, p.company_id,
            p.operate_type')
            ->alias('p')
            // ->where($where)
            // ->where('source_status', 2)
            // ->whereOr($where)
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where(['source_status' => 2, 'type' => 0, 'status' => 1])
                        ->catchSearch();
                }
            })
            ->order('p.id', 'desc')
            ->where(['source_status' => 2, 'type' => 0, 'status' => 1])
            ->select();
    }

    /**
     * 获取客户商品列表
     * @param $id
     */
    public function getCustomerProduct($id)
    {
        return $this->alias('p')
            ->field('p.id, p.is_disable, p.type, p.status, p.image_url, p.category_id,
            p.code, p.name_ch, p.name_en, p.operate_type, p.tax_tariff_rate, p.company_id,
            p.purchase_price_rmb, p.purchase_price_usd, p.benchmark_price, p.packing_method,
            c.name as category_name, c.parent_name')
            ->where([
                'company_id' => $id, 'is_disable' => 1,
                'type' => 1, 'status' => 1
            ])
            ->leftJoin('category c', 'c.id = p.category_id')
            ->select();
    }

    /**
     * 商品详情
     * @return object
     */
    public function findInfo($id)
    {
        $data = $this->field('p.*, c.name as category_name, s.name as supplier_names, co.name as company_name,
            u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->where('p.id', $id)
            ->alias('p')
            ->leftJoin('company co', 'co.id = p.company_id')
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->find();
        // 包装信息
        $data['packData'] = ProductInfo::withoutField(['creator_id', 'update_by'])->where(['product_id' => $id])->find();
        // 多箱包装包装信息详情
        $data['groupData'] = ProductGroup::withoutField(['creator_id', 'update_by'])->where(['product_id' => $id])->select();
        // 其他信息
        $data['otherData'] = ProductAnnex::withoutField(['creator_id', 'update_by'])->where(['product_id' => $id])->find();
        // 分类信息
        $data['categoryData'] = Category::field('id, parent_id, parent_name, name')->where('id', $data['category_id'])->find();
        return $data;
    }

    /**
     * 审核商品
     * @return boolean
     */
    public function examineBy($id, $param, $type = 0)
    {
        // 审核商品
        $product = $this->where('id', $id)
            ->update(['status' => $param['status'], 'is_disable' => $param['status'], 'reason' => $param['reason']]);
        $user = request()->user();
        // 修改商品价格审核状态
        $price = [];
        if ($param['status'] == 1 && $type == 0) {
            $price = ProductPrice::where(['product_id' => $id])
                ->update(['status' => $param['status'], 'is_status' => 1, 'reason' => $param['reason'], 'update_by' => $user['id'], 'updated_at' => time()]);
        }

        return $product;
    }
    /**
     * 开发商品编码
     */
    public function createOrderNoDevelopment()
    {

        $count = Cache::get(Code::CACHE_PRODUCT . 'development');
        $num = $count + 1;
        $str = sprintf("%03d", $num);
        Cache::set(Code::CACHE_PRODUCT . 'development', (int)$num);
        return 'development-product' . $str;
    }
    /**
     * 商品编码
     */
    public  function createOrderNo($id)
    {

        $twoTree = Category::where('id', $id)->find();
        $oneTree = Category::where('id', $twoTree['parent_id'])->find();
        // CACHE_PRODUCT Cache::get(Code::CACHE_PRESALE . $data['shop_basics_id'] . '_' . $data['product'][0]['goods_id']);

        // Cache::get(Code::CACHE_PRODUCT.$id);
        // 获取编码缓存
        $count = Cache::get(Code::CACHE_PRODUCT . $id);
        // $count = $this->where('created_at', '<', time())->count();

        $num = $count + 1;
        $str = sprintf("%03d", $num);

        return $oneTree['code'] . "-" . $twoTree['code'] . $str;
    }
    /**
     * 更新当前商品编码
     */
    public function updateOrderNo($id, $code)
    {
        $count = substr($code, -3);
        // 存入编码更新缓存
        Cache::set(Code::CACHE_PRODUCT . $id, (int)$count);
        return true;
    }

    /**
     * 获取开发商品列表
     * @return \think\Paginator
     */
    public function getDevelopList()
    {
        // $users = new Users;
        // $prowerData = $users->getRolesList();
        // $where = [];
        // if (!$prowerData['is_admin']) {
        //     if ($prowerData['is_company']) {
        //         $where = [
        //             'p.company_id' => $prowerData['company_id']
        //         ];
        //     }
        // }

        return $this->dataRange()
            // ->catchJoin(Supply::class, 'id', 'supplier_id', ['name as supplier_name'])
            ->catchSearch()
            ->field('p.id, p.code, p.category_id, p.name_ch, p.name_en, p.supplier_id, p.purchase_price_rmb,p.supplier_name,
                p.purchase_price_usd, p.created_at, p.updated_at, p.source_status, p.packing_method, p.purchase_id, p.purchase_name, 
                pi.unit, c.name as category_name, c.parent_name, u.username as creator_name,
                s.name as supplier_names, IFNULL(us.username, "-") as update_name, pi.oversize')
            ->alias('p')
            ->order('p.id', 'desc')
            // ->whereOr($where)
            // ->whereOr(function ($query) use ($where) {
            //     if (count($where) > 0) {
            //         $query->where($where)
            //             ->where(['source' => 2])
            //             ->catchSearch();
            //     }
            // })
            // ->where($where)
            ->where('source', 2)
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('product_info pi', 'pi.product_id = p.id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
    }

    /**
     * id获取指定批量商品
     */
    public function groupInfo($ids)
    {
        return $this->field('p.id, p.image_url, p.code, p.name_ch, p.name_en, p.packing_method, p.is_disable, p.status, p.category_id,
            c.name as category_name, c.parent_name')
            ->alias('p')
            ->where('p.id', 'IN', $ids)
            ->leftJoin('category c', 'c.id = p.category_id')
            ->select();
    }

    /**
     * 获取订单选择商品列表
     * 客户  company
     * 店铺  shop_basics  company_id
     * 根据店铺id->查询用户 ->用户下商品 company_id
     */
    public function orderProductList($data)
    {
        $shop_id = $data['shop_id'];
        $company_id = Shop::where('id', $data['shop_id'])->value('company_id');
        $ids = ProductPresale::join('product_presale_info ppi', 'ppi.product_presale_id=product_presale.id')
            ->where('product_presale.shop_id', '=', $shop_id)
            ->where('product_presale.is_disable', '=', '1')
            ->whereTime('product_presale.end_time', '>', time())
            ->whereTime('product_presale.start_time', '<', time())
            ->column('ppi.product_id');
        // 去重预售商品
        $ids = array_unique($ids);
        // $idsString = implode(",", $ids);
        // 促销商品  ProductSalesPrice
        $salesIdsData = ProductSalesPrice::join('product_sales_price_info psi', 'psi.product_sales_price_id = product_sales_price.id ')
            ->where('product_sales_price.is_disable', '=', '1')
            ->where('product_sales_price.shop_id', '=', $shop_id)
            ->whereTime('product_sales_price.end_time', '>', time())
            ->whereTime('product_sales_price.start_time', '<', time())
            ->column('psi.product_id, psi.product_sales_price_id');
        $salesIdsProduct = [];
        $salesIdsPriceOrder = [];
        foreach ($salesIdsData as $value) {
            $salesIdsProduct[] = $value['product_id'];
            $salesIdsPriceOrder[] = $value['product_sales_price_id'];
        }
        // 去重促销id
        $salesIdsPriceOrder = array_unique($salesIdsPriceOrder);
        // 去重促销商品
        $salesIds = array_unique($salesIdsProduct);
        // 数组
        $dataList = $this->field('p.id, p.code, p.name_ch, p.name_en, p.image_url, p.packing_method, p.purchase_price_rmb, p.benchmark_price, p.type, p.image_url,
            p.purchase_price_usd, p.tax_tariff_rate, p.tax_rebate_rate, psku.platform_code, psku.shop_id, psku.company_id, psku.platform_code as sku,
            p.category_id, cg.name as category_name, cg.parent_name ')
            ->alias('p')
            ->distinct(true)
            ->leftJoin('category cg', 'cg.id = p.category_id')
            ->leftJoin('product_platform_sku psku', 'psku.product_id = p.id and psku.is_disable = 1 and psku.shop_id=' . $shop_id . ' and psku.company_id=' . $company_id)
            ->where('p.company_id', $company_id)
            ->where('psku.company_id', $company_id)
            // ->leftJoin('product_sales_price_info psp', 'psp.product_id = p.id')
            // ->whereNotIn('p.id', $idsString)
            ->select()
            ->each(function ($item) use ($ids, $salesIds, $salesIdsPriceOrder) {
                // 是否预售商品
                $item['is_presale'] = in_array($item['id'], $ids);
                // 是否促销商品
                $item['is_sales'] = in_array($item['id'], $salesIds);
                $item['dataSales'] = ProductSalesPriceInfo::field('sales_price,price,product_sales_price_id')
                    ->where('product_id', $item['id'])
                    ->whereIn('product_sales_price_id', $salesIdsPriceOrder)
                    ->find();
                $item['purchase_benchmark_price'] = ProductPrice::where(['is_status' => 1, 'status' => 1, 'product_id' => $item['id']])
                    ->value('purchase_benchmark_price');
            });
        return $dataList;
    }
    /**
     * 导出未开发商品
     * @param  $fileData
     */
    public function exportList()
    {
        // $users = new Users;
        // $prowerData = $users->getRolesList();
        // $where = [];
        // if (!$prowerData['is_admin']) {
        //     if ($prowerData['is_company']) {
        //         $where = [
        //             'p.company_id' => $prowerData['company_id']
        //         ];
        //     }
        // }
        return $this->dataRange()
            ->field([
                'p.*', 'p.code as goods_code', 'pi.*', 'pg.size', 'pg.weight', 'pg.color', 'pg.material', 'pg.parts',
                'pg.other_remark',
                'cg.name as category_name', 'cg.parent_name', 'sp.name as supplier_names'
            ])
            ->alias('p')
            ->catchSearch()
            // ->where($where)
            // ->whereOr(function ($query) use ($where) {
            //     if (count($where) > 0) {
            //         $query->where($where)
            //             ->where(['source' => 2])
            //             ->catchSearch();
            //     }
            // })
            ->where('source', 2)
            ->leftJoin('product_group_annex pg', 'pg.product_id=p.id')
            ->leftJoin('product_info pi', 'pi.product_id=p.id')
            ->leftJoin('company cp', 'cp.id=p.company_id')
            ->leftJoin('supplies sp', 'sp.id=p.supplier_id')
            ->leftJoin('category cg', 'cg.id=p.category_id')
            ->select()->each(function (&$item) {
                $item['category_name_new'] = $item['parent_name'] . '-' . $item['category_name'];
                $item['packing_method_text'] = $item['packing_method'] == 1 ? '普通包装' : '多箱包装';
                $item['type_text'] = $item['type'] == $this::ENABLE ? '客户商品' : '内部商品';
            })->toArray();
    }

    /**
     * 商品导出
     */
    public function getExportList($oversize = '')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 客户角色
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
                // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $where = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $where = ['cp.user_type' => 0];
                    }
                }
            }
        }
        $whereAll = [];
        if ($oversize == 1) {
            $whereAll = ('CONVERT ( pi.oversize USING utf8 ) >= 130');
        }
        if ($oversize == 2) {
            $whereAll = ('CONVERT ( pi.oversize USING utf8 ) <= 130');
        }

        $fileList = [
            'p.*', 'p.code as goods_code',
            'pg.*', 'pi.*', 'pi.oversize', 'cp.name as company_name', 'sp.name as supplie_name', 'cg.parent_name', 'cg.name as category_name',
            'pgd.length as length_d', 'pgd.width as width_d', 'pgd.height as height_d', 'pgd.volume as volume_d',
            'pgd.weight_gross as weight_gross_d', 'pgd.weight as weight_d', 'pgd.length_AS as length_AS_d',
            'pgd.width_AS as width_AS_d', 'pgd.height_AS as height_AS_d', 'pgd.volume_AS as volume_AS_d',
            'pgd.weight_AS as weight_AS_d', 'pgd.volume_weight_AS as volume_weight_AS_d',
            'pgd.oversize as oversize_d', 'pgd.weight_gross_AS as weight_gross_AS_d', 'pgd.name as goods_name'
        ];
        $list = $this
            ->dataRange()
            ->field($fileList)
            ->alias('p')
            ->catchSearch();
        return $list
            ->where('source_status', 2)
            ->catchSearch()
            ->whereOr(function ($query) use ($where, $whereAll) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where($whereAll)
                        ->where('source_status', 2)
                        ->catchSearch();
                }
            })
            ->leftJoin('product_group_annex pg', 'pg.product_id=p.id')
            ->leftJoin('product_group pgd', 'pgd.product_id=p.id')
            ->leftJoin('product_info pi', 'pi.product_id=p.id')
            ->leftJoin('company cp', 'cp.id=p.company_id')
            ->leftJoin('supplies sp', 'sp.id=p.supplier_id')
            ->leftJoin('category cg', 'cg.id=p.category_id')
            ->select()->each(function (&$item) {
                $item['category_name_new'] = $item['parent_name'] . '-' . $item['category_name'];
                $item['packing_method_text'] = $item['packing_method'] == $this::ENABLE ? '普通包装' : '多箱包装';
                $item['type_text'] = $item['type'] == $this::ENABLE ? '客户商品' : '内部商品';
                $item['operate_type_text'] = $item['operate_type'] == $this::ENABLE ? '代营' : '自营';
                if ($item['packing_method'] == 2) {
                    $item['length'] = $item['length_d'];
                    $item['width'] = $item['width_d'];
                    $item['height'] = $item['height_d'];
                    $item['volume'] = $item['volume_d'];
                    $item['weight_gross'] = $item['weight_gross_d'];
                    $item['weight'] = $item['weight_d'];
                    $item['width_AS'] = $item['width_AS_d'];
                    $item['length_AS'] = $item['length_AS_d'];
                    $item['height_AS'] = $item['height_AS_d'];
                    $item['volume_AS'] = $item['volume_AS_d'];
                    $item['weight_AS'] = $item['weight_AS_d'];
                    $item['volume_weight_AS'] = $item['volume_weight_AS_d'];
                    $item['oversize'] = $item['oversize_d'];
                    $item['weight_gross_AS'] = $item['weight_gross_AS_d'];
                    $item['goods_code'] = $item['goods_name'];
                }
            })
            ->toArray();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function info()
    {
        return $this->hasOne(ProductInfo::class, 'product_id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supplier_id');
    }


    public function priceInfo()
    {
        return $this->belongsTo(ProductPrice::class, 'product_id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    /**
     * 获取商品分类id
     * @param $id 商品id
     */
    public function categoryNames($id)
    {
        $data = $this->field([
            'p.id', 'p.name_ch as goods_name', 'p.packing_method',
            'p.name_en as goods_name_en', 'p.image_url as goods_pic', 'c.name as category_namea', 'c.id as category_id'
        ])
            ->alias('p')
            ->where('p.id', $id)
            ->leftJoin('category c', 'c.id=p.category_id')
            ->find();
        return $data;
    }


    public function calPrice($rate)
    {
        if ($this->purchase_price_rmb > 0) {
            return $this->purchase_price_rmb;
        }
        return substr(sprintf("%.3f", $this->purchase_price_usd * $rate), 0, -1);
    }
    /**
     * 查询店铺下商品（内部）
     */
    public function getShopProductList($id)
    {
        return $this->field('p.id, p.benchmark_price,p.image_url, p.code, p.name_ch, p.name_en,
        p.packing_method, p.operate_type, p.bar_code, p.bar_code2 as bar_code_upc, pi.unit,
        p.status, p.is_disable, p.type, p.created_at, p.updated_at, s.name as supplier_names, 
        c.name as category_name1, c.parent_name, u.username as creator_name,
        IFNULL(us.username, "-") as update_name, IFNULL(cp.name, "-") as company_name')
            ->alias('p')
            ->where('p.company_id', $id)
            ->where([
                'p.is_disable' => 1, 'p.type' => 0,
                'p.status' => 1, 'is_multi_split' => 0
            ])
            ->catchSearch()
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->leftJoin('product_info pi', 'pi.product_id = p.id')
            ->leftJoin('category c', 'c.id = p.category_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
    }
    /**
     * 通过商品id 获取商品 是否预售，以及预售 商品价格 税费
     */
    public function goodsPriceData($id)
    {
    }

    /**
     * 查询商品供应商和采购价
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function findProductPrice()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 客户角色
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
                // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $where = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $where = ['cp.user_type' => 0];
                    }
                }
            }
        }
        return $this->dataRange()
            ->field('p.code, p.name_ch, p.name_en,
                p.bar_code_upc, s.name as supplier_name, p.purchase_price_rmb, p.purchase_price_usd')
            ->catchSearch()
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where('source_status', 2)
                        ->catchSearch();
                }
            })
            ->alias('p')
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->order('p.id', 'desc')
            ->paginate();
    }
    /**
     * 查询商品供应商和采购价
     */
    public function findAllProductPrice()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 客户角色
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
                // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $where = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $where = ['cp.user_type' => 0];
                    }
                }
            }
        }
        return $this->dataRange()
            ->field('p.id,p.code, p.name_ch, p.name_en,
                p.bar_code_upc, s.name as supplier_name, p.purchase_price_rmb, p.purchase_price_usd')
            ->catchSearch()
            ->alias('p')
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->where('source_status', 2)
                        ->catchSearch();
                }
            })
            ->leftJoin('supplies s', 's.id = p.supplier_id')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->order('p.id', 'desc')
            ->select()->toArray();
    }
    /**
     * 获取借卖订单商品
     */
    public function getBorrowSellGoodsList()
    {
        return $this->field('p.id,p.code, p.name_ch, p.name_en,p.bar_code_upc,p.packing_method, p.image_url,
            c.name as category_name1, c.parent_name, p.benchmark_price, p.type')
            ->alias('p')
            ->where('p.type', 0)
            ->where(['is_disable' => 1, 'status' => 1])
            ->leftJoin('category c', 'c.id = p.category_id')
            ->select();
    }
    /**
     * 商品导出字段
     */
    public function exportField()
    {
        return [
            [
                'title' => '商品SKU',
                'filed' => 'goods_code',
            ],
            [
                'title' => '产品图片',
                'filed' => 'image_url',
            ],
            [
                'title' => '中文名称*',
                'filed' => 'name_ch',
            ],
            [
                'title' => '英文名称',
                'filed' => 'name_en',
            ],
            [
                'title' => '商品分类*',
                'filed' => 'category_name_new',
            ],
            [
                'title' => '运营类型*',
                'filed' => 'operate_type_text',
            ],
            [
                'title' => '所属供应商*',
                'filed' => 'supplie_name',
            ],
            [
                'title' => '所属客户*',
                'filed' => 'company_name',
            ],
            [
                'title' => '采购价（RMB)*',
                'filed' => 'purchase_price_rmb',
            ],
            [
                'title' => '采购价（USD)*',
                'filed' => 'purchase_price_usd',
            ],
            [
                'title' => '产品尺寸',
                'filed' => 'size',
            ],
            [
                'title' => '产品重量',
                'filed' => 'weight',
            ],
            [
                'title' => '产品颜色',
                'filed' => 'color',
            ],
            [
                'title' => '产品材质',
                'filed' => 'material',
            ],
            [
                'title' => '配件',
                'filed' => 'parts',
            ],
            [
                'title' => '长（cm）*',
                'filed' => 'length',
            ],
            [
                'title' => '宽（cm）*',
                'filed' => 'width',
            ],
            [
                'title' => '高（cm）*',
                'filed' => 'height',
            ],
            [
                'title' => '毛重（kg）*',
                'filed' => 'weight_gross',
            ],
            [
                'title' => '净重（kg)',
                'filed' => 'weight',
            ],
            [
                'title' => '体积（m³）',
                'filed' => 'volume',
            ],
            [
                'title' => '长（inch）*',
                'filed' => 'length_AS',
            ],
            [
                'title' => '宽（inch）*',
                'filed' => 'width_AS',
            ],
            [
                'title' => '高（inch）*',
                'filed' => 'height_AS',
            ],
            [
                'title' => '毛重（lbs）*',
                'filed' => 'weight_gross_AS',
            ],
            [
                'title' => '净重（lbs)',
                'filed' => 'weight_AS',
            ],
            [
                'title' => 'Oversize参数',
                'filed' => 'oversize',
            ],
            [
                'title' => '箱率',
                'filed' => 'box_rate',
            ],
            // [
            //   'title' => '长（cm）',
            //   'filed' => 'transport_length',
            // ],
            // [
            //   'title' => '宽（cm）',
            //   'filed' => 'transport_width',
            // ],
            // [
            //   'title' => '高（cm）',
            //   'filed' => 'transport_height',
            // ],
            [
                'title' => '包装方式*',
                'filed' => 'packing_method_text',
            ],
            [
                'title' => '国内HS',
                'filed' => 'ZH_HS',
            ],
            [
                'title' => '国内退税率%',
                'filed' => 'tax_rebate_rate',
            ],
            [
                'title' => '国外HS',
                'filed' => 'EN_HS',
            ],
            [
                'title' => '国外关税税率%',
                'filed' => 'tax_tariff_rate',
            ],
            [
                'title' => '采购员*',
                'filed' => 'purchase_name',
            ]
        ];
    }

    /**
     * 开发商品导出字段
     */
    public function developmentExportField()
    {
        return [
            // [
            //     'title' => '商品编码',
            //     'filed' => 'code',
            // ],
            [
                'title' => '中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '英文名称',
                'filed' => 'name_en',
            ],
            [
                'title' => '商品类型',
                'filed' => 'type_text',
            ],
            [
                'title' => '商品分类',
                'filed' => 'category_name_new',
            ],
            [
                'title' => '所属供应商',
                'filed' => 'supplier_name',
            ],
            [
                'title' => '采购价(RMB)',
                'filed' => 'purchase_price_rmb',
            ],
            [
                'title' => '采购价(USD)',
                'filed' => 'purchase_price_usd',
            ],
            [
                'title' => '尺寸',
                'filed' => 'size',
            ],
            [
                'title' => '重量',
                'filed' => 'weight',
            ],
            [
                'title' => '颜色',
                'filed' => 'color',
            ],
            [
                'title' => '材质',
                'filed' => 'material',
            ],
            [
                'title' => '计量单位',
                'filed' => 'unit',
            ],
            [
                'title' => '箱率',
                'filed' => 'box_rate',
            ],
            [
                'title' => '包装方式',
                'filed' => 'packing_method_text',
            ],
            [
                'title' => '其他备注',
                'filed' => 'other_remark',
            ]
        ];
    }

    /**
     * 商品价格
     */
    public function exportFieldProductPrice()
    {
        return [
            [
                'title' => '商品编码',
                'filed' => 'code',
            ],
            [
                'title' => '中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '英文名称',
                'filed' => 'name_en',
            ],
            [
                'title' => 'UPC条码',
                'filed' => 'bar_code_upc',
            ],
            [
                'title' => '供应商',
                'filed' => 'supplier_name',
            ],
            [
                'title' => '采购价格（RMB）',
                'filed' => 'purchase_price_rmb',
            ],
            [
                'title' => '采购价格（USD）',
                'filed' => 'purchase_price_usd',
            ]
        ];
    }
}
