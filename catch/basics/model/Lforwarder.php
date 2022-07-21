<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 10:17:10
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-02 21:59:56
 * @Description: 
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\LforwarderSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catcher\base\CatchModel as Model;

class Lforwarder extends Model
{
    use DataRangScopeTrait;
    use LforwarderSearch;
    // 表名
    public $name = 'lforwarder_company';
    // 数据库字段映射
    public $field = array(
        'id',
        // 公司类型，1：物流，2：货代
        'type',
        // 代码
        'code',
        // 名称
        'name',
        // 结算周期
        'settlement_cycle',
        // 状态，1：正常，2：禁用
        'is_status',
        // 备注
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
        return $this
            ->field('l.id, l.type, l.code, l.name, l.settlement_cycle, l.is_status, l.remarks, l.updated_at, l.created_at,
            u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->catchSearch()
            ->alias('l')
            ->leftJoin('users u', 'u.id = l.creator_id')
            ->leftJoin('users us', 'us.id = l.update_by')
            ->order('id', 'desc')
            ->paginate();
    }
}
