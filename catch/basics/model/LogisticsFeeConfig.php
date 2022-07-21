<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-25 11:00:03
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-26 15:34:48
 * @Description:
 */

namespace catchAdmin\basics\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\basics\model\search\LogisticsFeeConfigSearch;
use catcher\base\CatchModel as Model;
use catchAdmin\permissions\model\DataRangScopeTrait;


class LogisticsFeeConfig extends Model
{
    use LogisticsFeeConfigSearch;
    use DataRangScopeTrait;

    // 表名
    public $name = 'logistics_fee_config';
    // 数据库字段映射
    public $field = array(
        'id',
        // 状态，1：正常，2：禁用
        'is_status',
        // 模板名称
        'name',
        // 是否更新商品价格 0-待更新 1-已更新
        'is_update_price',
        // 客户（公司）id 关联表 company
        'company_id',
        // 保价费设置费用(USD)/每100usd
        'insurance_fee',
        // 毛重(lbs)
        'gross_weight',
        // 毛重费用(包裹)
        'gross_weight_fee',
        // 最大边长（英寸）
        'big_side_length',
        // 最大边长费用(包裹)
        'big_side_length_fee',
        // 次长边（英寸）
        'second_side_length',
        // 次长边长费用(包裹)
        'second_side_length_fee',
        // 最小oversize（英寸）
        'oversize_min_size',
        // 最大oversize（英寸）
        'oversize_max_size',
        // 大于最小小于最大值费用(包裹)
        'oversize_fee',
        // 大于oversize尺寸（英寸），规则二
        'oversize_other_size',
        // 大于oversize尺寸，规则二
        'oversize_other_size_fee',
        // 偏远地区附加费(/包裹)
        'remote_fee',
        // 超偏远地区附加费
        'super_remote_fee',
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
     * 列表
     * @return \think\Paginator
     */
    public function getList()
    {
        $users = new Users;
        $powerData = $users->getRolesList();
        $where = [];
        if (!$powerData['is_admin']) {
            if ($powerData['is_company']) {
                $where = [
                    'l.company_id' => $powerData['company_id']
                ];
            }
        }
        return $this
            ->field('l.*, IFNULL(us.username, "-") as update_name, c.name as company_name')
            ->catchJoin(Users::class, 'id', 'creator_id', ['username as creator_name'])
            ->alias('l')
            ->whereOr($where)
            ->leftJoin('company c', 'c.id = l.company_id')
            ->leftJoin('users us', 'us.id = l.update_by')
            ->catchSearch()
            ->order('l.id', 'desc')
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
     * 获取商品的对应重量模板信息
     */
    public function getShopWeightConfig($id, $w, $zone, $field = 0)
    {
        if ($field == 0) {
            $field = "a.insurance_fee,a.gross_weight,a.gross_weight_fee,a.big_side_length,a.big_side_length_fee,
            a.second_side_length,a.second_side_length_fee,a.oversize_min_size,a.oversize_max_size,a.oversize_fee,
            a.oversize_other_size,a.oversize_other_size_fee,a.remote_fee,a.super_remote_fee,a.id,b.zone" . $zone;
        }
        return $this->field($field)
            ->alias('a')
            ->where('company_id', '=', $id)
            ->where('is_status', '=', 1)
            ->leftJoin("logistics_fee_config_info b", 'a.id = b.logistics_fee_id and b.weight=' . $w)
            ->find();
    }

}
