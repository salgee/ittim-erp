<?php
/*
 * @Date: 2021-06-24 21:06:20
 * @LastEditTime: 2022-01-06 16:55:58
 */

namespace catchAdmin\supply\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class PurchaseContractProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'purchase_contract_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 采购合同id
        'purchase_contract_id',
        // 商品id
        'purchase_product_id',
        // 唯一条形码
        'upc_only',
        // 条形码文件路径
        'date_path',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    protected $append = [
        'contract_code',
    ];


    public function getContractCodeAttr()
    {
        return PurchaseContracts::where('id', $this->getAttr('purchase_contract_id'))->value('code');
    }

    public function purchaseProducts()
    {
        return $this->belongsTo(PurchaseOrderProducts::class, 'purchase_product_id', 'id');
    }
}
