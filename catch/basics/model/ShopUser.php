<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-16 18:29:35
 * @LastEditors:
 * @LastEditTime: 2021-03-16 18:35:56
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;

class ShopUser extends Model
{
    // 表名
    public $name = 'shop_user';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联店铺id
        'shop_id',
        // 用户id
        'user_id',
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
            ->where('shop_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }
}