<?php
/*
 * @Version: 1.0
 * @Date: 2021-05-31 09:26:24
 * @LastEditTime: 2021-11-19 12:03:46
 * @Description: 
 */

namespace catchAdmin\report\model\search;

use catchAdmin\basics\model\Shop;

trait ReportOrderSearch
{
    // 开始时间
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime($this->aliasField('created_at'), '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime($this->aliasField('created_at'), '<=', strtotime($value));
    }

    // 店铺名称 shop_name or 订单编号
    public function searchKeyAttr($query, $value)
    {
        $map1 = [
            [App(Shop::class)->getTable() . '.shop_name', 'like',  "%{$value}%"],
        ];
        $map2 = [
            ['order_no', 'like',  "%{$value}%"]
        ];
        return $query->where(function ($query)  use ($map1, $map2) {
            $query->whereOr([$map1, $map2]);
        });
    }

    // 订单编码  platform_no  PlatformNo
    public function searchPlatformNoAttr($query, $value)
    {
        return $query->whereLike('platform_no', $value);
    }

    // 商品SKU
    public function searchProductSkuAttr($query, $value)
    {
        return $query->whereLike('product_sku', $value);
    }
    // 渠道SKU
    public function searchPlatformSkuAttr($query, $value)
    {
        return $query->whereLike('platform_sku', $value);
    }

}
