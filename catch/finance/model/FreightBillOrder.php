<?php

namespace catchAdmin\finance\model;

use Carbon\Carbon;
use catchAdmin\finance\model\search\FreightBillOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class FreightBillOrder extends Model {
    use BaseOptionsTrait, ScopeTrait, FreightBillOrderSearch;
    
    // 表名
    public $name = 'freight_bill_order';
    // 数据库字段映射
    public $field
        = array(
            'id',
            // 付款单号
            'payment_no',
            //货代公司
            'lforwarder_company',
            //应付金额
            'order_amount',
            //付款状态 1-已付款 0-待付款
            'pay_status',
            //实际付款金额
            'pay_amount',
            //实际付款时间
            'pay_time',
            'audit_status',
            'audit_notes',
            // 创建人ID
            'creator_id',
            // 创建时间
            'created_at',
        );
    
    
    public function createPaymentNo () {
        $date  = date('Ymd');
        $time  = strtotime($date);
        $count = FreightBillOrder::where('created_at', '>', $time)->count();
        $str   = sprintf("%04d", $count + 1);
        return "HDFK" . $date . $str;
    }
    
    public function getOrderAmountAttr() {
        $amount = 0;
       
        $bills =  FreightBill::where('payment_no', $this->payment_no)->select();
        foreach ($bills AS $bill) {
            $amount += $bill->domestic_fee + $bill->ocean_fee + $bill->overseas_fee;
        }
        return $amount;
    }
}