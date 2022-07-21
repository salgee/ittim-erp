<?php
/*
 * @Date: 2021-06-10 19:55:36
 * @LastEditTime: 2021-08-25 15:48:17
 */


namespace catchAdmin\settlement\model\search;

trait StorageProductFeeSearch
{
    public function searchGoodsCodeAttr($query, $value, $data)
    {
        return $query->whereLike('goods_code', $value);
    }

    public function searchGoodsNameAttr($query, $value, $data)
    {
        return $query->whereLike('goods_name', $value);
    }
    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '>=', $value);
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '<=', $value);
    }
}
