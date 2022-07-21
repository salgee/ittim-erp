<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-24 15:13:41
 * @LastEditors:
 * @LastEditTime: 2021-02-24 16:49:42
 * @Description:
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class StorageFeeConfigInfo extends Model
{
    // 表名
    public $name = 'storage_fee_config_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 仓储台阶服务费ID
        'fee_config_id',
        // 最小天数（天）
        'min_days',
        // 最大天数（天）
        'max_days',
        // 费用(USD)/每体积
        'fee',
        // 多个仓库使用逗号 ","隔开
        'warehouse_id',
        // 修改人ID
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

    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('fee_config_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }

    public function getStorageInfo($id)
    {
        return $this->where('fee_config_id', $id)
            ->select();
    }
}
