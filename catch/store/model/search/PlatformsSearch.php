<?php
namespace catchAdmin\store\model\search;

trait PlatformsSearch
{
    public function searchNameAttr($query, $value, $data)
    {
        return $query->whereLike('name', $value);
    }

}