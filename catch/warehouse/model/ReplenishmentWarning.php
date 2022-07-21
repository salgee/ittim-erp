<?php

namespace catchAdmin\warehouse\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class ReplenishmentWarning extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'replenishment_warning';
    // 数据库字段映射
    public $field = array(
        // 盘点单号
        'code',
        // 商品名
        'name_ch',
        // 商品名 英文
        'name_en',
        // 即时库存
        'stock',
        // 在途库存
        'trans_stock',
        // 销量
        'salse',
        // 预警时间
        'check_date',
        // 创建人
        'created_by',
        // 修改人
        'updated_by',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );
}