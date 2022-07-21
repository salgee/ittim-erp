<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Parts;
use catchAdmin\product\model\Product;
use catchAdmin\supply\model\SubOrders;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use think\facade\Db;

class WarehouseStock extends Model
{
    use BaseOptionsTrait, ScopeTrait;

    // 表名
    public $name = 'warehouse_stock';
    // 数据库字段映射
    public    $field
    = array(
        'id',
        // 商品编码
        'goods_code',
        // 实体仓id
        'entity_warehouse_id',
        // 虚拟仓id
        'virtual_warehouse_id',
        // 库存数量
        'number',
        //批次号
        'batch_no',
        //类型 1-商品 2-配件
        'goods_type',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );
    protected $append
    = [
        'goods_name_ch', 'goods_name_en', 'entity_warehouse', 'virtual_warehouse', 'available_stock', 'trans_stock', 'lock_stock'
    ];


    public static function  exportField()
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
     * 计算在途库存
     *
     * @return void
     */
    public function getTransStockAttr()
    {
        // return WarehouseOrderProducts::alias('wop')
        //     ->leftJoin('warehouse_orders wo', 'wo.id = wop.warehouse_order_id')
        //     ->where([
        //         'wop.goods_code' => $this->goods_code,
        //         'wo.virtual_warehouse_id' => $this->virtual_warehouse_id,
        //         'wo.warehousing_status' => 0,
        //         'wo.deleted_at' => 0,
        //     ])
        //     ->sum('number') ?? 0;

        return SubOrders::alias('so')
            ->leftJoin('transhipment_order_products top', 'so.`trans_goods_id` = top.id')
            ->leftJoin('purchase_order_products po', 'top.purchase_product_id=po.id')
            ->where([
                'po.goods_code' => $this->goods_code,
                'so.virtual_warehouse_id' => $this->virtual_warehouse_id,
                'so.warehouse_order_id' => 0,
            ])
            ->sum('so.number') ?? 0;
    }


    /**
     * 锁定库存
     *
     * @return void
     */
    public function getLockStockAttr()
    {

        $aop = $this->allotOrderLockStock();

        $faop = $this->fabAllotOrderLockStock();

        $op = $this->outBoundOrderLockStock();

        return  $aop + $faop + $op;
    }



    public function getAvailableStockAttr()
    {
        return $this->number - $this->lock_stock;
    }


    /**
     * 调拨占用库存
     *
     * @return void
     */
    public function allotOrderLockStock()
    {

        return  AllotOrderProducts::alias('aop')
            ->leftJoin('allot_orders ao', 'ao.id = aop.allot_order_id')
            ->whereIn('ao.audit_status', [0, 1, 2, -1, -2])
            ->where([
                'ao.transfer_out_warehouse_id' =>
                $this->virtual_warehouse_id,
                'aop.goods_code' => $this->goods_code,
                'aop.type' => $this->getAttr('goods_type'),
                'ao.deleted_at' => 0
            ])
            ->sum('number');
    }

    /**
     * FBA调拨占用库存
     *
     * @return void
     */
    public function fabAllotOrderLockStock()
    {
        return  FbaAllotOrderProducts::alias('faop')
            ->leftJoin('fba_allot_orders fao', 'fao.id = faop.fba_allot_order_id')
            ->whereIn('fao.audit_status', [0, 1, -1])
            ->where([
                'fao.virtual_warehouse_id' =>
                $this->virtual_warehouse_id,
                'faop.goods_code' => $this->goods_code,
                'faop.type' => $this->getAttr('goods_type'),
                'fao.deleted_at' => 0

            ])
            ->sum('number');
    }

    /**
     * 手工出库单占用库存
     *
     * @return void
     */
    public function outBoundOrderLockStock()
    {
        return  OutboundOrderProducts::alias('op')
            ->leftJoin('outbound_orders o', 'o.id = op.outbound_order_id')
            ->whereIn('o.audit_status', [0, 1])
            ->where([
                'o.virtual_warehouse_id' => $this->virtual_warehouse_id,
                'o.source' => 'manual',
                'op.goods_code' => $this->goods_code,
                'op.type' => $this->getAttr('goods_type'),
                'o.deleted_at' => 0
            ])
            ->sum('number');
    }

    public function getGoodsNameChAttr()
    {
        if ($this->getAttr('goods_type') == 1) {
            return Product::where('code', $this->getAttr('goods_code'))->value('name_ch') ?? '';
        } else {
            return Parts::where('code', $this->getAttr('goods_code'))->value('name_ch') ?? '';
        }
    }

    public function getGoodsNameEnAttr()
    {
        if ($this->getAttr('goods_type') == 1) {
            return Product::where('code', $this->getAttr('goods_code'))->value('name_en') ?? '';
        }
        return '';
    }

    public function getEntityWarehouseAttr()
    {
        return Warehouses::where('id', $this->getAttr('entity_warehouse_id'))->value('name') ?? '';
    }

