<?php

namespace catchAdmin\supply\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\permissions\model\DataRangScopeTrait;

class TranshipmentOrderProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait, DataRangScopeTrait;
    // 表名
    public $name = 'transhipment_order_products';
    // 数据库字段映射
    public $field = array(
        'id',
        //转运单id
        'trans_order_id',
        // 采购单id
        'purchase_order_id',
        // 采购合同id
        'purchase_contract_id',
        // 商品id
        'purchase_product_id',
        // 转运数量
        'trans_number',
        //到仓数量
        'arrive_number',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    public function product()
    {
        return $this->belongsTo(PurchaseOrderProducts::class, 'purchase_product_id', 'id');
    }
}
