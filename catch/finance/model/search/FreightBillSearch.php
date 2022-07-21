<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-23 16:51:06
 * @LastEditTime: 2021-10-28 17:01:43
 * @Description:
 */

namespace catchAdmin\finance\model\search;

trait FreightBillSearch
{



    // 提单号
    public function searchBlNoAttr($query, $value, $data)
    {
        return $query->whereLike('bl_no', $value);
    }
    // 付款单号
    public function searchPaymentNoAttr($query, $value, $data)
    {
        return $query->where('payment_no', $value);
    }

    // 运单号
    public function searchCabinetNoAttr($query, $value, $data)
    {
        return $query->whereLike('cabinet_no', $value);
    }
    // 发货单号
    public function searchLforwarderCompanyAttr($query, $value, $data)
    {
        return $query->whereLike('lforwarder_company', $value);
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
}
