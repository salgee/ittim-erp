<?php
/*
 * @Author:
 * @Date: 2021-02-04 17:47:18
 * @LastEditTime: 2021-02-04 17:52:11
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\search\ShopWarehouseSearch.php
 */
namespace catchAdmin\basics\model\search;

trait ShopWarehouseSearch
{
public function searchShopIdAttr($query, $value, $data)
{
return $query->where('shop_id', $value);
}
}