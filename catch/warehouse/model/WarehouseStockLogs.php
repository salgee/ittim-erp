<?php

namespace catchAdmin\warehouse\model;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class WarehouseStockLogs extends Model
{
    use BaseOptionsTrait, ScopeTrait;

    // 表名
    public $name = 'warehouse_stock_logs';
    // 数据库字段映射
    public    $field
    = array(
        'id',
        // 商品编码
        'goods_code',
        // 实体仓id
        'entity_warehouse_id',
        // 虚拟仓id
        'virtual_warehouse_id',
        // 库存数量
        'number',
        //批次号
        'batch_no',
        //类型 1-商品 2-配件
        'goods_type',
        //备份日期
        'log_date',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );
}
