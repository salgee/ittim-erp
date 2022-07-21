<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-23 16:51:06
 * @LastEditTime: 2021-04-23 17:07:38
 * @Description:
 */

namespace catchAdmin\finance\model\search;

trait FreightBillOrderSearch
{

    public function searchPaymentNoAttr($query, $value, $data)
    {
        return $query->whereLike('payment_no', $value);
    }

    public function searchLforwarderCompanyAttr($query, $value, $data)
    {
        return $query->whereLike('lforwarder_company', $value);
    }

    public function searchPayStatusAttr($query, $value, $data)
    {
        return $query->where('pay_status', $value);
    }

    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('audit_status', $value);
    }

    // 导入时间 开始时间
    public function searchStartAtAttr($query, $value, $data)
    {
        $value = strtotime($value);
        return $query->where('created_at', '>=', $value);
    }
    // 结束时间
    public function searchEndAtAttr($query, $value, $data)
    {
        $value = strtotime($value);
        return $query->where('created_at', '<=', $value);
    }

}
