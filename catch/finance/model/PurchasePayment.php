<?php

namespace catchAdmin\finance\model;

use catchAdmin\finance\model\search\PurchasePaymentSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class PurchasePayment extends Model {
    use BaseOptionsTrait, ScopeTrait, PurchasePaymentSearch;
    
    // 表名
    public $name = 'purchase_payment';
    // 数据库字段映射
    public $field
        = array(
            'id',
            // 付款单号
            'payment_no',
            // 付款单来源
            'source',
            // 出运单号
            'trans_code',
            // 合同单号
            'contract_code',
            // 供应商id
            'supply_id',
            // 供应商名称
            'supply_name',
            // 应付款金额
            'order_amount',
            // 预计付款时间
            'estimated_pay_time',
            // 付款抬头
            'pay_title',
            // 付款单状态 0-待付款 1-已付款
            'pay_status',
            // 付款单状态 0-待审核 1-审核通过-1审核拒绝
            'audit_status',
            //审核意见
            'audit_notes',
            // 实际付款金额
            'pay_amount',
            // 实际付款时间
            'pay_time',
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
    
    public function createPaymentNo () {
        $date  = date('Ymd');
        $time  = strtotime($date);
        $count = PurchasePayment::where('created_at', '>', $time)->count();
        $str   = sprintf("%04d", $count + 1);
        return "CGFK" . $date . $str;
    }
}