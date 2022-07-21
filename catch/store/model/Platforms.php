<?php
/*
 * @Description: 
 * @Author: maryna
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2021-07-27 15:19:35
 */

namespace catchAdmin\store\model;

use catchAdmin\store\model\search\PlatformsSearch;
use catcher\base\CatchModel as Model;

class Platforms extends Model
{
    use PlatformsSearch;

    // 表名
    public $name = 'platform';
    // 数据库字段映射
    public $field = array(
        'id',
        // 平台名称
        'name',
        // 平台中文名称
        'name_ch',
        // 开发文档链接
        'develop_url',
        // 账号配置
        'platform_parameters',
        // 备注
        'remark',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );
    // 设置 json 字段
    protected $json = ['platform_parameters'];

    // 把 json 返回格式转换为数组
    protected $jsonAssoc = true;
}
