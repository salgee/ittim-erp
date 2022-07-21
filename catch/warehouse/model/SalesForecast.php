<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\search\SalesForecastSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class SalesForecast extends Model
{
    use BaseOptionsTrait, ScopeTrait, SalesForecastSearch;
    // 表名
    public $name = 'sales_forecast';
    // 数据库字段映射
    public $field = array(
        'id',
        // 年份
        'year',
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
    
    protected $append = [
        'created_by_name', 'updated_by_name',
    ];
    
    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }
    
    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }
    
    public function products() {
        return $this->hasMany(SalesForecastProducts::class, 'sales_forecast_id');
    }
    
}