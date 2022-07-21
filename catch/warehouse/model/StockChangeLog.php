<?php
/*
 * @Date: 2021-08-05 18:18:56
 * @LastEditTime: 2021-08-23 13:01:33
 */

namespace catchAdmin\warehouse\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class StockChangeLog extends Model
{
    use BaseOptionsTrait, ScopeTrait;

    // 表名
    public $name = 'stock_change_log';
    // 数据库字段映射
    public    $field
    = array(
        'id',
        // 对应操作model名称
        'change_model',
        // 订单id
        'order_id',
        'add_number',
        'reduce_number',
        'before_number',
        'after_number',
        //商品编码
        'goods_code',
        // 批次号
        'batch_no',
        // 虚拟仓库id
        'warehouse_id',
        // 销售订单id
        'order_id_sale',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );
}
