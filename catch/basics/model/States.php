<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-20 11:31:25
 * @LastEditors:
 * @LastEditTime: 2021-03-20 14:08:12
 * @Description:
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class States extends Model
{
    // 表名
    public $name = 'states';
    // 数据库字段映射
    public $field = array(
        'id',
        // 国家id默认
        'country_id',
        // 国家代码 默认美国
        'country_code',
        // 州代码
        'code',
        // 州名称—英文
        'name',
        // 州名称-中文
        'cname',
        // 小写名称
        'lower_name',
        // 州代码全称
        'code_full',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    /**
     * @param string $state
     * @return mixed|string
     */
    public function getStates($state = '')
    {
        $state = trim($state);
        if (strlen($state) == 2){
            return strtoupper($state);
        }else {
            return $this->where(['lower_name' => strtolower($state)])->value('code') ?? $state;
        }
    }
}
