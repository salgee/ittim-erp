<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-08 16:05:22
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-17 13:04:12
 * @Description:
 */

namespace catchAdmin\product\model\search;

trait ProductCombinationSearch
{
    public function searchKeysAttr($query, $value, $data)
    {
        // URL传参解码（+加号问题）
        $value = rawurldecode(urlencode(urldecode($value)));

        $value1 = [
            ['p.name_ch', 'like',  "%{$value}%"],
        ];
        $value2 = [
            ['p.code', 'like', "%{$value}%"],
        ];
        $value3 = [
            ['p.name_en', 'like', "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($value1, $value2, $value3) {
            $query->whereOr([$value1, $value2, $value3]);
        });
    }
    // 通过店铺id搜索组合商品
    public function searchShopIdAttr($query, $value)
    {
        return $query->where('p.shop_id', $value);
    }
}
