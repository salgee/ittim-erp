<?php

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class LogisticsFeeConfigInfo extends Model
{
    // 表名
    public $name = 'logistics_fee_config_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联物流台阶费用ID logistics_fee_config
        'logistics_fee_id',
        // 毛重 计费重量(lbs)
        'weight',
        // zone2(USD)
        'zone2',
        // zone3(USD)
        'zone3',
        // zone4(USD)
        'zone4',
        // zone5(USD)
        'zone5',
        // zone6(USD)
        'zone6',
        // zone7(USD)
        'zone7',
        // zone8(USD)
        'zone8',
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
   
    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('logistics_fee_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }
}