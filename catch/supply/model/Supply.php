<?php

namespace catchAdmin\supply\model;

use catchAdmin\permissions\model\Users;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\supply\model\search\SupplySearch;

class Supply extends Model {
    use BaseOptionsTrait, ScopeTrait, SupplySearch;
    
    // 表名
    public $name = 'supplies';
    
    public $field
        = array(
            'id',
            // 供应商名称
            'name',
            // 供应商代码
            'code',
            // 结算周期
            'billing_cycles',
            // 采购员
            'buyer',
            // 联系人
            'contacts',
            // 联系人手机
            'contacts_phone',
            // 固定电话
            'phone',
            // 传真
            'fax',
            // 地址
            'address',
            // 邮编
            'zipcode',
            // 预付款比例
            'pay_ratio',
            // 备注说明
            'notes',
            // 营业执照
            'business_license',
            //合同模板
            'contract_template',
            // 审核状态，0-待提交 1-待审核 2-已审核 -1 审核拒绝
            'audit_status',
            //审核意见
            'audit_notes',
            // 合作状态 0-暂停 1-正常
            'cooperation_status',
            // 创建人
            'created_by',
            // 修改人
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        );
    
    protected $append = [
            'created_by_name', 'updated_by_name', 'audit_status_text', 'cooperation_status_text',
        'bank_accounts'
        ];
    
    
    public function supplyBankAccounts()
    {
        return $this->hasMany(SupplyBankAccounts::class, 'supply_id', 'id');
    }
    
    
    public function getBankAccountsAttr() {
        return $this->supplyBankAccounts->all();
    }
    
    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }
    
    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }
    
    public function getAuditStatusTextAttr () {
      
        switch ($this->getAttr('audit_status')) {
            case '-1':
                return '审核驳回';
                break;
            case 0:
                return '待提交';
                break;
            case 1:
                return '待审核';
                break;
            case 2:
                return '审核通过';
                break;
            default:
                return '待提交';
                break;
        }
    }
    
    public function getCooperationStatusTextAttr () {
        switch ($this->getAttr('cooperation_status')) {
            case 0:
                return '终止';
                break;
            case 1:
                return '正常';
                break;
            default:
                return '终止';
                break;
        }
    }
}