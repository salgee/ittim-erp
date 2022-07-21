<?php

namespace catchAdmin\supply\model;

use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use think\model\concern\SoftDelete;

class SubOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'sub_orders';
    // 数据库字段映射
    public $field = array(
        'id',
        // 转运单id
        'trans_order_id',
        // 转运单商品id
        'trans_goods_id',
        //实体仓id
        'entity_warehouse_id',
        //虚拟仓id
        'virtual_warehouse_id',
        // 分仓数量
        'number',
        //入库单id
        'warehouse_order_id',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    protected $append = [
        'entity_warehouse', 'virtual_warehouse'
    ];


    public function getEntityWarehouseAttr() {
        return Warehouses::where('id', $this->getAttr('entity_warehouse_id'))->value('name') ?? '';
    }

    public function getVirtualWarehouseAttr() {
        return Warehouses::where('id', $this->getAttr('virtual_warehouse_id'))->value('name') ?? '';
    }


    public function product() {
        return $this->belongsTo(TranshipmentOrderProducts::class, 'trans_goods_id', 'id');
    }

    public function transOrder() {
        return $this->belongsTo(TranshipmentOrders::class, 'trans_order_id', 'id');
    }
}