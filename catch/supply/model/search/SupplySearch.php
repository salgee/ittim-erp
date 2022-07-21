<?php
/*
 * @Date: 2021-11-04 18:02:03
 * @LastEditTime: 2022-01-13 17:31:52
 */

namespace catchAdmin\supply\model\search;

trait SupplySearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);
    }

    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('code', $value);
    }

    public function searchBuyerAttr($query, $value, $data)
    {
        return $query->whereLike('buyer', $value);
    }

    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('audit_status', $value);
    }


    public function searchCooperationStatusAttr($query, $value, $data)
    {
        return $query->where('cooperation_status', $value);
    }
    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '>=', strtotime($value));
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '<=', strtotime($value));
    }
}
