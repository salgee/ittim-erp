<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors:
 * @LastEditTime: 2021-03-15 18:46:42
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait OrderFeeSettingSearch
{
    // 模板名称
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('o.name', $value);
    }
    // 客户名称
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.name', $value);
    }
    //状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('o.is_status', $value);
    }
}
