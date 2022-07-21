<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 18:36:03
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-02 13:00:00
 * @Description:
 */

namespace catchAdmin\order\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;

class OrderItemRecords extends Model
{
    // 表名
    public $name = 'order_item_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // 是否发货（只针补货） 0- 否 1-是
        'is_delivery',
        // 来自订单类型 0-正常订单 1-补货订单（order_record_id  关联 售后记录ID）
        'type',
        // 关联售后订单id
        'after_order_id',
        // 商品类型 0-商品 1-配件
        'goods_type',
        // 三方商品ID
        'item_id',
        // 三方商品编码
        'product_code',
        // 订单表ID
        'order_record_id',
        // 商品表ID erp平台商品
        'goods_id',
        // 商品Code erp平台商品code
        'goods_code',
        // 商品名称
        'name',
        // 商品属性
        'variation',
        // 商品SKU
        'sku',
        // 商品交易价格(单位)
        'transaction_price_currencyid',
        // 商品交易价格
        'transaction_price_value',
        // 购买数量
        'quantity_purchased',
        // 税额(单位)
        'tax_amount_currencyid',
        // 税额
        'tax_amount_value',
        // 发货仓库 （虚拟仓库）
        'warehouse_id',
        // 商品基准价格(或者促销价格)
        'goods_price',
        // 商品税额
        'goods_tax_amount',
        // 购买订单的电子邮件
        'buyer_email',
        // 购买订单的买方的名字
        'buyer_user_firstname',
        // 购买订单的买方的姓氏
        'buyer_user_lastname',
        // 海运费
        'freight_fee',
        // 扩展字段1(Walmart-存储lineNumber字段)
        'extend_1',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );


    public function product()
    {
        return $this->belongsTo(Product::class, 'goods_id', 'id');
    }
}
