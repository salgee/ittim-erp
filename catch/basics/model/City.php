<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-20 11:41:00
 * @LastEditors:
 * @LastEditTime: 2021-03-22 10:32:54
 * @Description:
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

use catchAdmin\basics\model\search\CitySearch;

class City extends Model
{
    use CitySearch;
    // 表名
    public $name = 'city';
    // 数据库字段映射
    public $field = array(
        'id',
        // 州id 关联 states表
        'states_id',
        // 城市代码
        'code',
        // 城市名称—英文
        'name',
        // 城市名称-中文
        'cname',
        // 小写名称
        'lower_name',
        // 城市代码全称
        'code_full',
        // 经度
        'lon',
        // 维度
        'lat',
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
     * get list
     *
     * @time 2021/2/7
     * @param $params
     * @throws \think\db\exception\DbException
     *
     */
    public function getList()
    {
        return $this
            ->catchSearch()
            ->select();
    }
}
