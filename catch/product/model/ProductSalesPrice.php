<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-09 09:56:56
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-04 15:36:11
 * @Description: 
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;

use catchAdmin\product\model\search\ProductSalesPriceSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;

class ProductSalesPrice extends Model
{
    use ProductSalesPriceSearch;
    use DataRangScopeTrait;
    // 表名
    public $name = 'product_sales_price';
    // 数据库字段映射
    public $field = array(
        'id',
        // 审核状态，0-待提交审核 1-审核通过 2-审核驳回 3-审核中
        'status',
        // 审核原因
        'reason',
        // 状态，1：正常，2：禁用
        'is_disable',
        // 模板名称
        'name',
        // 店铺（公司）id 关联表 company
        'shop_id',
        // 备注
        'remarks',
        // 开始时间
        'start_time',
        // 结束时间
        'end_time',
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
        $prowerData = $users->getRolesList();
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['p.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->field('p.*, s.shop_name, u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('p')
            ->order('p.id', 'desc')
            ->leftJoin('shop_basics s', 's.id = p.shop_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
    }

    /**
     * 详情
     */
    public function findByInfo($id)
    {
        return $this->field('p.*, s.shop_name')
            ->alias('p')
            ->where('p.id', $id)
            ->leftJoin('shop_basics s', 's.id = p.shop_id')
            ->find();
    }
}
