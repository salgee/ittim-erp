<?php
/*
 * @Date: 2021-06-03 21:30:19
 * @LastEditTime: 2022-01-13 14:32:17
 */


namespace catchAdmin\warehouse\model\search;


use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\AllotOrderProducts;

trait AllotOrderSearch
{
    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('w.audit_status', $value);
    }

    public function searchWarehouseNameAttr($query, $value, $data)
    {
        $warehouseId = Warehouses::whereLike('name', $value)->value('id') ?? '';
        // return $query->where('entity_warehouse_id', $warehouseId)
        //     ->whereOr('transfer_in_warehouse_id', $warehouseId)
        //     ->whereOr('transfer_out_warehouse_id', $warehouseId);

        if ($warehouseId) {
            $map1 = [
                ['w.entity_warehouse_id', '=',  $warehouseId],
            ];
            $map2 = [
                ['w.transfer_in_warehouse_id', '=', $warehouseId],
            ];
            $map3 = [
                ['w.transfer_out_warehouse_id', '=',  $warehouseId],
            ];
            return $query->where(function ($query)  use ($map1, $map2, $map3) {
                $query->whereOr([$map1, $map2, $map3]);
            });
        } else {
            return $query->where('w.id', '');
        }
    }

    // 商品code
    public function searchGoodsCodeAttr($query, $value, $data)
    {
        $ids = AllotOrderProducts::where('goods_code', $value)->column('allot_order_id');
        if ($ids && count($ids) > 0) {
            $idString = implode(',', $ids);
            return $query->whereIn('w.id', $idString);
        } else {
            return $query->whereIn('w.id', '');
        }
    }

    // 开始时间
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '<=', strtotime($value));
    }
}
