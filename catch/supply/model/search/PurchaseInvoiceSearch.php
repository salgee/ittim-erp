<?php
/*
 * @Date: 2021-05-27 07:08:45
 * @LastEditTime: 2022-01-13 17:31:41
 */

namespace catchAdmin\supply\model\search;

trait PurchaseInvoiceSearch
{
    //发票号、付款单位、采购单号搜索类型

    public function searchInvoiceNoAttr($query, $value, $data)
    {
        return $query->whereLike('invoice_no', $value);
    }

    public function searchPayerAttr($query, $value, $data)
    {
        return $query->whereLike('payer', $value);
    }

    public function searchPurchaseCodeAttr($query, $value, $data)
    {
        return $query->whereLike('purchase_code', $value);
    }

    public function searchSupplyAttr($query, $value, $data)
    {
        return $query->whereLike('supply', $value);
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
