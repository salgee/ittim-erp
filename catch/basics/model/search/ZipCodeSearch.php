<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors:
 * @LastEditTime: 2021-02-06 12:05:37
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait ZipCodeSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        // return $query->whereLike('origin', $value);  // dest_zip
        $value1 = [
            ['origin', 'like',  "%{$value}%"],
        ];
        $value2 = [
            ['dest_zip', 'like', "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($value1, $value2) {
            $query->whereOr([$value1, $value2]);
        });
    }
}
