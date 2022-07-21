<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-08 15:28:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-17 13:46:48
 * @Description: 
 */

namespace catchAdmin\product\model\search;

trait ProductPresaleSearch
{
    public function searchNameAttr($query, $value)
    {
        return $query->whereLike('p.name', $value);
    }
    // 所属店铺
    public function searchShopIdAttr($query, $value)
    {
        return $query->where('p.shop_id', $value);
    }
}
