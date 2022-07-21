<?php

namespace catchAdmin\supply\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\supply\model\search\PurchaseInvoiceSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class PurchaseInvoice extends Model
{
    use BaseOptionsTrait, ScopeTrait, PurchaseInvoiceSearch;
    // 表名
    public $name = 'purchase_invoice';
    // 数据库字段映射
    public $field = array(
        'id',
        // 采购单号
        'purchase_code',
        // 发票号
        'invoice_no',
        // 发票日期
        'invoice_date',
        // 付款单位
        'payer',
        // 发票税率
        'rate',
        // 税额
        'tax_amount',
        // 未付金额
        'unpaid_amount',
        // 供应商
        'supply',
        // 备注
        'notes',
        // 附件
        'attachment',
        //报关单号
        'declaration_no',
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

    protected $append = ['created_by_name', 'updated_by_name', 'invoice_amount'];

    public static function  exportField() {
        return [
            [
                'title' => '采购单号',
                'filed' => 'purchase_code',
            ],
            [
                'title' => '发票号',
                'filed' => 'invoice_no',
            ],
            [
                'title' => '发票日期',
                'filed' => 'invoice_date',
            ],
            [
                'title' => '付款单位',
                'filed' => 'payer',
            ],
            [
                'title' => '发票税率',
                'filed' => 'rate',
            ],
            [
                'title' => '发票税额',
                'filed' => 'tax_amount',
            ],
            [
                'title' => '未付金额',
                'filed' => 'unpaid_amount',
            ],
            [
                'title' => '供应商',
                'filed' => 'supply',
            ],
            [
                'title' => '备注',
                'filed' => 'notes',
            ]
        ];
    }


    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getInvoiceAmountAttr() {
        $purchaseCode = explode(",", $this->getAttr('purchase_code'));
        return PurchaseOrders::whereIn('code', $purchaseCode)->sum('amount');
    }
}