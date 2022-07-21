<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:56:06
 * @LastEditors:
 * @LastEditTime: 2021-03-20 14:26:12
 * @Description: 
 */


namespace catchAdmin\basics\model\search;

trait CitySearch
{
    public function searchStatesIdAttr($query, $value)
    {
        return $query->where('states_id', $value);
    }
}
