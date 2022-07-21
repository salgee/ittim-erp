<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-23 16:51:06
 * @LastEditTime: 2021-04-23 17:07:38
 * @Description:
 */

namespace catchAdmin\finance\model\search;

trait PurchasePaymentSearch
{
    // 运单号
    public function searchPaymentNoAttr($query, $value, $data)
    {
        return $query->whereLike('payment_no', $value);
    }
    // 发货单号
    public function searchSupplyNameAttr($query, $value, $data)
    {
        return $query->whereLike('supply_name', $value);
    }
    // 物流公司
    public function searchTransCodeAttr($query, $value, $data)
    {
        return $query->whereLike('trans_code', $value);
    }
    // 导入时间 开始时间
    public function searchStartAtAttr($query, $value, $data)
    {
        return $query->where('estimated_pay_time', '>=', $value);
    }
    // 结束时间
    public function searchEndAtAttr($query, $value, $data)
    {
        return $query->where('estimated_pay_time', '<=', $value);
    }

    public function  searchPayStatusAttr($query, $value, $data) {
        return $query->whereLike('pay_status', $value);
    }
}
