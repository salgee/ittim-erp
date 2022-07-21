<?php
/*
 * @Date: 2021-05-27 07:08:45
 * @LastEditTime: 2022-01-13 14:27:25
 */


namespace catchAdmin\warehouse\model\search;

use catchAdmin\supply\model\SubOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseOrderProducts;

trait WarehouseOrderSearch
{
    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('w.audit_status', $value);
    }

    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('w.code', $value);
    }

    public function searchSourceAttr($query, $value, $data)
    {
        return $query->where('w.source', $value);
    }

    public function searchCabinetNoAttr($query, $value, $data)
    {
        $ids = SubOrders::alias('so')->leftJoin('transhipment_orders to', 'to.id = so.trans_order_id')
            ->whereLike('to.cabinet_no', $value)->column('so.warehouse_order_id');

        return $query->whereIn('w.id', $ids);
    }
    // 仓库
    public function searchWarehouseNameAttr($query, $value, $data)
    {
        $warehouseId = Warehouses::whereLike('name', $value)->value('id') ?? '';
        if ($warehouseId) {
            $map1 = [
                ['w.virtual_warehouse_id', '=',  $warehouseId],
            ];
            $map2 = [
                ['w.entity_warehouse_id', '=', $warehouseId],
            ];
            return $query->where(function ($query)  use ($map1, $map2) {
                $query->whereOr([$map1, $map2]);
            });
        } else {
            return $query->where('w.id', '');
        }
    }
    // 商品code
    public function searchGoodsCodeAttr($query, $value, $data)
    {
        $ids = WarehouseOrderProducts::where('goods_code', $value)->column('warehouse_order_id');
        if ($ids && count($ids) > 0) {
            $idString = implode(',', $ids);
            return $query->whereIn('w.id', $idString);
        } else {
            return $query->whereIn('w.id', '');
        }
    }
    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '>=', strtotime($value));
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('w.created_at', '<=', strtotime($value));
    }
}
