<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 15:16:34
 * @LastEditors:
 * @LastEditTime: 2021-02-05 16:14:31
 * @Description: 
 */

namespace catchAdmin\basics\model;
use catchAdmin\basics\model\search\LfCurrencySearch;
use catcher\base\CatchModel as Model;
use catchAdmin\permissions\controller\User;

class LfCurrency extends Model
{
    use LfCurrencySearch;
    // 表名
    public $name = 'lforwarder_company_currency';
    // 数据库字段映射
    public $field = array(
        'id',
        // 物流公司id && 货代公司id
        'lforwarder_company_id',
        // 币别id
        'currency_id',
        // 币种名称
        'target_name',
        // 银行名称
        'bank_name',
        // 银行卡号
        'bank_number',
        // 修改人
        'update_by',
        // 状态，1：正常，0：禁用
        'is_status',
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