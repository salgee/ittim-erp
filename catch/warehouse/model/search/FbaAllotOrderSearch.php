<?php
/*
 * @Date: 2021-06-03 21:30:19
 * @LastEditTime: 2022-01-13 15:31:43
 */


namespace catchAdmin\warehouse\model\search;


use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\FbaAllotOrderProducts;

trait FbaAllotOrderSearch
{
    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('w.audit_status', $value);
    }

    public function searchWarehouseNameAttr($query, $value, $data)
    {
        $warehouseId = Warehouses::whereLike('name', $value)->value('id') ?? '';
        return $query->where('w.entity_warehouse_id', $warehouseId)
            ->whereOr('w.virtual_warehouse_id', $warehouseId)
            ->whereOr('w.fba_warehouse_id', $warehouseId);
    }

    public function searchBillOfLadingNumberAttr($query, $value, $data)
    {
        return $query->whereLike('w.bill_of_lading_number', $value);
    }
    public function searchAmazonPoIdAttr($query, $value, $data)
    {
        return $query->whereLike('w.amazon_po_id', $value);
    }

    // 商品code
    public function searchGoodsCodeAttr($query, $value, $data)
    {
        $ids = FbaAllotOrderProducts::where('goods_code', $value)->column('fba_allot_order_id');
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
