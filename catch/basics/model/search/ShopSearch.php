<?php
/*
 * @Author:
 * @Date: 2021-02-03 19:23:26
 * @LastEditTime: 2021-07-03 11:22:55
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\search\ShopSearch.php
 */

namespace catchAdmin\basics\model\search;
use catchAdmin\basics\model\Company;

trait ShopSearch
{
    // 类型
    public function searchTypeAttr($query, $value, $data)
    {
        return $query->where('s.type', $value);
    }
    // 状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('s.is_status', $value);
    }
    // 编码
    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('s.code', $value);
    }
    // 名称
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('s.shop_name', $value);
    }
    // 公司id
    public function searchCompanyIdAttr($query, $value, $data)
    {
        return $query->where('s.company_id', $value);
    }
    // 公司名称 Company
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('c.name', $value);
    }
    // 平台 id
    public function searchPlatformIdAttr($query, $value, $data)
    {
        return $query->where('s.platform_id', $value);
    }
}
