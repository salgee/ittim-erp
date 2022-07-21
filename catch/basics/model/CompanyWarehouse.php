<?php
/*
 * @Version: 1.0
 * @Date: 2021-06-22 14:33:14
 * @LastEditTime: 2021-06-22 14:51:57
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class CompanyWarehouse extends Model
{
    // 表名
    public $name = 'company_warehouse';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联客户id  关联店铺表 company
        'company_id',
        // 实体仓库id  关联仓库表 warehouse
        'warehouse_id',
        // 虚拟仓库id  关联仓库表 warehouse
        'warehouse_fictitious_id',
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
     * 获取所有子数组
     * */
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('company_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }
}