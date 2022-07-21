<?php

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;
use catchAdmin\order\model\search\OrderGetRecordsSearch;

class OrderGetRecords extends Model
{
    use OrderGetRecordsSearch;

    // 表名
    public $name = 'order_get_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // 平台名称
        'platform_name',
        // 接口名称
        'interface_name',
        // 接口请求时间
        'get_at',
        // 接口返回数量
        'get_count',
        // 店铺ID
        'shop_basics_id',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );
}
