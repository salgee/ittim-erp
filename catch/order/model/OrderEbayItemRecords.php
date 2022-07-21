<?php

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;

class OrderEbayItemRecords extends Model
{
    // 表名
    public $name = 'order_ebay_item_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // Ebay订单记录表ID
        'order_ebay_records_id',
        // 商品ID
        'itemid',
        // 站点
        'site',
        // 标题
        'title',
        // sku标识
        'sku',
        // 购买数量
        'quantity_purchased',
        // 交易编号
        'transaction_id',
        // 销售价格(单位)
        'transaction_price_currencyid',
        // 一个单位的销售价格。此价格不包括任何其他费用，例如运费或营业税，其价格在付款前后将保持不变
        'transaction_price_value',
        // 属性SKU
        'variation_sku',
        // 属性标题
        'variation_title',
        // 属性规格(JSON)
        'variation_specifics',
        // 购买订单的电子邮件
        'buyer_email',
        // 购买订单的买方的名字
        'buyer_user_firstname',
        // 购买订单的买方的姓氏
        'buyer_user_lastname',
        // 发货时间
        'shippedtime_date',
        // 最终费用(单位)
        'final_value_fee_currencyid',
        // 最终费用
        'final_value_fee_value',
        // 总税额(单位)
        'total_tax_amount_currencyid',
        // 所有税种的订单行项目的总税额，其中可能包括营业税（适用于卖方税或“ eBay收汇”），“商品和服务”税（适用于澳大利亚或新西兰卖方） ，或其他费用，例如电子废物回收费
        'total_tax_amount_value',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );
}
