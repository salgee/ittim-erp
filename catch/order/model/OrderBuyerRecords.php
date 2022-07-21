<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-24 18:55:27
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-11-19 11:07:58
 * @Description:
 */

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;

class OrderBuyerRecords extends Model
{
    // 表名
    public $name = 'order_buyer_records';
    // 数据库字段映射
    public $field = array(
        'id',
        // 订单来源类型 0-正常订单 1-补货订单（order_record_id  关联 售后记录ID）
        'type',
        // 是否启用 1-启用 2-禁用 3-修改地址已提交未审核
        'is_disable',
        // 售后表id
        'after_sale_id',
        // 订单表ID
        'order_record_id',
        // 买家姓名
        'address_name',
        // 买家电话
        'address_phone',
        // 买家的电子邮件
        'address_email',
        // 买家的邮编
        'address_postalcode',
        // 买家的国家的代码
        'address_country',
        // 买家的国家
        'address_country_name',
        // 买家的州/省
        'address_stateorprovince',
        // 买家的城市
        'address_cityname',
        // 买家的街道1
        'address_street1',
        // 买家的街道2
        'address_street2',
        // 买家的街道3
        'address_street3',
        // 买家地址验证UPS后返回如下的收货地址信息
        // 收货的区域
        'ship_region',
        // 收货的邮编
        'ship_postalcode',
        // 收货的国家代码
        'ship_country',
        // 收货的州/省
        'ship_stateorprovince',
        // 收货的城市
        'ship_cityname',
        // 收货的街道1
        'ship_street1',
        // 收货的街道2
        'ship_street2',
        // 收货的街道3
        'ship_street3',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 修改人
        'updated_id',
        // 软删除
        'deleted_at',
    );
}
