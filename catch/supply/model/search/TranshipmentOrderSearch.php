<?php
/*
 * @Date: 2021-06-03 21:30:19
 * @LastEditTime: 2022-01-13 17:32:08
 */

namespace catchAdmin\supply\model\search;

use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\basics\model\Lforwarder;

trait TranshipmentOrderSearch
{


    public function searchCodeAttr($query, $value, $data)
    {
        return $query->whereLike('code', $value);
    }

    public function searchBlNoAttr($query, $value, $data)
    {
        return $query->whereLike('bl_no', $value);
    }

    public function searchCabinetNoAttr($query, $value, $data)
    {
        return $query->whereLike('cabinet_no', $value);
    }

    public function searchAuditStatusAttr($query, $value, $data)
    {
        return $query->where('audit_status', $value);
    }

    public function searchSupplyAttr($query, $value, $data)
    {
        return $query->where('supply_id', $value);
    }

    public function searchIsBillAttr($query, $value, $data)
    {
        if ($value == 1) {
            return $query->where('bill_id', 0);
        }
    }

    public function searchContractIdAttr($query, $value, $data)
    {
        return $query->where('purchase_contract_id', $value);
    }

    public function searchArriveStatusAttr($query, $value, $data)
    {
        return $query->where('arrive_status', $value);
    }

    public function searchArriveDateAttr($query, $value, $data)
    {
        return $query->where('arrive_date', $value);
    }

    public function searchWarehouseAttr($query, $value, $data)
    {
        $warehouseId = Warehouses::whereLike('name', $value)->column('id') ?? [];
        return $query->whereIn('warehouse_id', $warehouseId);
    }

    public function searchIsSubAttr($query, $value, $data)
    {
        return $query->where('is_sub', $value);
    }

    public function searchSubConfirmAttr($query, $value, $data)
    {
        return $query->where('sub_confirm', $value);
    }
    // 货代 
    public function searchLforwarderCompanyAttr($query, $value, $data)
    {
        $id = Lforwarder::whereLike('name', $value)->column('id') ?? [];
        return $query->whereIn('lforwarder_company', $id);
    }
    // 起运日期
    public function searchShipmentDateAttr($query, $value, $data)
    {
        return $query->where('shipment_date', $value);
    }
    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '>=', strtotime($value));
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('created_at', '<=', strtotime($value));
    }
}
