<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-24 18:55:27
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-24 16:31:54
 * @Description:
 */

declare(strict_types=1);

namespace catcher;

class Code
{
    public const SUCCESS = 10000; // 成功
    public const LOST_LOGIN = 10001; //  登录失效
    public const VALIDATE_FAILED = 10002; // 验证错误
    public const PERMISSION_FORBIDDEN = 10003; // 权限禁止
    public const LOGIN_FAILED = 10004; // 登录失败
    public const FAILED = 10005; // 操作失败
    public const FAILEDO = 100051; // ups获取物流单号报错
    public const LOGIN_EXPIRED = 10006; // 登录失效
    public const LOGIN_BLACKLIST = 10007; // 黑名单
    public const USER_FORBIDDEN = 10008; // 账户被禁
    public const LOGIN_FAILED_COUNT = 10009; // 登录失败，密码错误次数过多

    public const WECHAT_RESPONSE_ERROR = 40000;

    public const AMAZON = 1; // 亚马逊
    public const EBAY = 2; // eBay
    public const WAYFAIR = 3; // wayfair
    public const OVERSTOCK = 4; // Overstock
    public const WALMART = 5; // Walmart
    public const OPENCART = 6; // Opencart
    public const SHOPIFY = 7; // Shopify
    public const HOUZZ = 8; // Houzz

    public const ORDER_UNSHIPPED = 1; // 待发货
    public const ORDER_SHIPPED = 2; // 已发货（发货中）
    public const ORDER_TRAFFIC = 3; // 运输中
    public const ORDER_DELIVERY = 4; // 配送中
    public const ORDER_DELIVERED = 5; // 已收货（已交付）
    public const ORDER_CANCELED = 6; // 已取消(作废订单)
    public const ORDER_REFUND = 10; // 已退款

    public const ORDER_TYPE_SALES = 0; // 销售订单
    public const ORDER_TYPE_ABNORMAL = 1; // 异常订单
    public const ORDER_TYPE_LOAN = 2; // 借卖订单
    public const ORDER_TYPE_CUSTOMER = 3; // 客户订单
    public const ORDER_TYPE_PRESALES = 4; // 预售订单
    public const ORDER_TYPE_FBA = 5; // FBA订单

    public const ORDER_SOURCE_API = 0; // 平台接口
    public const ORDER_SOURCE_INSERT = 1; // 录入
    public const ORDER_SOURCE_IMPORT = 2; // 导入

    public const ORDER_SALES_REFUND = 1; // 订单退款
    public const ORDER_SALES_REFUNDALL = 2; // 退货退款
    public const ORDER_SALES_CPFR = 3; // 补货
    public const ORDER_SALES_RECALL = 4; // 召回
    public const ORDER_SALES_MODIFY_ADDRESS = 5; // 修改地址

    public const AFTER_STATUS_WAIT = 0; // 售后订单待审核
    public const AFTER_STATUS_PASS = 1; // 售后订单通过
    public const AFTER_STATUS_REFUSE = 2; // 售后订单驳回

    public const ABNORMAL_PRODUCT = 1; // 订单商品异常
    public const ABNORMAL_ADDRESS = 2; // 订单地址异常

    public const CACHE_PRESALE = 'preSale:'; //预售商品缓存前缀.shop_id.product_id

    public const ORDER_EXAMNE_WAIT = 0; // 物流付款待审核
    public const ORDER_EXAMNE_PASS = 1; // 物流付款审核通过
    public const ORDER_EXAMNE_REFUSE = 2; // 物流付款审核拒绝

    public const CACHE_PRODUCT = 'product:'; // 商品编码前缀
    public const CACHE_PART = 'part:'; // 配件编码前缀

    public const TYPE_PRODUCT = 0; //商品
    public const TYPE_COMBINATION = 1; //组合商品
    public const TYPE_PRODUCT_ALL = 9; //商品和组合商品
}
