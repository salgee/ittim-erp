<?php

namespace catchAdmin\finance\model;

use Carbon\Carbon;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\finance\model\search\FreightBillSearch;
use catchAdmin\supply\model\UtilityBills;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class FreightBill extends Model {
    use BaseOptionsTrait, ScopeTrait, FreightBillSearch;
    
    // 表名
    public $name = 'freight_bill';
    // 数据库字段映射
    public $field
        = array(
            'id',
            // 提货单
            'bl_no',
            // 柜号
            'cabinet_no',
            // 装柜日期
            'loading_date',
            // 起运日期
            'shipment_date',
            // 预计到仓日期
            'arrive_date',
            // 付款单状态 0-待申请 1-已申请
            'pay_status',
            // 付款单号
            'payment_no',
            // 类型 domestic-国内陆运 ocean-海运 overseas-国外陆运
            'type',
            //货代公司
            'lforwarder_company',
            //国内路运费用
            'domestic_fee',
            //海运费用
            'ocean_fee',
            //国外路运费用
            'overseas_fee',
            //预计付款时间
            'estimated_pay_time',
            //应付金额
            'bill_amount',
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
    
    protected $append
        = [
            'settlement_cycle',
        ];
    
    public function getSettlementCycleAttr() {
        return Lforwarder::where('name', $this->lforwarder_company)->value('settlement_cycle') ?? '';
    }
    
//    /**
//     * 费用单信息
//     * @return array|mixed
//     */
//    public function getExtraAttr () {
//        $data = [
//            'lforwarder_company' => '',
//            'domestic_fee' => '',
//            'ocean_fee' => '',
//            'overseas_fee' => ''
//        ];
//        $bill = UtilityBills::where(['bl_no' => $this->bl_no, 'cabinet_no' => $this->cabinet_no])
//                            ->find();
//        if (!$bill) {
//            return $data;
//        }
//        $domestic = $bill->domestic_trans;
//        $ocean    = $bill->ocean_shipping;
//        $overseas = $bill->overseas_trans;
//
//
//        switch ($this->type) {
//            case "domestic":
//                $res                        = json_decode($domestic, true);
//                $data['lforwarder_company'] = $res['lforwarder_company'];
//                $data['bill_amount']        = $data['domestic_fee'] = $res['traile_fee']
//                                                                      ??
//                                                                      0 +
//                                                                      $res['detour_fee']
//                                                                      ??
//                                                                      0 + $res['advance_fee']
//                                                                      ??
//                                                                      0 +
//                                                                      $res['declare_fee']
//                                                                      ??
//                                                                      0;
//                $data['estimated_pay_time'] = Carbon::parse($this->loading_date)->addDays
//                ($this->settlement_cycle)->toDateString();
//                break;
//            case "ocean":
//                $res                        = json_decode($ocean, true);
//                $data['lforwarder_company'] = $res['lforwarder_company'];
//                $data['bill_amount']        = $data['ocean_fee'] = $res['amount_rmb'];
//                $data['estimated_pay_time'] = Carbon::parse($this->shipment_date)->addDays
//                ($this->settlement_cycle)->toDateString();
//                break;
//            default:
//                $res                        = json_decode($overseas, true);
//                $data['lforwarder_company'] = $res['lforwarder_company'];
//                $data['bill_amount']        = $data['overseas_fee'] = $res['other_fee'];
//                $data['estimated_pay_time'] = Carbon::parse($this->arrive_date)->addDays
//                ($this->settlement_cycle)->toDateString();
//                break;
//        }
//
//        return $data;
//    }
}