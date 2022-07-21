<?php
/*
 * @Author: your name
 * @Date: 2021-02-04 11:22:15
 * @LastEditTime: 2021-03-10 14:38:31
 * @LastEditors:
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\model\Sender.php
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\SenderSearch;
use catcher\base\CatchModel as Model;

class Sender extends Model
{
    use SenderSearch;
    // 表名
    public $name = 'sender';
    // 数据库字段映射
    public $field = array(
        'id',
        // 关联店铺id  关联店铺表 shop_basics
        'shop_id',
        // 仓库名称
        'warehouse_name',
        // 关联仓库id  关联仓库表 warehouse
        'warehouse_id',
        // 国家代码默认中国
        'country_code',
        // 寄件人公司
        'company',
        // 寄件人姓名
        'name',
        // 电话
        'phone',
        // 手机
        'mobile',
        // 街道
        'street',
        // 城市
        'city',
        // 州/省代码
        'city_code',
        // 邮编
        'post_code',
        // 是否为默认配置，1是，0否
        'is_default',
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
     * @throws \think\db\exception\DbException
     * @return \think\Paginator
     */
    public function getList()
    {
        return $this->field('id, shop_id, country_code, company, name, phone, warehouse_id,warehouse_name,
            mobile, street, city, city_code, post_code, is_default, created_at, updated_at')
            ->catchSearch()
            ->order('id', 'desc')
            ->paginate();
    }
}