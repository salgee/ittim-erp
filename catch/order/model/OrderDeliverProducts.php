<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-15 10:48:21
 * @LastEditTime: 2021-09-28 10:11:23
 * @Description:
 */

namespace catchAdmin\order\model;

use catchAdmin\product\model\Product;
use catcher\base\CatchModel as Model;

class OrderDeliverProducts extends Model
{
    // 表名
    public $name = 'order_deliver_products';
    // 数据库字段映射
    public $field = array(
        'id',
        // 分组商品名称
        'goods_group_name',
        // 分组商品id
        'goods_group_id',
        // 关联发货单订单id
        'order_deliver_id',
        // 关联订单id
        'order_id',
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
        // 商品交易价格(单位)
        'transaction_price_currencyid',
        // 交易价格
        'transaction_price_value',
        // 税额(单位)
        'tax_amount_currencyid',
        // 税额
        'tax_amount_value',
        // 海运费
        'freight_fee',
        // 发货数量
        'number',
        // 退货数量
        'return_num',
        // 关联售后id
        'after_order_id',
        // 售后金额
        'after_amount',
        // 类型 1-普通商品 2-配件
        'type',
        // 批次号
        'batch_no',
        // 发货仓库（虚拟仓库）
        'warehouses_id',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    public function product()
    {
        return $this->belongsTo(Product::class, 'goods_id', 'id');
    }

    public function orderDeliver()
    {
        return $this->belongsTo(OrderDeliver::class, 'order_deliver_id', 'id');
    }
}
