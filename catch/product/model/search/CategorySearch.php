<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-08 15:28:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-17 18:31:32
 * @Description: 
 */

namespace catchAdmin\product\model\search;

trait CategorySearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.name', $value);
    }

    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('c.is_status', $value);
    }
}
