<?php
/*
 * @Author: your name
 * @Date: 2021-02-04 17:16:16
 * @LastEditTime: 2021-07-08 19:03:16
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\ShopWarehouse.php
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\ShopWarehouseSearch;
use catcher\base\CatchModel as Model;

class ShopWarehouse extends Model
{
    use ShopWarehouseSearch;

    // 表名
    public $name = 'shop_warehouse';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联店铺id  关联店铺表 shop_basics
        'shop_id',
        // 关联仓库id  关联仓库表 warehouse
        'warehouse_id',
        // 关联虚拟仓库id  关联仓库表 warehouse
        'warehouse_fictitious_id',
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
     * get list
     *
     * @time 2021/2/3
     * @param $params
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        return $this->catchSearch()
            ->order('id', 'desc')
            ->paginate();
    }

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

    //查找对应商品仓库
    public function getOrderWarehouse($id)
    {

        return $this->field('warehouse_id,warehouse_fictitious_id')
            ->where('shop_id', '=', $id)
            ->select();
    }
    /**
     * 查询店铺是否关联店铺
     * @param $shopId 店铺id
     * @param $viId 虚拟仓库id
     */
    public function getShopWarehouse($shopId, $viId)
    {
        if (!$this->where(['shop_id' =>  $shopId, 'warehouse_fictitious_id' => $viId])->value('id')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 通过实体仓查询虚拟仓库ID
     */
    public function getShopWarehouseId($shopId, $enId)
    {
        if (!$id = $this->where(['shop_id' =>  $shopId, 'warehouse_id' => $enId])->value('warehouse_fictitious_id')) {
            return '';
        } else {
            return $id;
        }
    }
}
