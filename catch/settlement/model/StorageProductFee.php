<?php

namespace catchAdmin\settlement\model;

use catchAdmin\permissions\model\Department;
use catchAdmin\product\model\Product;
use catchAdmin\settlement\model\search\StorageProductFeeSearch;
use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class StorageProductFee extends Model
{
    use BaseOptionsTrait, ScopeTrait, StorageProductFeeSearch;
    // 表名
    public $name = 'storage_product_fee';
    // 数据库字段映射
    public $field = array(
        'id',
        'storage_fee_id',
        //虚拟仓id
        'virtual_warehouse_id',
        // 商品编码
        'goods_code',
        // 商品名称
        'goods_name',
        // 批次号
        'batch_no',
        // 所属组织
        'department_id',
        // 即时库存
        'storage_number',
        // 入库时间
        'warehousing_time',
        // 在库时长
        'storage_days',
        // 仓储费用
        'fee',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    public $append = ['virtual_warehouse', 'quota' , 'department', 'company'];

    public static function  exportField()
    {
        return [

            [
                'title' => '商品编码',
                'filed' => 'goods_code',
            ],
            [
                'title' => '商品名称',
                'filed' => 'goods_name',
            ],
            [
                'title' => '采购批次',
                'filed' => 'batch_no',
            ],
            [
                'title' => '所在仓库',
                'filed' => 'virtual_warehouse',
            ],
            [
                'title' => '所属公司',
                'filed' => 'company',
            ],
            [
                'title' => '即时库存',
                'filed' => 'storage_number',
            ],
            [
                'title' => '入库时间',
                'filed' => 'warehousing_time',
            ],
            [
                'title' => '在库时长（天）',
                'filed' => 'storage_days',
            ],
            [
                'title' => '仓储费用',
                'filed' => 'fee',
            ],
            [
                'title' => '扣减额度',
                'filed' => 'quota',
            ]
        ];
    }


    public function getVirtualWarehouseAttr() {
        return Warehouses::where('id', $this->getAttr('virtual_warehouse_id'))->value('name');
    }

    public function getQuotaAttr() {
        return $this->fee;
    }

    public function  getDepartmentAttr() {
        return Department::where('id', $this->getAttr('department_id'))->value('department_name') ?? '';
    }

    public function getCompanyAttr() {
        return StorageFee::where('id', $this->storage_fee_id)->value('company_name') ?? '';
    }

}