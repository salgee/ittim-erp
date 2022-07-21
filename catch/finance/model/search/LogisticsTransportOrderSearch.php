<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-23 16:51:06
 * @LastEditTime: 2021-04-29 17:32:19
 * @Description: 
 */

namespace catchAdmin\finance\model\search;

trait LogisticsTransportOrderSearch
{
    // 运单号
    public function searchTransportOrderNoAttr($query, $value, $data)
    {
        return $query->whereLike('transport_order_no', $value);
    }
    // 发货单号
    public function searchInvoiceOrderNoAttr($query, $value, $data)
    {
        return $query->whereLike('invoice_order_no', $value);
    }
    // 物流公司
    public function searchLogisticsCompanyAttr($query, $value, $data)
    {
        return $query->whereLike('logistics_company', $value);
    }
    // 导入时间 开始时间
    public function searchStartAtAttr($query, $value, $data)
    {
        return $query->whereTime($this->aliasField('created_at'), '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value, $data)
    {
        return $query->whereTime($this->aliasField('created_at'), '<=', strtotime($value));
    }
    
    // 客户名称
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('company_name', $value);
    }
}
