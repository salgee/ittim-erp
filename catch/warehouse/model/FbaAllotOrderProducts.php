<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class FbaAllotOrderProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'fba_allot_order_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // fab调拨单id
        'fba_allot_order_id',
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
        // 调拨数量
        'number',
        // 托盘数量
        'pallet_number',
        // 贴标费
        'label_price',
        // 打托费
        'pallet_price',
        // 出库费
        'outbound_price',
        //类型 1-商品 2-配件
        'type',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    public $append = ['packing_method'];

    public function getPackingMethodAttr() {
        $packingMethod = '';
        if($this->getAttr('type') == 1) {
            return  Product::where('id', $this->goods_id)->value('packing_method');
        }

        return 1;
    }
}