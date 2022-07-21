<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\search\WarehouseSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class Warehouses extends Model
{
    use BaseOptionsTrait, ScopeTrait, WarehouseSearch;

    // 表名
    public $name = 'warehouses';
    // 数据库字段映射
    public $field = array(
        'id',
        // 仓库名称
        'name',
        // 仓库代码
        'code',
        // 状态，1：正常，0：禁用
        'is_active',
        // 仓库类型  1-实体仓 2-虚拟仓 3-残品仓 4-FBA仓
        'type',
        // 上级仓库
        'parent_id',
        // 所属组织
        'department_id',
        //所属客户
        'company_id',
        // 州/省
        'state',
        // 城市
        'city',
        // 街道
        'street',
        // 邮编
        'zipcode',
        // 备注
        'notes',
        //是否第三方库 1-是 0-否
        'is_third_part',
        // usps 参数
        'usps_json',
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

    // 设置 json 字段
    protected $json = ['usps_json'];

    // 把 json 返回格式转换为数组
    protected $jsonAssoc = true;

    protected $append
    = [
        'created_by_name', 'updated_by_name', 'parent_warehouse', 'is_active_text', 'type_text',
    ];

    public function getCreatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getUpdatedAtAttr()
    {
        if ($this->getAttr('updated_by') == 0) {
            return '';
        }

        return $this->getData('updated_at');
    }
    public function getParentWarehouseAttr()
    {
        return $this->where('id', $this->getAttr('parent_id'))->value('name') ?? '';
    }

    public function getIsActiveTextAttr()
    {
        return $this->getAttr('is_active') == 1 ? '启用' : '禁用';
    }

    public function getTypeTextAttr()
    {
        //  1-实体仓 2-虚拟仓 3-残品仓 4-FBA仓
        switch ($this->getAttr('type')) {
            case 1:
                return '实体仓';
                break;
            case 2:
                return '虚拟仓';
                break;
            case 3:
                return '残品仓';
                break;
            case 4:
                return 'FBA仓';
                break;
            default:
                return '';
        }
    }

    public function warehouseGoods($k, $kk)
    {
        return $this->field('zipcode,id')
            ->where('is_active', 1)
            ->where('id', $k)
            ->where('parent_id', $kk)
            ->where('type', 2)
            ->find();
    }

    public function childWarehouse()
    {
        return $this->where('parent_id', $this->id)->select();
    }
}
