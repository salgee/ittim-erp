<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-27 12:53:28
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait StorageFeeConfigSearch
{
    // 模板名称
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('s.name', $value);
    }
    // 客户名称
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.name', $value);
    }
    // 客户id
    public function searchCompanyIdAttr($query, $value, $data)
    {
        return $query->where('s.company_id', $value);
    }

    //状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('s.is_status', $value);
    }
}
