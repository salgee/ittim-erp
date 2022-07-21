<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-09 16:34:23
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-27 20:20:34
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;
use catchAdmin\product\model\search\ProductGroupSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\basics\model\Shop;

class ProductGroup extends Model
{
    use ProductGroupSearch;
    use DataRangScopeTrait;
    // 表名 多箱包装商品
    public $name = 'product_group';
    // 数据库字段映射
    public $field = array(
        'id',
        // 产品id 关联产品product
        'product_id',
        // 名称
        'name',
        // 数量
        'number',
        // 尺寸公制(长)cm
        'length',
        // 尺寸公制(宽)cm
        'width',
        // 尺寸公制(高)cm
        'height',
        // 体积
        'volume',
        // 毛重 kg
        'weight_gross',
        // 净重 kg
        'weight',
        //（美）尺寸公制(长)cm
        'length_AS',
        // （美）尺寸公制(宽)cm
        'width_AS',
        // （美）尺寸公制(高)cm
        'height_AS',
        // （美）体积
        'volume_AS',
        // （美）净重 kg
        'weight_AS',
        // （美）毛重 kg'
        'weight_gross_AS',
        // （美）体积重 体积(美制)/系数
        'volume_weight_AS',
        // oversize(特大) (美制)长+（宽+高）*2
        'oversize',
        // 保值
        'hedge_price',
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
     * 获取所有多箱包装信息
     *
     */
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('product_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }

    public function getInfoGroup($id, $field)
    {
        return $this->field($field)
            ->where('product_id', $id)
            ->select();
    }

    /**
     * 获取分组商品信息
     */
    public function getMultiGroupList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
            }
            // 如果是采购员
            if ($prowerData['is_buyer_staff']) {
                $where = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
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
        return $this
            // ->dataRange()
            ->catchSearch()
            ->field('pg.name, pg.product_id, pg.creator_id, p.code, p.name_ch')
            ->alias('pg')
            ->where('p.status', 1) // 审核通过
            ->where('p.packing_method', 2) // 多箱商品
            ->where($where)
            ->leftJoin('product p', 'p.id = pg.product_id')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->paginate();
    }
}
