<?php
/*
 * @Version: 1.0
 * @Date: 2021-09-24 17:38:36
 * @LastEditTime: 2021-11-17 16:55:29
 * @Description: 
 */

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;

class OrdersTemp extends Model
{
    // 表名
    public $name = 'orders_temp';
    // 数据库字段映射
    public $field = array(
        'id',
        // 平台订单号
        'order_no',
        // 平台订单号2
        'order_no2',
        // 店铺ID
        'shop_id',
        // 平台名称
        'platform_name',
        // 平台ID
        'platform_id',
        // 是否同步到订单表，1已同步，0未同步
        'is_sync_order',
        // 同步时间
        'sync_at',
        // 订单内容
        'order_info',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    // 设置 json 字段
    protected $json = ['order_info'];

    // 把 json 返回格式转换为数组
    protected $jsonAssoc = true;

    /**
     * 订单未同步
     * @var int
     */
    const SYNC_ORDER_N = 0;

    /**
     * 订单已同步
     * @var int
     */
    const SYNC_ORDER_Y = 1;

    /**
     * 订单同步异常
     * @var int
     */
    const SYNC_ORDER_E = 2;

    /**
     * 订单是否已在表中
     * @param string $orderNo 平台订单号
     * @param int $shopId 店铺ID账号
     * @param string $platformId 平台站点，默认只检查订单号
     * @return bool
     * @date 2021/02/23
     * @author salgee
     */
    public static function hasOrder($orderNo, $shopId = 0, $platformId = 0)
    {
        $where = ['order_no' => $orderNo];
        if(!empty($shopId)) $where['shop_id'] = $shopId;
        if(!empty($platformId)) $where['platform_id'] = $platformId;
        return !!static::where($where)->count();
    }
}
