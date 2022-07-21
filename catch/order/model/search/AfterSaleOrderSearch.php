<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-25 18:02:26
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-16 15:54:31
 * @Description: 
 */

namespace catchAdmin\order\model\search;

trait AfterSaleOrderSearch
{
    // 售后订单类型
    public function searchTypeAttr($query, $value)
    {
        return $query->where('o.type', $value);
    }
    // 售后原因 sale_reason
    public function searchSaleReasonAttr($query, $value)
    {
        return $query->where('o.sale_reason', $value);
    }
    // 订单编号
    // public function searchOrderNOAttr($query, $value)
    // {
    //     return $query->whereLike('o.platform_order_no', $value);
    // }
    public function searchOrderNOAttr($query, $value)
    {
        $map1 = [
            ['platform_order_no', 'like',  "%{$value}%"]
        ];
        $map2 = [
            ['platform_order_no2', 'like',  "%{$value}%"]
        ];
        return $query->where(function ($query)  use ($map1, $map2) {
            $query->whereOr([$map1, $map2]);
        });
    }
    // 售后订单编号 sale_order_no
    public function searchSaleOrderNOAttr($query, $value)
    {
        return $query->whereLike('o.sale_order_no', $value);
    }
    // 店铺 s.shop_name
    public function searchShopNameAttr($query, $value)
    {
        return $query->whereLike('s.shop_name', $value);
    }
    // 商品编码 og.goods_code
    public function searchGoodsCodeAttr($query, $value)
    {
        return $query->whereLike('p.code', $value);
    }
    // 中文名称 p.name_ch
    public function searchNameChAttr($query, $value)
    {
        return $query->whereLike('p.name_ch', $value);
    }
    // 商品分类 p.category_id
    public function searchCategoryIdAttr($query, $value)
    {
        return $query->where('p.category_id', $value);
    }
    // 订单审核状态
    public function searchStatusAttr($query, $value)
    {
        return $query->where('o.status', $value);
    }
    // 物流单号
    public function searchLogisticsNoAttr($query, $value)
    {
        return $query->whereLike('o.logistics_no', $value);
    }
    // 是否入库 is_warehousing
    public function searchIsWarehousingAttr($query, $value)
    {
        return $query->where('o.is_warehousing', $value);
    }
    // 平台名称 platform_name
    public function searchPlatformNameAttr($query, $value)
    {
        return $query->whereLike('pf.name', $value);
    }
}
