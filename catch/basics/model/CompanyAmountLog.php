<?php

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class CompanyAmountLog extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'company_amount_log';

    // 数据库字段映射
    public $field = array(
        'id',
        // 客户id 关联company 表
        'company_id',
        // 修改前的用户金额
        'before_modify_amount',
        // 扣除用户的金额数
        'subtract_amount',
        // 扣除后用户余额
        'charge_balance',
        // 扣费类型：1.发货扣费
        'type',
        // 修改人
        'update_by',
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
