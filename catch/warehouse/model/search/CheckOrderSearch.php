<?php


namespace catchAdmin\warehouse\model\search;


trait CheckOrderSearch
{
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('status', $value);
    }

    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);

    }

}