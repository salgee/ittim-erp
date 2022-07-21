<?php


namespace catchAdmin\warehouse\model\search;


use catchAdmin\warehouse\model\Warehouses;

trait SalesForecastSearch
{
    public function searchYearAttr($query, $value, $data)
    {
        return $query->where('year', $value);
    }
    
}