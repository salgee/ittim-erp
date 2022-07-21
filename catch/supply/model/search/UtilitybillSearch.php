<?php
/*
 * @Version: 1.0
 * @Date: 2021-06-09 20:48:22
 * @LastEditTime: 2021-11-17 11:17:31
 * @Description: 
 */
namespace catchAdmin\supply\model\search;
use catchAdmin\system\model\DictionaryData;
use catchAdmin\basics\model\Lforwarder;



trait UtilitybillSearch
{
    // 提单号
    public function searchBlNoAttr($query, $value, $data)
    {
        return $query->whereLike('bl_no', $value);
    }

    // 导入时间 开始时间  起运日期 shipment_date
    public function searchShipmentDateStartAttr($query, $value, $data)
    {
        return $query->where('shipment_date', '>=', $value);
    }

    // 结束时间起运日期
    public function searchShipmentDateEndAttr($query, $value, $data)
    {
        return $query->where('shipment_date', '<=', $value);
    }

    // 起运日期 起运日期
    public function searchShipmentDateAttr($query, $value, $data)
    {
        return $query->where('shipment_date', $value);
    }

    // 结束时间  装柜日期
    public function searchLoadingDateEndAttr($query, $value, $data)
    {
        return $query->where('loading_date', '<=', $value);
    }

    // 起运日期  装柜日期
    public function searchLoadingDateStartAttr($query, $value, $data)
    {
        return $query->where('loading_date', '>=', $value);
    }

    // 装柜日期 
    public function searchLoadingDateAttr($query, $value, $data)
    {
        return $query->where('loading_date', $value);
    }

    // 柜号 cabinet_no 
    public function searchCabinetNoAttr($query, $value, $data)
    {
        return $query->whereLike('cabinet_no', $value);
    }

    // 目的港 
    public function searchDestinationPortAttr($query, $value, $data)
    {
        $name = DictionaryData::where('dict_data_name', $value)->value('id') ?? '0';
        return $query->where('destination_port', $name);
    }

    // 国内段（货代）
    public function searchdomesticNameAttr($query, $value, $data)
    {
        $name = Lforwarder::whereLike('name', $value)->column('id') ?? '';
        return $query->whereIn('domestic_lforwarder_id', $name);
    }

    // 海运段（货代）
    public function searchOceanNameAttr($query, $value, $data)
    {
        $name = Lforwarder::whereLike('name', $value)->column('id') ?? '';
        return $query->whereIn('ocean_lforwarder_id', $name);
    }

    // 国外（货代）
    public function searchOverseasNameAttr($query, $value, $data)
    {
        $name = Lforwarder::whereLike('name', $value)->column('id') ?? '';
        return $query->whereIn('overseas_lforwarder_id', $name);
    }
}