    public function getVirtualWarehouseAttr()
    {
        return Warehouses::where('id', $this->getAttr('virtual_warehouse_id'))->value('name') ?? '';
    }

    /**
     * 产看商品库存数量
     * $v[0] 实体仓库id  $v[1] 虚拟仓库id  $v[2] 商品编码  $v[数量] 商品数量
     */
    public function warehouseNumber($v)
    {
        $num = $this->where('entity_warehouse_id', '=', $v[0])
            ->where('virtual_warehouse_id', '=', $v[1])
            ->where('goods_code', '=', $v[2])
            ->sum('number');
        if ($num >= $v[3]) {
            return $this->warehouseGoodsNumber($v, 1);
        } else {
            return false;
        }
    }

    /**
     * 仓库具体商品查询
     * type==1 不固定批次  type==2 查询大于某个批次的数据
     */
    public function warehouseGoodsNumber($v, $type)
    {
        if ($type == 2) {
            $data = $this->field('id,virtual_warehouse_id,number,batch_no,created_at')
                ->where('entity_warehouse_id', '=', $v[0])
                ->where('virtual_warehouse_id', '=', $v[1])
                ->where('goods_code', '=', $v[2])
                ->where('batch_no', '>=', $v[3])
                ->select();
        } else {
            $data = $this->field('id,virtual_warehouse_id,number,batch_no')
                ->where('entity_warehouse_id', '=', $v[0])
                ->where('virtual_warehouse_id', '=', $v[1])
                ->where('goods_code', '=', $v[2])
                ->select();
        }
        return $data;
    }

    public function obopGoods($v)
    {

        $datas = $this->field('batch_no,number')
            ->where('entity_warehouse_id', '=', $v[0])
            ->where('virtual_warehouse_id', '=', $v[1])
            ->where('goods_code', '=', $v[2])
            ->where('number', '>', 0)
            ->order('batch_no', 'asc')
            ->find();
        return array($datas['batch_no'], 0);
    }

    /**
     * 增加库存
     *
     * @param $entityWarehouseId  实体仓id
     * @param $virtualWarehouseId 虚拟仓id
     * @param $goodsCode  商品编码
     * @param $batchNo 批次号
     * @param $number 数量
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function increaseStock(
        $entityWarehouseId,
        $virtualWarehouseId,
        $goodsCode,
        $batchNo,
        $number,
        $goodsType,
        $changeModel = '',
        $orderId = '',
        $orderIdSale = ''
    ) {
        $stock = WarehouseStock::where('entity_warehouse_id', $entityWarehouseId)
            ->where('virtual_warehouse_id', $virtualWarehouseId)
            ->where('goods_code', $goodsCode)
            ->where('batch_no', $batchNo)
            ->where('goods_type', $goodsType)
            ->find();
        if (!$stock) {
            $before_number = 0;
            $after_number = $number;
            //记录不存在新建
            WarehouseStock::create([
                'entity_warehouse_id' => $entityWarehouseId,
                'virtual_warehouse_id' => $virtualWarehouseId,
                'goods_code' => $goodsCode,
                'number' => $number,
                'batch_no' => $batchNo,
                'goods_type' => $goodsType
            ]);
        } else {
            //记录存在则更新
            $before_number = $stock->number;
            $stock->number += $number;
            $stock->save();
            $after_number =  $stock->number;
        }

        StockChangeLog::create([
            'change_model' => $changeModel,
            'order_id' => $orderId,
            'add_number' => $number,
            'before_number' => $before_number,
            'after_number' => $after_number,
            'goods_code' => $goodsCode,
            'batch_no' => $batchNo,
            'warehouse_id' => $virtualWarehouseId,
            'order_id_sale' => $orderIdSale
        ]);
    }

    public function reduceStock(
        $entityWarehouseId,
        $virtualWarehouseId,
        $goodsCode,
        $batchNo,
        $number,
        $goodsType,
        $changeModel = '',
        $orderId = '',
        $orderIdSale = ''
    ) {
        $stock = WarehouseStock::where('entity_warehouse_id', $entityWarehouseId)
            ->where('virtual_warehouse_id', $virtualWarehouseId)
            ->where('goods_code', $goodsCode)
            ->where('batch_no', $batchNo)
            ->where('goods_type', $goodsType)
            ->find();
        if ($stock) {
            //记录存在则更新
            $before_number = $stock->number;
            $stock->number -= $number;
            $stock->save();
            $after_number = $stock->number;
            StockChangeLog::create([
                'change_model' => $changeModel,
                'order_id' => $orderId,
                'reduce_number' => $number,
                'before_number' => $before_number,
                'after_number' => $after_number,
                'goods_code' => $goodsCode,
                'batch_no' => $batchNo,
                'warehouse_id' => $virtualWarehouseId,
                'order_id_sale' => $orderIdSale
            ]);
        }
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'goods_code', 'code');
    }
}
