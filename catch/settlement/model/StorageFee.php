<?php

namespace catchAdmin\settlement\model;

use catchAdmin\basics\model\Company;
use catchAdmin\permissions\model\Department;
use catchAdmin\settlement\model\search\StorageFeeSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class StorageFee extends Model
{
    use BaseOptionsTrait, ScopeTrait, StorageFeeSearch;
    // 表名
    public $name = 'storage_fee';
    // 数据库字段映射
    public $field = array(
        'id',
        // 客户id
        'company_id',
        // 客户名称
        'company_name',
        // 客户编码
        'company_code',
        // 客户类型，1：代仓储，0：自营
        'company_type',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    public $append = ['depart_name', 'quota', 'company_type_text'];


    public static function  exportField()
    {
        return [

            [
                'title' => '客户名称',
                'filed' => 'company_name',
            ],
            [
                'title' => '客户编码',
                'filed' => 'company_code',
            ],
            [
                'title' => '客户类型',
                'filed' => 'company_type_text',
            ],
            [
                'title' => '所属组织',
                'filed' => 'depart_name',
            ],
            [
                'title' => '仓储费用',
                'filed' => 'storage_fee',
            ],
            [
                'title' => '扣减额度',
                'filed' => 'quota',
            ],
            [
                'title' => '发生时间',
                'filed' => 'created_at'
            ]
        ];
    }

    public function getDepartNameAttr()
    {
        return Department::where('id', $this->department_id)->value('department_name') ?? '';
    }

    public function getQuotaAttr()
    {
        return $this->storage_fee;
    }

    public function getCompanyTypeTextAttr()
    {
        //1：代仓储，0：自营， 2-代运营
        $company = Company::find($this->company_id);

        switch ($company->getAttr('type')) {
            case 0:
                return "自营";
                break;
            case 1:
                return "代仓储";
                break;
            case 2:
                return "代运营";
                break;
        }
    }
}
