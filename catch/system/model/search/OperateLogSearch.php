<?php
/*
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2021-10-14 16:36:53
 */

namespace catchAdmin\system\model\search;

use catchAdmin\permissions\model\Users;

trait OperateLogSearch
{
    public function searchModuleAttr($query, $value, $data)
    {
        return $query->whereLike('module', $value);
    }

    public function searchMethodAttr($query, $value, $data)
    {
        return $query->whereLike('method', $value);
    }

    public function searchCreatorAttr($query, $value, $data)
    {
        return $query->whereLike(app(Users::class)->getTable() . '.username', $value);
    }

    public function searchCreateAtAttr($query, $value, $data)
    {
        return $query->whereTime($this->aliasField('created_at'), 'between', $value);
    }
    public function searchRouteAttr($query, $value, $data)
    {
        return $query->whereLike('route', $value);
    }
    public function searchOperateAttr($query, $value, $data)
    {
        return $query->whereLike('operate', $value);
    }
}
