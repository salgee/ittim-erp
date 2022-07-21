<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\product\model\Parts;
use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class OutboundOrderProducts extends Model
{
    use BaseOptionsTrait, ScopeTrait;

    // 表名
    public $name = 'outbound_order_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 出库单id
        'outbound_order_id',
        // 商品id
        'goods_id',
        // 商品分类
        'category_name',
        // 商品编码
        'goods_code',
        // 商品名称
        'goods_name',
        // 商品名称(英文)
        'goods_name_en',
        // 商品缩率图
        'goods_pic',
        // 出库数量
        'number',
        //类型 1-商品 2-配件
        'type',
        //订单类型;0-非销售订单出库;1-销售订单;2-借卖订单;3-客户订单;4-预售订单;
        'order_type',
        //批次号
        'batch_no',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );
    /**
     * 查询出库单数量集合
     * @param $$o_id 出库订单id集合  $g_id 商品id  $商品批次
     */
    public function obopSumNumber($o_id, $g_id, $mate)
    {
        $mate = array_column($mate, 'batch_no');
        return $this->whereIn('outbound_order_id', $o_id)
            ->whereIn('batch_no', $mate)
            ->where('goods_id', $g_id)
            ->sum('number');
    }

    //查出出库单最后一批次出货数量
    public function obopgoods($o_id, $g_id, $mate)
    {
        $mate = array_column($mate, 'batch_no');
        $goods = $this->field('batch_no')
            ->whereIn('outbound_order_id', $o_id)
            ->whereIn('batch_no', $mate)
            ->where('goods_id', $g_id)
            ->order('batch_no', 'desc')
            ->find();
        if (isset($goods)) {
            $batch_no = $goods->toArray();
            $num = $this->whereIn('outbound_order_id', $o_id)
                ->whereIn('batch_no', $mate)
                ->where('goods_id', $g_id)
                ->where('batch_no', $batch_no['batch_no'])
                ->sum('number');
            return array($batch_no['batch_no'], $num);
        }
    }

    public function product() {
        if ($this->getAttr('type') == 1) {
            return $this->belongsTo(Product::class, 'goods_id');
        }

        if ($this->getAttr('type')  == 2) {
            return $this->belongsTo(Parts::class, 'goods_id');
        }
    }



    public function getCategoryNameAttr() {
        $cate = $this->product->category ?? null;
        if ($cate) {
            return $cate->partent() . '-' . $cate->getAttr('name');
        }
    }


}
