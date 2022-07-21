<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-12 12:30:58
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-26 09:57:36
 * @Description:
 */

namespace catchAdmin\product\model;

use catchAdmin\basics\model\Shop;
use catchAdmin\permissions\model\Users;
use catcher\base\CatchModel as Model;
use catchAdmin\product\model\search\PartsSearch;
use catchAdmin\supply\model\Supply;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\product\model\Product;
use think\facade\Cache;
use catcher\Code;


class ViewParts extends Model
{
    use DataRangScopeTrait;
    use PartsSearch;
    // 表名
    public $name = 'parts';
    // 数据库字段映射
    public $field = array(
        'id',
        // 配件主图
        'image_url',
        // 二级分类id 关联 category
        'category_id',
        // 状态 2-禁用 1-启用
        'is_status',
        // 编码
        'code',
        // 中文名称
        'name_ch',
        // 流向 1-国内 2-国外
        'flow_to',
        // 采购员
        'purchase_name',
        // 采购员id 关联 users
        'purchase_id',
        // 供应商id
        'supplier_id',
        // 长cm
        'length',
        // 宽cm
        'width',
        // 高cm
        'height',
        // 美制长 英寸
        'length_AS',
        // 美制宽 英寸
        'width_AS',
        // 美制高 英寸
        'height_AS',
        // 毛重美制 lbs
        'weight_gross_AS',
        // 体积
        'volume',
        // 重
        'weight',
        // 外箱长cm
        'length_outside',
        // 外箱宽cm
        'width_outside',
        // 外箱高cm
        'height_outside',
        // 外箱体积
        'volume_outside',
        // 箱率
        'box_rate',
        // 商品id， 多个使用 ，号隔开
        'product_id',
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
     *
     * @return \think\Paginator
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的配件
            if ($prowerData['shop_ids']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $where = [
                    'p.company_id' => ['in', $company_ids]
                ];
            } else {
                // 判断是运营岗，只可以查看所有的内部客户的商品
                if ($prowerData['is_operation']) {
                    $where = ['cp.user_type' => 0];
                }
            }
        }
        $list = $this
            ->dataRange()
            ->catchSearch()
            ->field('c.id, c.purchase_name,c.supplier_id, c.box_rate, c.purchase_id, c.image_url, c.code, c.name_ch, c.is_status,
            c.updated_at, c.created_at, su.name as supplier_name, c.flow_to, 
            cg.name as category_names, cg.parent_name, c.created_at, c.updated_at,u.username as creator_name,  IFNULL(us.username, "-") as update_name')
            ->alias('c')
            // ->whereOr($where)
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->catchSearch();
                }
            })
            ->order('c.id', 'desc')
            // ->whereRaw("FIND_IN_SET(p.id, c.product_id)")
            ->leftJoin('product p', 'FIND_IN_SET(p.id, c.product_id)')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->leftJoin('supplies su', 'su.id = c.supplier_id')
            ->leftJoin('category cg', 'cg.id = c.category_id')
            ->leftJoin('users us', 'us.id = c.update_by')
            ->leftJoin('users u', 'u.id = c.creator_id')
            ->group('c.id')
            ->paginate();
        //     ->fetchSql()->find(1);
        // var_dump($list);
        // exit;
        return $list;
    }
}
