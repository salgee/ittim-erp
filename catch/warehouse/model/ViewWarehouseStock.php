<?php

namespace catchAdmin\warehouse\model;

use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use think\facade\Db;

class ViewWarehouseStock extends Model
{
    use BaseOptionsTrait, ScopeTrait;

    // 表名
    public $name = 'view_warehouse_stock';
    // 数据库字段映射
    public $field
        = array(
            'id',
            //类型 1-商品 2-配件
            'goods_type',
            // 商品编码
            'goods_code',
            // 实体仓id
            'entity_warehouse_id',
            // 虚拟仓id
            'virtual_warehouse_id',
            // 实体仓名称
            'entity_warehouse',
            // 虚拟仓名称
            'virtual_warehouse',
            // 英文名
            'goods_name_o_en',
            // 中文名
            'goods_name_o_ch',
            // 配件中文名
            'parts_name_ch',
            // 库存数量
            'number',
            // 在途库存
            'trans_stock',
            // 调拨占用库存
            'allot_order_lock_stock',
            // FBA调拨占用库存
            'fba_order_lock_stock',
            // 手工出库单占用库存
            'outbound_order_lock_stock'
        );

    public static function exportField()
    {
        return [

            [
                'title' => '商品编码',
                'filed' => 'goods_code',
            ],
            [
                'title' => '中文名称',
                'filed' => 'goods_name_ch',
            ],
            [
                'title' => '英文名称',
                'filed' => 'goods_name_en',
            ],
            [
                'title' => '所属虚拟仓',
                'filed' => 'virtual_warehouse',
            ],
            [
                'title' => '所属实体仓',
                'filed' => 'entity_warehouse',
            ],
            [
                'title' => '即时库存',
                'filed' => 'number',
            ],
            [
                'title' => '锁定库存',
                'filed' => 'lock_stock',
            ],
            [
                'title' => '在途库存',
                'filed' => 'trans_stock',
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function getAvailableStockAttr()
    {
        return $this->number - $this->lock_stock;
    }

    /**
     * 锁定库存
     * @return mixed
     */
    public function getLockStockAttr()
    {
        return $this->allot_order_lock_stock + $this->fba_order_lock_stock + $this->outbound_order_lock_stock;
    }

    public function getGoodsNameChAttr()
    {
        if ($this->goods_type == 1) {
            return $this->goods_name_o_ch ?: '';
        } else {
            return $this->parts_name_ch ?: '';
        }
    }

    public function getGoodsNameEnAttr()
    {
        if ($this->goods_type == 1) {
            return $this->goods_name_o_en ?: '';
        }
        return '';
    }

}
