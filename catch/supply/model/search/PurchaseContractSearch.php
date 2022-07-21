<?php
/*
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2022-01-13 17:31:45
 */

namespace catchAdmin\supply\model\search;

trait PurchaseContractSearch
{
    public function searchPurchaseOrderCodeAttr($query, $value, $data)
    {
        return $query->whereLike('purchase_order_code', $value);
    }

    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('code', $value);
    }

    public function searchSupplyNameAttr($query, $value, $data)
    {
        return $query->whereLike('supply_name', $value);
    }

    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('audit_status', $value);
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
