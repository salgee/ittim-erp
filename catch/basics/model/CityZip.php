<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-20 11:41:00
 * @LastEditors:
 * @LastEditTime: 2021-03-23 18:56:46
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class CityZip extends Model
{
    // 表名
    public $name = 'city_zip';
    // 数据库字段映射
    public $field = array(
        'id',
        // 城市表ID
        'city_id',
        // 邮编
        'zip',
        // 经度
        'lon',
        // 维度
        'lat',
        // 时区
        'timezone',
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
