<?php
/*
 * @Description: 
 * @Author: maryna
 * @Date: 2021-07-13 18:38:08
 * @LastEditTime: 2021-07-13 18:39:01
 */

namespace catchAdmin\product\model\search;

use catchAdmin\basics\model\Shop;
use catchAdmin\basics\model\Company;

trait ProductPlatformSkuSearch
{
    public function searchCodeAttr($query, $value)
    {
        $value1 = [
            ['product_code', 'like',  "%{$value}%"],
        ];
        $value2 = [
            ['platform_code', 'like', "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($value1, $value2) {
            $query->whereOr([$value1, $value2]);
        });
    }
    // 商品编码
    public function searchPlatformCodeAttr($query, $value)
    {
        $query->where('pps.platform_code', $value);
    }
    // 商品中文名称
    public function searchNameChAttr($query, $value)
    {
        $query->whereLike('pi.name_ch', $value);
    }

    // 商品英文名称
    public function searchNameEnAttr($query, $value)
    {
        $query->whereLike('pi.name_en', $value);
    }

    // 店铺名称
    public function searchShopNameAttr($query, $value)
    {
        $query->whereLike(app(Shop::class)->getTable() . '.shop_name', $value);
    }

    // 客户名称
    public function searchCompanyNameAttr($query, $value)
    {
        $query->whereLike(app(Company::class)->getTable() . '.name', $value);
    }

    // 店铺ID
    public function searchShopIdAttr($query, $value)
    {
        $query->where('pps.shop_id',  $value);
    }
    // 商品id
    public function searchProductIdAttr($query, $value)
    {
        $query->where('pps.product_id',  $value);
    }
    // 状态
    public function searchIsDisableAttr($query, $value)
    {
        $query->where('pps.is_disable',  $value);
    }
}
