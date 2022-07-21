<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-08 15:01:31
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-24 14:56:37
 * @Description:
 */

namespace catchAdmin\product\model;

// use catchAdmin\product\model\search\CategorySearch;
use catchAdmin\product\model\search\CategorySearch;
use catcher\base\CatchModel as Model;

class Category extends Model
{
    use CategorySearch;
    // 表名
    public $name = 'category';
    // 数据库字段映射
    public $field = array(
        'id',
        // 父分类id 父级0
        'parent_id',
        // 父级名称
        'parent_name',
        // 分类名
        'name',
        // 分类编码
        'code',
        // 备注说明
        'remark',
        // 国内(HS)
        'ZH_HS',
        // 国外(HS)
        'EN_HS',
        // 国内退税率
        'tax_rebate_rate',
        // 国外关税税率
        'tax_tariff_rate',
        // 关税杂税率
        'mix_tariff_rate',
        // 额外关税率
        'additional_tax_rate',
        // 是否可用，0-是，1-否
        'is_status',
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
     * 一级分类列表
     *
     * @return array
     * @throws DbException
     */
    public function getList() {
        return $this->field('c.id, c.name, c.parent_id, c.code, c.is_status,c.remark, c.ZH_HS, c.EN_HS, c.tax_rebate_rate,
            c.tax_tariff_rate, c.mix_tariff_rate, c.additional_tax_rate,
            c.created_at, c.updated_at,u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('c')
            ->where('c.parent_id', 0)
            ->catchSearch()
            ->catchOrder()
            ->leftJoin('users u', 'u.id = c.creator_id')
            ->leftJoin('users us', 'us.id = c.update_by')
            ->select();
    }

    /**
     * 子列表
     * @throws \think\db\exception\DbException
     * @return \think\Paginator
     */
    public function getChildList($id) :\think\Paginator
    {
        return $this->field('cp.name as parent_name, c.id, c.name, c.parent_id, c.code, c.is_status,
            c.ZH_HS, c.EN_HS, c.tax_rebate_rate, c.tax_tariff_rate, c.remark, c.mix_tariff_rate, c.additional_tax_rate,
            c.created_at, c.updated_at,u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('c')
            ->catchSearch()
            ->where('c.parent_id', $id)
            ->catchOrder()
            ->order('c.id', 'desc')
            ->leftJoin('category cp', 'cp.id = c.parent_id')
            ->leftJoin('users u', 'u.id = c.creator_id')
            ->leftJoin('users us', 'us.id = c.update_by')
            ->paginate();
    }

    /**
     * 分类树状结构
     * ->toTree()
     * @return array
     */
    public function getListTree()
    {
        return $this->catchSearch()
            ->field('c.id,c.name, c.parent_id, c.code, c.ZH_HS, c.EN_HS, c.tax_rebate_rate, c.tax_tariff_rate, c.is_status')
            // ->where('c.is_status', 0)
            ->alias('c')
            ->catchOrder()
            ->select()->toTree();
    }

    public function partent() {

        return $this->where('parent_id', $this->parent_id)->value('parent_name') ?? '';
    }

}