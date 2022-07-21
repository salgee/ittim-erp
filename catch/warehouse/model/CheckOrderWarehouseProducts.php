<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class CheckOrderWarehouseProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'check_order_warehouse_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 盘点单id
        'check_order_id',
        // 商品id
        'goods_id',
        // 虚拟仓id
        'virtual_warehouse_id',
        // 库存差异
        'stock_difference',
        // 备注信息
        'notes',
        // 创建人
        'created_by',
        // 修改人
        'updated_by',
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
}