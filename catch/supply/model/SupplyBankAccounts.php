<?php

namespace catchAdmin\supply\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class SupplyBankAccounts extends Model
{
    use BaseOptionsTrait, ScopeTrait;
    // 表名
    public $name = 'supply_bank_accounts';
    // 数据库字段映射
    public $field = array(
        'id',
        //供应商id
        'supply_id',
        // 币别
        'currency',
        // 开户行
        'bank',
        // 银行账号
        'bank_account',
        // 创建时间
        'created_at',
        // 修改时间
        'update_at',
    );
}