<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-08 10:53:08
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-14 15:19:27
 * @Description:
 */

namespace catchAdmin\product\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catcher\base\CatchModel as Model;
use catchAdmin\product\model\search\ProductCombinationSearch;

class ProductCombination extends Model
{
    use DataRangScopeTrait;
    use ProductCombinationSearch;
    // 表名
    public $name = 'product_combination';
    // 数据库字段映射
    public $field = array(
        'id',
        // 编码
        'code',
        // 状态，1：正常，2：禁用
        'is_disable',
        // 中文名称
        'name_ch',
        // 英文名称
        'name_en',
        // 套餐单价-rmb
        'price_usd',
        // 店铺id
        'shop_id',
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

    /**
     * 列表
     * @return \think\Paginator
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['p.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->field('p.*, u.username as creator_name, IFNULL(us.username, "-") as update_name, s.shop_name')
            ->alias('p')
            ->order('p.id', 'desc')
            ->leftJoin('shop_basics s', 's.id = p.shop_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
    }


    /**
     * 所有商品列表 getSystemGoodsList
     */
    public function getSystemGoodsList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['p.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->field('p.id, p.code, p.is_disable, p.name_ch, p.name_en, p.shop_id')
            ->alias('p')
            ->order('p.id', 'desc')
            ->where(['is_disable' => 1])
            ->select();
    }

    /**
     * 导出商品
     */
    public function getExportList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['p.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->field('p.id, p.code, p.created_at, p.is_disable, p.name_ch, p.name_en, p.shop_id, s.shop_name, 
            pi.number, pi.price, pd.code as goods_code, pd.name_ch as goods_name_ch,
             pd.packing_method, pd.benchmark_price, pd.category_id,
            cg.name as category_name, cg.parent_name')
            ->alias('p')
            ->leftJoin('shop_basics s', 's.id = p.shop_id')
            ->leftJoin('product_combination_info pi', 'pi.product_combination_id = p.id')
            ->leftJoin('product pd', 'pd.id = pi.product_id')
            ->leftJoin('category cg', 'cg.id = pd.category_id')
            ->order('p.id', 'desc')
            ->select()->each(function ($item) {
                $item['category_names'] = $item['parent_name'] . '-' . $item['category_name'];
                $item['packing_method_text'] = (int)$item['packing_method'] == 2 ? '多箱商品' : '普通商品';
            })->toArray();
    }

    /**
     * 导出字段
     * exportField
     */
    public function exportField()
    {
        return [
            [
                'title' => '商品组合编码',
                'filed' => 'code',
            ],
            [
                'title' => '商品组合中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '商品组合所属店铺',
                'filed' => 'shop_name',
            ],
            [
                'title' => '商品SKU',
                'filed' => 'goods_code',
            ],
            [
                'title' => '商品中文名称',
                'filed' => 'goods_name_ch',
            ],
            [
                'title' => '商品包装方式',
                'filed' => 'packing_method_text',
            ],
            [
                'title' => '商品分类',
                'filed' => 'category_names',
            ],
            [
                'title' => '基准价格',
                'filed' => 'price',
            ],
            [
                'title' => '商品数量',
                'filed' => 'number',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ]
        ];
    }
}
