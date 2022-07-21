<?php
/*
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2021-08-20 14:18:59
 */

namespace catchAdmin\order\model\search;

trait OrderGetRecordsSearch
{
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime('get_at', '>=', strtotime($value));
    }

    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime('get_at', '<=', strtotime($value));
    }

    // 平台名称
    public function searchPlatformNameAttr($query, $value)
    {
        return $query->whereLike('platform_name', $value);
    }

    // 店铺id
    public function searchShopIdAttr($query, $value)
    {
        return $query->where('shop_basics_id', $value);
    }
}
