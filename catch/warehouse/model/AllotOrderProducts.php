<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Parts;
use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class AllotOrderProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'allot_order_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 调拨单id
        'allot_order_id',
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
        //商品类型
        'packing_method',
        //调拨数量
        'number',
        //类型 1-商品 2-配件
        'type',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    protected $append = [
        'packing_method_text'
    ];

    public function getPackingMethodTextAttr() {
        return $this->getAttr('packing_method') == 1 ? '普通商品' : '多箱包装';
    }

    public function product() {
        if ($this->getAttr('type') == 1) {
            return $this->belongsTo(Product::class, 'goods_id');
        }

        if ($this->getAttr('type')  == 2) {
            return $this->belongsTo(Parts::class, 'goods_id');
        }
    }

    public function getCategoryNameAttr() {
        $cate = $this->product->category ?? null;
        if ($cate) {
            return $cate->partent() . '-' . $cate->getAttr('name');
        }
    }
}