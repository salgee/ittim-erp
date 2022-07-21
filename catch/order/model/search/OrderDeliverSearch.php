<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-23 09:59:14
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-20 10:39:11
 * @Description:
 */

namespace catchAdmin\order\model\search;

use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\basics\model\Shop;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\order\model\OrderRecords;

trait OrderDeliverSearch
{
    // 物流方式 logistics_type 0-未设置 1-自有物流 2-它有物流
    public function searchLogisticsTypeAttr($query, $value)
    {
        return $query->where('o.logistics_type', (int)$value);
    }
    // 订单是否完成物流异常 OrderStatus
    public function searchOrderStatusAttr($query, $value)
    {
        return $query->whereIn('o.delivery_state', $value);
    }
    // 订单来源类型 1-正常订单 2-补货订单
    public function searchTypeAttr($query, $value)
    {
        return $query->where('o.order_type_source', (int)$value);
    }
    // 订单状态 1-成功发货订单，2-异常发货订单 
    public function searchLogisticsStatusAttr($query, $value)
    {
        return $query->where('o.logistics_status', (int)$value);
    }
    // 物流异常发货单
    public function searchAbnormalLogisticsAttr($query, $value)
    {
        return $query->where('o.deliver_day', '<',  strtotime('-3 days'));
    }
    // 订单发货状态
    public function searchDeliveryStateAttr($query, $value)
    {
        return $query->where('o.delivery_state', (int)$value);
    }
    // erp平台订单编号
    public function searchOrderNoAttr($query, $value)
    {
        return $query->whereLike('o.order_no', $value);
    }
    // 第三方平台订单编号
    public function searchPlatformNoAttr($query, $value)
    {
        return $query->whereLike('o.platform_no', $value);
    }
    // 发货单编号
    public function searchInvoiceNoAttr($query, $value)
    {
        return $query->whereLike('o.invoice_no', $value);
    }
    // 是否打印面单 
    public function searchDeliveryTypeAttr($query, $value)
    {
        return $query->where('o.delivery_process_status', (int)$value);
    }
    // 实体仓库名称 en_name
    public function searchEnNameAttr($query, $value)
    {
        return $query->whereLike('w.name', $value);
    }
    // 虚拟仓库  vi_name
    public function searchViNameAttr($query, $value)
    {
        return $query->whereLike('wvi.name', $value);
    }
    // 快递公司
    public function searchShippingNameAttr($query, $value)
    {
        return $query->whereLike('o.shipping_name', $value);
    }
    // 物流单号
    public function searchShippingCodeAttr($query, $value)
    {
        return $query->whereLike('o.shipping_code', $value);
    }
    // 创建开始时间
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime('o.created_at', '>=', strtotime($value));
    }
    // 创建结束时间
    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime('o.created_at', '<=', strtotime($value));
    }
    // 店铺id几个 shop_basics_id
    // 店铺id批量搜索
    public function searchShopIdsAttr($query, $value)
    {
        return $query->whereIn('o.shop_basics_id', $value);
    }
    // 店铺名称 
    public function searchShopNameAttr($query, $value)
    {
        return $query->whereLike('sb.shop_name', $value);
    }
    // 商品编码 goods_code
    public function searchGoodsCodeAttr($query, $value)
    {
        return $query->whereLike('o.goods_code', $value);
    }
    // 商品中文名称 goods_name
    public function searchGoodsNameAttr($query, $value)
    {
        return $query->whereLike('p.name_ch', $value);
    }
    // 商品分类 category_id
    public function searchCategoryIdAttr($query, $value)
    {
        return $query->where('p.category_id', (int)$value);
    }
    // 订单类型 1-整单发货 2-拆分发货 order_delivery_type
    public function searchOrderDeliveryTypeAttr($query, $value)
    {
        return $query->where('o.order_delivery_type', (int)$value);
    }
    // 州 stateorprovince
    public function searchStateorprovinceAttr($query, $value)
    {
        return $query->whereLike('obr.address_stateorprovince', $value);
    }
    // 城市 address_cityname
    public function searchCityNameAttr($query, $value)
    {
        return $query->whereLike('obr.address_cityname', $value);
    }
    // 买家姓名 user_name
    public function searchUserNameAttr($query, $value)
    {
        return $query->whereLike('obr.address_name', $value);
    }
    // 邮箱 Email
    public function searchEmailAttr($query, $value)
    {
        return $query->whereLike('obr.address_email', $value);
    }
    // 平台 platform_name
    public function searchPlatformNameAttr($query, $value)
    {
        return $query->whereLike('pf.name', $value);
    }
    // 发货类型 DeliverType
    public function searchDeliverTypeAttr($query, $value)
    {
        return $query->where('o.deliver_type', (int)$value);
    }
    // 打印大货单时间
    // 创建开始时间
    public function searchStartPrintAttr($query, $value)
    {
        return $query->whereTime('o.print_time', '>=', ($value));
    }
    // 创建结束时间
    public function searchEndPrintAttr($query, $value)
    {
        return $query->whereTime('o.print_time', '<=', ($value));
    }
    // 是否同步
    public function searchSyncLogisticsAttr($query, $value)
    {
        return $query->where('o.sync_logistics', (int)$value);
    }
}
