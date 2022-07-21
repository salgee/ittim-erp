<?php
/*
 * @Version: 1.0
 * @Date: 2021-02-23 09:59:14
 * @LastEditTime: 2021-04-30 16:55:50
 * @Description: 
 */

namespace catchAdmin\system\model\search;
use catchAdmin\system\model\Notice;

trait NoticeSearch
{
    public function searchTitleAttr($query, $value, $data)
    {
        return $query->whereLike('title', $value);
    }

    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where(app(Notice::class)->getTable() . '.status', $value);
    }
}
