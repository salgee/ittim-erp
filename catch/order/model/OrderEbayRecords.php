<?php

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;

class OrderEbayRecords extends Model
{
    // 表名
    public $name = 'order_ebay_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联店铺表ID
        'shop_basics_id',
        // EBay订单ID
        'orderid',
        // 订单状态
        'order_status',
        // 订单调整金额（单位）
        'adjustment_amount_currencyid',
        // 订单调整金额（正数表示支付额外费用，负值表示折扣）
        'adjustment_amount_value',
        // 订单支付的总金额（单位）
        'amount_paid_currencyid',
        // 订单支付的总金额
        'amount_paid_value',
        // 订单节省的金额(单位)
        'amount_saved_currencyid',
        // 订单节省的金额
        'amount_saved_value',
        // 支付时间
        'checkout_date',
        // 创建时间
        'createdtime_date',
        // 用户姓名
        'address_name',
        // 用户地址在eBay数据库中用户地址的唯一ID
        'address_addressid',
        // eBay与PayPal
        'address_address_owner',
        // 城市
        'address_cityname',
        // 国家的两位数字代码
        'address_country',
        // 国家的全名
        'address_country_name',
        // 用户街道地址的第一行
        'address_street1',
        // 用户街道地址的第二行
        'address_street2',
        // 用户地址中的州或省
        'address_stateorprovince',
        // 用户地址的电话号码
        'address_phone',
        // 用户地址的邮编
        'address_postalcode',
        // 订单中所有订单项的累计商品成本
        'subtotal_currencyid',
        // 订单中所有订单项的累计商品成本
        'subtotal_value',
        // 该总金额显示订单的总成本:包括项目总成本,运费
        'total_currencyid',
        // 该总金额显示订单的总成本:包括项目总成本,运费
        'total_value',
        // 购买订单的买方的邮箱
        'buyer_email',
        // 购买订单的买方的名字
        'buyer_user_firstname',
        // 购买订单的买方的姓氏
        'buyer_user_lastname',
        // 购买用户ID
        'buyer_userid',
        // 支付时间
        'paidtime_date',
        // eBay REST API模型中eBay订单的唯一标识符
        'extended_orderid',
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
