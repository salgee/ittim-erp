<?php
/*
 * @Date: 2021-06-24 21:06:20
 * @LastEditTime: 2022-01-05 13:29:53
 */

namespace catchAdmin\supply\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class PurchaseOrderProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'purchase_order_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 供应商id
        'supply_id',
        // 采购单id
        'purchase_order_id',
        // 商品id
        'goods_id',
        // 商品编码
        'goods_code',
        // 商品名称
        'goods_name',
        //分类名称
        'category_name',
        // 商品名称(英文)
        'goods_name_en',
        // 箱率
        'container_rate',
        // 商品缩率图
        'goods_pic',
        // 采购员
        'buyer',
        // 采购单价
        'number',
        // 采购单价
        'price',
        // 采购金额
        'amount',
        // 需求时间
        'delivery_date',
        //交货时间
        'arrive_date',
        // 备注
        'notes',
        //类型  1-商品 2-配件
        'type',
        // upc
        'upc',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        'deleted_at'
    );

    protected $append = [
        'purchase_order_code',  'supply'
    ];


    public function getPurchaseOrderCodeAttr()
    {
        return PurchaseOrders::where('id', $this->getAttr('purchase_order_id'))->value('code');
    }

    public function getSupplyAttr()
    {
        return Supply::where('id', $this->getAttr('supply_id'))->value('name') ?? '';
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'goods_id', 'id');
    }
}
