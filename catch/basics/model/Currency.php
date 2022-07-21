<?php
/*
 * @Author: your name
 * @Date: 2021-02-04 18:04:38
 * @LastEditTime: 2021-05-16 11:06:30
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\Currency.php
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\CurrencySearch;
use catcher\base\CatchModel as Model;

class Currency extends Model
{
    use CurrencySearch;
    // 表名
    public $name = 'currency';
    // 数据库字段映射
    public $field = array(
        'id',
        // 源转换币种代码
        'source_code',
        // 源转换币种名称
        'source_name',
        // 目标转换币种代码
        'target_code',
        // 目标转换币种名称
        'target_name',
        // 兑换比例 汇率
        'rate',
        // 状态，1：正常，2：禁用
        'is_status',
        // 说明
        'remarks',
        // 修改人id
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
    {
        return $this->field('c.id, c.source_code, c.source_name, c.rate, c.is_status, c.remarks, c.updated_at, c.created_at,
            u.username as creator_name, IFNULL(us.username, "-") as update_name ')
            ->alias('c')
            ->catchSearch()
            ->leftJoin('users u', 'u.id = c.creator_id')
            ->leftJoin('users us', 'us.id = c.update_by')
            ->order('c.id', 'desc')
            ->paginate();
    }
    /** 
     * get list
     * 
     * @time 2021/2/3
     * @param $params
     * @throws \think\db\exception\DbException
     * @return array
     */
    public function getAllList(): array
    {
        return $this->field(['source_code', 'source_name', 'rate', 'is_status'])
            ->catchOrder()
            ->catchSearch()
            ->select()->toArray();
    }
}