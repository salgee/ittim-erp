<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-09 14:55:42
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-18 16:17:31
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;


class ProductInfo extends Model
{
    // 表名
    public $name = 'product_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 产品id 关联 product
        'product_id',
        // 长cm
        'length',
        // 宽cm
        'width',
        // 高cm
        'height',
        // 体积
        'volume',
        // 美制长cm
        'length_AS',
        // 美制宽cm
        'width_AS',
        // 美制高cm
        'height_AS',
        // （美）体积
        'volume_AS',
        // （美）体积重 体积(美制)/系数
        'volume_weight_AS',
        // oversize(特大) (美制)长+（宽+高）*2
        'oversize',
        // 运输包装体积
        'transport_volume',
        // 运输包装长
        'transport_length',
        // 运输包装宽
        'transport_width',
        // 运输包装高
        'transport_height',
        // 毛重 kg
        'weight_gross',
        // 美制毛重 kg
        'weight_gross_AS',
        // 净重 kg
        'weight',
        // 美制净重 kg
        'weight_AS',
        // 40HQ装箱量
        'hq_size',
        // 箱率
        'box_rate',
        // 计量单位
        'unit',
        // 运输外箱体积
        'outside_transport_volume',
        // 客户商品 包装类型 0-无包装 1-自带包装  2-特殊包装
        'packing_type',
        // 客户商品 是否需要序列号 1-是 0-否
        'is_serial_number',
        // 客户商品 是否带电 1-是 0-否
        'is_electric',
        // 客户商品 打包设置 1-独立打包 0-不设置
        'pack_set',
        // 修改人
        'update_by',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    //获取单商品的详细信息
    public function getPinfo($id, $field)
    {
        return $this->field($field)
            ->where('product_id', $id)
            ->find()->toArray();

    }
}
