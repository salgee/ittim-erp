<?php

namespace catchAdmin\system\model\search;

trait DictionarySearch
{
    public function searchDictNameAttr($query, $value, $data)
    {
        return $query->whereLike('dict_name', $value);
    }
}
