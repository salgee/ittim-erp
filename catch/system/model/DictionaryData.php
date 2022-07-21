<?php
/*
 * @Version: 1.0
 * @Date: 2021-01-23 09:25:36
 * @LastEditTime: 2021-04-08 18:16:05
 * @Description: 
 */


namespace catchAdmin\system\model;

use catcher\base\CatchModel as Model;

class DictionaryData extends Model
{
    protected $name = 'dictionary_data';

    protected $field = [
        'id', // 
		'dict_value', // all_dictionary表中的字典值
		'dict_data_name', // 字典名称
		'dict_data_value', // 字典值(固定的不可变)
		'sort', // 排序
		'creator_id', // 创建人ID
		'created_at', // 创建时间
		'updated_at', // 更新时间
		'deleted_at', // 软删除
    ];

    /**
     * 重构查询Db::table('user')->where('id',5)->cache(true)->find()
     * 
     */
    public function getDictName($id) {
        return $this->where('id', $id)
            ->cache(true)
            ->column('dict_data_name');
    }
}