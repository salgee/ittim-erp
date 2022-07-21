<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-08 15:28:59
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-24 10:52:49
 * @Description:
 */

namespace catchAdmin\product\model\search;

trait ProductPriceSearch
{
    // 关键词
    public function searchKeysAttr($query, $value, $data)
    {
        $value1 = [
            ['pd.name_ch', 'like',  "%{$value}%"],
        ];
        $value2 = [
            ['pd.name_en', 'like', "%{$value}%"],
        ];
        $value3 = [
            ['pd.code', 'like', "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($value1, $value2, $value3) {
            $query->whereOr([$value1, $value2, $value3]);
        });
    }
    // 编号
    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('pd.code', $value);
    }
    // 中文名称
    public function searchNameChAttr($query, $value, $data)
    {
        return $query->whereLike('pd.name_ch', $value);
    }
    // 英文名称
    public function searchNameEnAttr($query, $value, $data)
    {
        return $query->whereLike('pd.name_en', $value);
    }
    // 状态
    public function searchStatusAttr($query, $value, $data)
    {
        return $query->where('p.status', $value);
    }
}
