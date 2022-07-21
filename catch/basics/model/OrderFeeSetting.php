<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-07 14:29:53
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-03 17:56:59
 * @Description:
 */

namespace catchAdmin\basics\model;

use catcher\base\CatchModel as Model;
use catchAdmin\basics\model\search\OrderFeeSettingSearch;
use catchAdmin\permissions\model\Users;
use catchAdmin\permissions\model\DataRangScopeTrait;


class OrderFeeSetting extends Model
{
    use OrderFeeSettingSearch;
    use DataRangScopeTrait;

    // 表名
    public $name = 'order_fee_setting';
    // 数据库字段映射
    public $field = array(
        'id',
        // 客户id 关联company 表
        'company_id',
        // 模板名称
        'name',
        // 最小重量（lbs）
        'min_weight',
        // 最大重量（lbs）
        'max_weight',
        // 费用(USD)/每件
        'fee',
        // 父级id
        'parent_id',
        // 状态，1：正常，0：禁用
        'is_status',
        // 是否更新商品价格 0-待更新 1-已更新
        'is_update_price',
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

    // getList

    /**
     * get list
     *
     * @time 2021/2/3
     * @param $params
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'o.company_id' => $prowerData['company_id']
                ];
            }
        }

        return $this->field('o.id, o.company_id, o.name, c.name as company_name, o.is_status,
            o.updated_at, o.created_at, o.is_update_price,
            u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->where('parent_id', 0)
            ->catchSearch()
            ->whereOr($where)
            ->alias('o')
            ->leftJoin('company c', 'c.id = o.company_id')
            ->leftJoin('users u', 'u.id = o.creator_id')
            ->leftJoin('users us', 'us.id = o.update_by')
            ->order('o.id', 'desc')
            ->paginate();
    }

    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('parent_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }

    /**
     * 批量修改状态
     */
    public function isDsable($id, $status)
    {
        return $this->where(['company_id' => $id, 'parent_id' => 0])->update(['is_status' => $status]);
    }

    /**
     * 获取客户订单操作费用
     * @param $data 客户id
     * @param $width 重量
     */
    public function getUserOrderAmount($data, $weight)
    {
        if(!$id=$this->where(['company_id' => $data, 'parent_id' => 0])->where('is_status', '=', 1)->value('id')) {
            return false;
        }
        return $this->field('fee')
            ->where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            // ->where('company_id', '=', $data)
            ->where('parent_id', '=', $id)
            ->find();

    }
}
