<?php


namespace catchAdmin\warehouse\model\search;


trait WarehouseSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);
    }

    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('code', $value);
    }

    public function searchTypeAttr($query, $value, $data)
    {
        return $query->where('type', $value);
    }

    public function searchParentIdAttr($query, $value, $data)
    {
        return $query->where('parent_id', $value);
    }

    public function searchIsActiveAttr($query, $value, $data)
    {
        return $query->where('is_active', $value);
    }


    public function searchIsThirdPartAttr($query, $value, $data)
    {
        return $query->where('is_third_part', $value);
    }

}