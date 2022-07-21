<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-30 16:35:12
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait CompanySearch
{
    // 编号
    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('code', $value);
    }
    // 名称
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);
    }
    // 业务员
    public function searchSalesmanUsernameAttr($query, $value, $data)
    {
        return $query->whereLike('salesman_username', $value);
    }
    // 状态
    public function searchIsStatusAttr($query, $value, $data)
    {
        return $query->where('is_status', $value);
    }
    // 账号id
    public function searchAccountIdAttr($query, $value, $data)
    {
        return $query->where('account_id', $value);
    }
}
