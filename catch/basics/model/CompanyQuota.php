<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 15:55:16
 * @LastEditors:
 * @LastEditTime: 2021-02-06 16:01:53
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class CompanyQuota extends Model
{
    // 表名
    public $name = 'company_quota';
    // 数据库字段映射
    public $field = array(
        'id',
        // 客户id 关联company 表
        'company_id',
        // 币别id currency 表
        'currency_id',
        // 币别名称 关联 currency
        'currency_name',
        // 额度
        'quota',
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