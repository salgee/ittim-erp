<?php
/*
 * @Author:
 * @Date: 2021-02-03 19:23:26
 * @LastEditTime: 2021-04-02 16:01:35
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\search\ShopSearch.php
 */

namespace catchAdmin\basics\model\search;

trait LfCurrencySearch
{
    public function searchCompanyIdAttr($query, $value, $data)
    {
        return $query->where('lforwarder_company_id', $value);
    }
}
