<?php
/*
 * @Author:
 * @Date: 2021-02-03 19:23:26
 * @LastEditTime: 2021-03-11 12:32:02
 * @LastEditors:
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\search\ShopSearch.php
 */

namespace catchAdmin\basics\model\search;

trait CurrencySearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('source_name', $value);
    }
    // 状态搜索 status
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('is_status', $value);
    }
}
