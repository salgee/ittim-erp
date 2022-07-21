<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-08 10:58:10
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-17 21:51:16
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;

class ProductCombinationInfo extends Model
{
    // 表名
    public $name = 'product_combination_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 商品组合ID 关联 product_combination
        'product_combination_id',
        // 商品ID
        'product_id',
        // 基准价格-rmb
        'price',
        // 商品数量
        'number',
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

    public function selectInfo($id) {
        return $this->field('p.*,c.name as category_name, c.name as parent_name, pi.image_url, pi.benchmark_price, pi.name_ch, pi.name_en, pi.type,
            pi.packing_method, pi.purchase_price_usd, pi.category_id, pi.code,
            pi.operate_type, pi.bar_code_upc, pi.status, pi.is_disable')
        ->alias('p')
        ->where('p.product_combination_id', $id)
        ->leftJoin('product pi', 'pi.id = p.product_id')
        ->leftJoin('category c', 'c.id = pi.category_id')
        ->select();
    }

    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('product_combination_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }

    /**
     * 根據組合商品ID查詢關聯的組合商品
     * @param $id
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfoByCombinationID($id){
        return $this->where('product_combination_id', $id)->select();
    }
}
