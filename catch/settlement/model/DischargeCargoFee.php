<?php

namespace catchAdmin\settlement\model;

use catchAdmin\basics\model\Company;
use catchAdmin\permissions\model\Users;
use catchAdmin\settlement\model\search\DischargeCargoFeeSearch;
use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class DischargeCargoFee extends Model {
    use BaseOptionsTrait, ScopeTrait, DischargeCargoFeeSearch;

    // 表名
    public $name = 'discharge_cargo_fee';
    // 数据库字段映射
    public $field
        = array(
            'id',
            // 客户id
            'company_id',
            // 账单月份
            'bill_time',
            // 仓库id
            'warehouse_id',
            // 卸货商品数量
            'discharge_number',
            // 卸货单价
            'discharge_fee',
            // 入库验收商品数量
            'check_number',
            // 入库验收单价
            'check_fee',
            // 标准订单出库服务商品数量
            'outbound_service_number',
            // 标准订单出库服务单价
            'outbound_service_fee',
            // 拒收/退货入库商品数量
            'return_number',
            // 拒收/退货入库单价
            'return_fee',
            // 打托商品数量
            'pallet_number',
            // 打托单价
            'pallet_fee',
            // 贴标商品数量
            'label_number',
            // 贴标单价
            'label_fee',
            // 确认状态 1-已确认 0-未确认
            'status',
            // 创建人
            'created_by',
            // 修改人
            'updated_by',
            // 创建时间
            'created_at',
            // 修改时间
            'updated_at',
            // 删除时间
            'deleted_at',
        );

    public $append
        = [
            'warehouse', 'company', 'discharge_total', 'check_total', 'outbound_service_total',
            'return_total',
            'pallet_total', 'label_total', 'quota', 'pay_amount', 'created_by_name' , 'updated_by_name'
        ];

    public static function  exportField()
    {
        return [

            [
                'title' => '客户',
                'filed' => 'company',
            ],
            [
                'title' => '卸货费',
                'filed' => 'discharge_total',
            ],
            [
                'title' => '入库验收费',
                'filed' => 'check_total',
            ],
            [
                'title' => '标准订单出库服务费',
                'filed' => 'outbound_service_total',
            ],
            [
                'title' => '拒收/退货入库服务费',
                'filed' => 'return_total',
            ],
            [
                'title' => '打托费',
                'filed' => 'pallet_total',
            ],
            [
                'title' => '贴标签',
                'filed' => 'label_total',
            ],
            [
                'title' => '应扣额度',
                'filed' => 'quota',
            ],
            [
                'title' => '实际扣减额度',
                'filed' => 'pay_amount',
            ]
        ];
    }

    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }
    public function getWarehouseAttr() {
        return Warehouses::where('id', $this->getAttr('warehouse_id'))->value('name') ?? '';
    }

    public function getCompanyAttr() {
        return Company::where('id', $this->getAttr('company_id'))->value('name') ?? '';
    }
    public function getDischargeTotalAttr () {
        return $this->getAttr('discharge_fee') * $this->getAttr('discharge_number');
    }

    public function getCheckTotalAttr () {
        return $this->getAttr('check_fee') * $this->getAttr('check_number');
    }

    public function getOutboundServiceTotalAttr () {
        return $this->getAttr('outbound_service_fee') * $this->getAttr('outbound_service_number');
    }

    public function getReturnTotalAttr () {
        return $this->getAttr('return_fee') * $this->getAttr('return_number');
    }

    public function getPalletTotalAttr () {
        return $this->getAttr('pallet_fee') * $this->getAttr('pallet_number');
    }

    public function getLabelTotalAttr () {
        return $this->getAttr('label_fee') * $this->getAttr('label_number');
    }

    public function getQuotaAttr() {
        return $this->discharge_total + $this->check_total + $this->outbound_service_total +
               $this->return_total + $this->pallet_total + $this->label_total;
    }

    public function getPayAmountAttr() {
        return $this->quota;
    }
}