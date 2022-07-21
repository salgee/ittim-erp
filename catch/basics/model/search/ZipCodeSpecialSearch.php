<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 12:28:36
 * @LastEditors:
 * @LastEditTime: 2021-02-06 14:39:33
 * @Description: 
 */

namespace catchAdmin\basics\model\search;

trait ZipCodeSpecialSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('zipCode', $value);
    }

    public function searchTypeAttr($query, $value, $data)
    {
        return $query->where('type', $value);
    }
}
