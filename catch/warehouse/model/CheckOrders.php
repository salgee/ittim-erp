<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Category;
use catchAdmin\warehouse\model\search\CheckOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use think\facade\Db;

class CheckOrders extends Model {
    use BaseOptionsTrait, ScopeTrait, CheckOrderSearch;

    // 表名
    public $name = 'check_orders';
    // 数据库字段映射
    public $field
        = array(
            'id',
            // 盘点单号
            'code',
            // 实体仓id
            'entity_warehouse_id',
            // 盘库名称
            'name',
            // 审核状态 0 待盘点 1已盘点
            'status',
            // 库存差异
            'stock_difference',
            // 备注说明
            'notes',
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


    public $append = ['warehouse', 'created_by_name', 'updated_by_name', 'status_text'];

    public static function  exportField()
    {
        return [
            [
                'title' => '盘点单号',
                'filed' => 'code',
            ],
            [
                'title' => '盘库名称',
                'filed' => 'name',
            ],
            [
                'title' => '实体仓',
                'filed' => 'warehouse',
            ],
            [
                'title' => '审核状态',
                'filed' => 'status_text',
            ],
            [
                'title' => '库存差异',
                'filed' => 'stock_difference',
            ],
            [
                'title' => '创建人',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ]
        ];
    }

    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getWarehouseAttr () {
        return Warehouses::where('id', $this->getAttr('entity_warehouse_id'))->value('name') ?? '';
    }

    public function getStockDifferenceAttr() {
        return  CheckOrderProducts::where('check_order_id', $this->getAttr('id'))->sum('stock_difference');
    }


    public function getStatusTextAttr() {
            return $this->status == 1 ? '已盘点' : '待盘点';
    }

    public function createOrderNo () {
        $date  = date('Ymd');
        $time  = strtotime($date);
        $count = CheckOrders::where('created_at', '>', $time)->count();
        $str   = sprintf("%04d", $count + 1);
        return "KCPD" . $date . $str;
    }

    public function products($id, $entityWarehouseId) {
        $res = WarehouseStock::alias('ws')->leftJoin('product p', 'p.code = ws.goods_code')
                             ->where('ws.entity_warehouse_id', $entityWarehouseId)
                             ->where('ws.goods_type',1)
                             ->field('p.id,  p.image_url,p.packing_method, p.category_id, sum(ws.number) as number ,ws.goods_code, ws.goods_type')
                             ->group('ws.goods_code')
                             ->select()->toArray();

        foreach ($res AS &$val) {

            $category = Category::where('id', $val['category_id'])->find();

            $fisrtCategoryName = Category::where('id', $category->parent_id ?? 0)->value('name');
            $secondCategoryName = isset($category) ? $category->getAttr('name') : '';
            $val['category_name'] = $fisrtCategoryName . "-" . $secondCategoryName;
            $val['packing_method'] = $val['packing_method'] == 1 ? '普通商品' : '多箱包装';
            //盘点库存 差异库存 盘库结果
            $product = CheckOrderProducts::where(['check_order_id' => $id, 'goods_id' => $val['id']])
                                ->find();
             //获取盘点单时间库存
            if($product) {
                $val['number'] = $product->stock;
            }

            $val['check_stock'] = $product->check_stock ?? 0;
            $val['stock_difference'] = $product->stock_difference ?? 0;

            $val['check_result'] = '平';
            if ($val['stock_difference'] > 0 ) {
                $val['check_result'] = '盘赢';
            }

            if ($val['stock_difference'] < 0 ) {
                $val['check_result'] = '盘亏';
            }

        }

        return $res;
    }

    public function parts($id, $entityWarehouseId) {
        $res = WarehouseStock::alias('ws')->leftJoin('parts p', 'p.code = ws.goods_code')
                             ->where('ws.entity_warehouse_id', $entityWarehouseId)
                             ->where('ws.goods_type',2)
                             ->field('p.id,  p.image_url, p.category_id, sum(ws.number) as number ,ws.goods_code, ws.goods_type')
                             ->group('ws.goods_code')
                             ->select()->toArray();

        foreach ($res AS &$val) {

            $category = Category::where('id', $val['category_id'])->find();
            $fisrtCategoryName = Category::where('id', $category->parent_id ?? 0)->value('name');
            $val['category_name'] = $fisrtCategoryName . "-" .$category->getAttr('name') ?? '';

            $val['packing_method'] = '';

            //盘点库存 差异库存 盘库结果
            $product = CheckOrderProducts::where(['check_order_id' => $id, 'goods_id' => $val['id']])
                                ->find();

            if($product) {
                $val['number'] = $product->stock;
            }
            $val['check_stock'] = $product->check_stock ?? 0;
            $val['stock_difference'] = $product->stock_difference ?? 0;

            $val['check_result'] = '平';
            if ($val['stock_difference'] > 0 ) {
                $val['check_result'] = '盘赢';
            }

            if ($val['stock_difference'] < 0 ) {
                $val['check_result'] = '盘亏';
            }

        }

        return $res;
    }
}
