<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 11:42:38
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-07 08:07:46
 * @Description: 偏远分区表
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\ZipCodeSearch;

use catcher\base\CatchModel as Model;

class ZipCode extends Model
{
    use ZipCodeSearch;

    // 表名
    public $name = 'zip_code_division';
    // 数据库字段映射
    public $field = array(
        'id',
        // 仓库邮编
        'origin',
        // 订单邮编
        'dest_zip',
        // 数据字典 属性信息，只做标识
        'state',
        // 数字越小，距离越近
        'zone',
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
     * @time 2021/2/6
     * @param $params
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        return $this->field('z.id, z.origin, z.dest_zip, z.state,z.zone, z.creator_id, z.updated_at, z.created_at, z.update_by,
            u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('z')
            ->catchSearch()
            ->order('z.id', 'desc')
            ->leftJoin('users u', 'u.id = z.creator_id')
            ->leftJoin('users us', 'us.id = z.update_by')
            ->paginate();
    }

    /**
     * 获取匹配的邮编分区
     */
    public function selZipzone($data, $stock)
    {
        // 使用邮编前三位匹配
        // $code = explode('-', $data);
        $code = substr($data, 0, 3);

        return $this->whereLike('dest_zip', $code)
            ->where('origin', $stock)
            ->find();

        // $code = explode('-', $data);
        // return $this->whereLike('dest_zip', $code[0])
        //     ->where('origin', $stock)
        //     ->find();
    }
}
