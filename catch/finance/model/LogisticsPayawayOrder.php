<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-22 15:55:28
 * @LastEditTime: 2021-04-29 17:25:42
 * @Description: 
 */

namespace catchAdmin\finance\model;

use catcher\base\CatchModel as Model;
use catchAdmin\finance\model\search\LogisticsPayawayOrderSearch;
use catcher\Code;

class LogisticsPayawayOrder extends Model
{
    use LogisticsPayawayOrderSearch;
    // 表名
    public $name = 'logistics_payaway_order';
    // 数据库字段映射
    public $field = array(
        'id',
        // 申请付款状态 0-待审核 1-审核通过 2-审核拒绝
        'status',
        // 申请备注
        'remarks',
        // 付款单状态 0-待付款 1-已付款
        'payaway_status',
        // 应付款金额
        'payaway_amount',
        // 实际付款金额
        'payaway_amount_real',
        // 实际付款时间
        'pay_time',
        // 物流付款单号
        'payaway_order_no',
        // 所属物流公司
        'logistics_company',
        // 所属物流公司ID
        'logistics_id',
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
     * 平台类型
     */
    public static $orderStatus = array(
        Code::ORDER_EXAMNE_WAIT => '待审核',
        Code::ORDER_EXAMNE_PASS => '审核通过',
        Code::ORDER_EXAMNE_REFUSE => '审核驳回'
    );
}