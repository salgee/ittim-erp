<?php
/*
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2022-01-13 17:31:21
 */

namespace catchAdmin\supply\model\search;

use catchAdmin\supply\model\PurchaseContracts;

trait PurchaseOrderSearch
{

    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('w.code', $value);
    }


    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('w.audit_status', $value);
    }

    //合同编码  contract_code
    public function searchContractCodeAttr($query, $value)
    {
        $contractId = PurchaseContracts::whereLike('code', $value)->column('purchase_order_id') ?? [];
        return $query->whereIn('w.id', $contractId);
    }

    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '>=', strtotime($value));
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '<=', strtotime($value));
    }
}
