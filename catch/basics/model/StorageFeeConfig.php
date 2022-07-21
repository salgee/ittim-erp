<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-24 15:03:47
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-18 11:43:15
 * @Description:
 */

namespace catchAdmin\basics\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\basics\model\Company;
use catchAdmin\basics\model\search\StorageFeeConfigSearch;
use catcher\base\CatchModel as Model;
use catchAdmin\permissions\model\DataRangScopeTrait;

class StorageFeeConfig extends Model
{
    use StorageFeeConfigSearch;
    use DataRangScopeTrait;
    // 表名
    public $name = 'storage_fee_config';
    // 数据库字段映射
    public $field = array(
        'id',
        // 状态，1：正常，2：禁用
        'is_status',
        // 模板名称
        'name',
        // 客户（公司）id
        'company_id',
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

    /**
     * 列表
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    's.company_id' => $prowerData['company_id']
                ];
            }
        }
        return $this->field('s.*, IFNULL(us.username, "-") as update_name, c.name as company_name')
            ->catchJoin(Users::class, 'id', 'creator_id', ['username as creator_name'])
            ->catchSearch()
            ->whereOr($where)
            ->leftJoin('company c', 'c.id = s.company_id')
            ->leftJoin('users us', 'us.id = s.update_by')
            ->alias('s')
            ->order('s.id', 'desc')
            ->paginate();
    }

    /**
     * 批量修改状态
     */
    public function isDsable($id, $status)
    {
        return $this->where('company_id', $id)->update(['is_status' => $status]);
    }

    /**
     * 用户模板id查询
     */
    public function getStorageConfig($id)
    {

        return $this->field('a.id')
            ->alias('a')
            ->where('a.company_id', $id)
            ->where('a.is_status', '=', 1)
            ->find();

    }
}
