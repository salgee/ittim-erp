<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-22 11:09:28
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-18 12:13:52
 * @Description: 
 */

namespace catchAdmin\product\model\search;

use catchAdmin\supply\model\Supply;
use catchAdmin\basics\model\Company;

trait ProductSearch
{
    // 编码
    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('p.code', $value);
    }
    // 中文名称
    public function searchNameChAttr($query, $value, $data)
    {
        return $query->whereLike('p.name_ch', $value);
    }
    // 英文名称
    public function searchNameEnAttr($query, $value, $data)
    {
        return $query->whereLike('p.name_en', $value);
    }
    // 所属供应商 s.name
    // public function searchSupplierNameAttr($query, $value, $data)
    // {
    //     // s.name
    //     return $query->whereLike('p.supplier_name', $value);
    // }
    public function searchSupplierNameAttr($query, $value)
    {
        $map1 = [
            ['p.supplier_name', 'like',  "%{$value}%"]
        ];
        $map2 = [
            ['s.name', 'like',  "%{$value}%"]
        ];
        return $query->where(function ($query)  use ($map1, $map2) {
            $query->whereOr([$map1, $map2]);
        });
    }
    // 所属客户
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('cp.name', $value);
    }
    // 分类
    public function searchCategoryIdAttr($query, $value, $data)
    {
        return $query->where('p.category_id', $value);
    }
    // 采购员名称 
    public function searchPurchaseNameAttr($query, $value, $data)
    {
        return $query->whereLike('p.purchase_name', $value);
    }
    // 是否禁用 is_disable
    public function searchIsDisableAttr($query, $value, $data)
    {
        return $query->where('p.is_disable', $value);
    }
    // 状态 status
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('p.status', $value);
    }
    // 包装方式
    public function searchPackingMethodAttr($query, $value, $data)
    {
        return $query->where('p.packing_method', $value);
    }
    // 运营类型
    public function searchOperateTypeAttr($query, $value, $data)
    {
        return $query->where('p.operate_type', $value);
    }
    // 开始时间
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime('p.created_at', '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime('p.created_at', '<=', strtotime($value));
    }
    // 查询 /product
    public function searchTypeAttr($query, $value)
    {
        return $query->where('p.type', $value);
    }
    // Oversize参数是否>130inch
    // public function searchOversizeAttr($query, $value)
    // {
    //     if ((int)$value == 1) {
    //         return $query->where('pi.oversize', '>=',  130);
    //     } else if ((int)$value == 2) {
    //         return $query->where('pi.oversize', '<=', '130');
    //     }
    // }
    // 是否多箱拆分商品 is_multi_split  IsMultiSplit
    public function searchIsMultiSplitAttr($query, $value)
    {
        return $query->where('p.is_multi_split', $value);
    }
}
