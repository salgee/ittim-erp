<?php

namespace catchAdmin\settlement\model;

use catchAdmin\settlement\model\search\ThirdPartLogisticsFeeSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class ThirdpartTransportOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, ThirdPartLogisticsFeeSearch;
    // 表名
    public $name = 'thirdpart_transport_orders';
    // 数据库字段映射
    public $field = array(
        'id',
        // 发货日期
        'send_date',
        // 城市
        'city',
        // 联系地址
        'address',
        // 收货人邮编
        'zipcode',
        // 运输方式
        'shippment',
        // 参考号
        'reference_number',
        // 跟踪号
        'tracking_number',
        // 仓库代码
        'warehouse_code',
        // 订单号
        'order_no',
        // SKU
        'sku',
        // Actual Weight
        'actual_weight',
        // Billed Weight
        'billed_weight',
        // Zone
        'zone',
        // 报价运费求和
        'shipping_price_total',
        // 报价基础运费
        'shipping_price',
        // 出库处理费
        'outbound_price',
        // Fuel surchage/燃油附加费
        'fuel_surchage',
        // Residential Delivery/住宅地址附加费
        'residential_delivery',
        // DAS Comm/偏远 地区附加费-商业
        'das_comm',
        // DAS Extended Comm/超偏远 地区附加费-商业
        'das_extended_comm',
        // DAS resi/偏远 地区附加费-住宅
        'das_resi',
        // DAS  Extended  resi/超偏远 地区附加费-住宅
        'das_extended_resi',
        // AHS报价
        'ahs',
        // 报价金额
        'price',
        // Address Correction/地址修正
        'address_correction',
        // Oversize Charge/超长超尺寸费
        'oversize_charge',
        // Peak - Oversize Charge/高峰期超尺寸附加费
        'peak_oversize_charge',
        // Weekday Delivery/工作日派送
        'weekday_delivery',
        // Direct Signature/签名费
        'direct_signature',
        // Unauthorized OS/不可发
        'unauthorized_os',
        // Peak - Unauth Charge
        'peak_unauth_charge',
        // Courier Pickup Charge/快递取件费
        'ourier_pickup_charge',
        // Print Return Label/打印快递面单费用
        'print_return_label',
        // Return Pickup Fee/退件费
        'return_pickup_fee',
        // NDOC P/U- Auto Comm
        'ndoc',
        // Date Certain
        'date_certain',
        // 退件
        'return_label',
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
}