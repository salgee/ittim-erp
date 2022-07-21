<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 12:27:21
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-16 11:08:27
 * @Description:
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\ZipCodeSpecialSearch;

use catcher\base\CatchModel as Model;

class ZipCodeSpecial extends Model
{
    use ZipCodeSpecialSearch;

    // 表名
    public $name = 'zip_code_special';
    // 数据库字段映射
    public $field = array(
        'id',
        // 邮编
        'zipCode',
        // 类型，1：偏远邮编，2：超偏远邮编
        'type',
        // 修改人
        'update_by',
        // 备用字段
        'spare',
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
     * @time 2021/2/6
     * @param $params
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        return $this->field('z.id, z.zipCode, z.type, z.creator_id, z.updated_at, z.created_at, z.update_by,
            u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('z')
            ->catchSearch()
            ->order('z.id', 'desc')
            ->leftJoin('users u', 'u.id = z.creator_id')
            ->leftJoin('users us', 'us.id = z.update_by')
            ->paginate();
    }

    public function getZip($zip_code, $field)
    {
        return $this->field($field)
            ->where('zipCode', '=', $zip_code)
            ->find();

    }
}
