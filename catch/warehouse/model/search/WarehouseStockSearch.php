<?php


namespace catchAdmin\warehouse\model\search;


use catchAdmin\product\model\Product;
use catchAdmin\warehouse\model\Warehouses;

trait WarehouseStockSearch
{
    public function searchGoodsCodeAttr($query, $value, $data)
    {
       
        return $query->whereLike('goods_code', $value);
    }
    
    public function searchNameChAttr($query, $value, $data)
    {
        
        $goodsCodes = Product::whereLike('name_ch', $value)->column('goods_code');
        return $query->whereIn('goodsCode', $goodsCodes);
    }
    
    public function searchNameEnAttr($query, $value, $data)
    {
        $goodsCodes = Product::whereLike('name_en', $value)->column('goods_code');
        return $query->whereIn('goodsCode', $goodsCodes);
    }
    
    public function searchWarehouseAttr($query, $value, $data)
    {
        $warehouse  = Warehouses::where('name', $value)->value('id') ?? 0;
        return $query->whereRaw("entity_warehouse_id = $warehouse or virtual_warehouse_id = $warehouse");
    }
}