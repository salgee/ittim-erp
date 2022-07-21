<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-08 15:28:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-17 13:41:50
 * @Description: 
 */

namespace catchAdmin\product\model\search;

trait ProductSalesPriceSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('p.name', $value);
    }
    // 所属店铺
    public function searchShopIdAttr($query, $value, $data)
    {
        return $query->where('p.shop_id', $value);
    }
    // 审核状态 
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('p.status', $value);
    }
}
