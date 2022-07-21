<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors:
 * @LastEditTime: 2021-03-22 10:30:56
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait AddressSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('city', $value);
    }
}