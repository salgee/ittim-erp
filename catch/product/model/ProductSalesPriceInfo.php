<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-09 10:01:22
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-29 16:22:18
 * @Description: 
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;

class ProductSalesPriceInfo extends Model
{
    // 表名
    public $name = 'product_sales_price_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 商品促销价格ID 关联 product_sales_price
        'product_sales_price_id',
        // 商品ID
        'product_id',
        // 基准价格-rmb
        'price',
        // 促销基准价格-rmb
        'sales_price',
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
    public function selectInfo($id)
    {
        return $this->field('p.*,c.name as category_name, c.parent_name, pi.image_url, pi.benchmark_price, pi.name_ch, pi.name_en, pi.type,
            pi.packing_method, pi.purchase_price_usd, pi.category_id,
            pi.operate_type, pi.bar_code_upc, pi.status, pi.is_disable, pi.code')
            ->alias('p')
            ->where('p.product_sales_price_id', $id)
            ->leftJoin('product pi', 'pi.id = p.product_id')
            ->leftJoin('category c', 'c.id = pi.category_id')
            ->select();
    }

    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('product_sales_price_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }
}