<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 14:19:47
 * @LastEditors:
 * @LastEditTime: 2021-03-11 18:18:32
 * @Description: 
 */

namespace catchAdmin\basics\model\search;

trait LforwarderSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);
    }
    // 状态搜索 status
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('is_status', $value);
    }
    // 类型
    public function searchTypeAttr($query, $value, $data)
    {
        return $query->where('type', $value);
    }
}
