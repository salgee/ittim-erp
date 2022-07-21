<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class SalesForecastProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'sales_forecast_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 年份
        'sales_forecast_id',
        // 商品id
        'goods_id',
        // 商品分类
        'category_name',
        // 商品编码
        'goods_code',
        // 商品名称
        'goods_name',
        // 商品名称(英文)
        'goods_name_en',
        // 商品缩率图
        'goods_pic',
        // 商品类型 1-普通商品 2-多箱包装
        'packing_method',
        // 1月
        'jan',
        // 2月
        'feb',
        // 3月
        'mar',
        // 4月
        'apr',
        // 5月
        'may',
        // 6月
        'jun',
        // 7月
        'jul',
        // 8月
        'aug',
        // 9月
        'sep',
        // 10月
        'oct',
        // 11月
        'nov',
        // 12月
        'dec',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );


    public function product() {
        return $this->belongsTo(Product::class, 'goods_id');
    }

    // public function getCategoryNameAttr() {
    //     $cate = $this->product->category ?? null;
    //     if ($cate) {
    //         return $cate->partent() . '-' . $cate->getAttr('name');
    //     }

    //     return '';

    // }
}