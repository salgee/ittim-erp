<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-23 16:51:25
 * @LastEditTime: 2021-04-29 16:45:12
 * @Description: 
 */
namespace catchAdmin\finance\model\search;

trait LogisticsPayawayOrderSearch
{
    // 付款单号
    public function searchPayawayOrderNoAttr($query, $value, $data)
    {
        return $query->whereLike('payaway_order_no', $value);
    }
    // 物流公司
    public function searchCompanyAttr($query, $value, $data)
    {
        return $query->whereLike('logistics_company', $value);
    }
    // 付款状态
    public function searchPayawayStatusAttr($query, $value, $data)
    {
        return $query->where('payaway_status', $value);
    }
    // 审核状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('status', $value);
    }
    // 开始时间
    public function searchStartAtAttr($query, $value, $data)
    {
        return $query->whereTime($this->aliasField('created_at'), '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value, $data)
    {
        return $query->whereTime($this->aliasField('created_at'), '<=', strtotime($value));
    }
}
