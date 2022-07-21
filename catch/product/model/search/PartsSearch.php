<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-08 15:28:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-17 13:57:04
 * @Description: 
 */

namespace catchAdmin\product\model\search;

trait PartsSearch
{
    // 名称
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.name_ch', $value);
    }
    // 编码
    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('c.code', $value);
    }
    // 状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('c.is_status', $value);
    }
    // 分类id
    public function searchCategoryIdAttr($query, $value, $data)
    {
        return $query->where('c.category_id', $value);
    }
    // 采购员  purchase_name
    public function searchPurchaseNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.purchase_name', $value);
    }
    // 采购员 id
    public function searchPurchaseIdAttr($query, $value, $data)
    {
        return $query->where('c.purchase_id', $value);
    }
    // 供应商名称搜索
    public function searchSupplierNameAttr($query, $value)
    {
        return $query->whereLike('su.name', $value);
    }
    // 流向 FlowTo
    public function searchFlowToAttr($query, $value)
    {
        return $query->where('c.flow_to', $value);
    }
}
