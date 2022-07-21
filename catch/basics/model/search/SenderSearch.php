<?php
/*
 * @Author:
 * @Date: 2021-02-03 19:23:26
 * @LastEditTime: 2021-02-04 15:37:40
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\search\ShopSearch.php
 */

namespace catchAdmin\basics\model\search;

trait SenderSearch
{
    public function searchShopIdAttr($query, $value, $data)
    {
        return $query->where('shop_id', $value);
    }
}
