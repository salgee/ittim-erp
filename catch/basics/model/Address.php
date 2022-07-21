<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:42:04
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-16 11:06:02
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\AddressSearch;

use catcher\base\CatchModel as Model;

class Address extends Model
{
    use AddressSearch;
    // 表名
    public $name = 'address';
    // 数据库字段映射
    public $field = array(
        'id',
        // 城市
        'city',
        // 城市code
        'city_code',
        // 州
        'state',
        // 州code
        'state_code',
        // 邮政区号
        'area_code',
        // 街道
        'street',
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
    /**
     * get list
     *
     * @time 2021/2/3
     * @param $params
     * @throws \think\db\exception\DbException
     * @return \think\Paginator
     */
    public function getList()
    {        return $this->field('a.id, a.city, a.city_code, a.state, a.state_code, a.area_code, a.street, a.updated_at, a.created_at,
            u.username as creator_name, IFNULL(us.username, "-") as update_name ')
            ->alias('a')
            ->catchSearch()
            ->leftJoin('users u', 'u.id = a.creator_id')
            ->leftJoin('users us', 'us.id = a.update_by')
            ->order('a.id', 'desc')
            ->paginate();
    }

}
