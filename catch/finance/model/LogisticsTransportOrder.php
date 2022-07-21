<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-22 15:47:03
 * @LastEditTime: 2021-08-13 14:51:37
 * @Description:
 */

namespace catchAdmin\finance\model;

use catcher\base\CatchModel as Model;
use catchAdmin\finance\model\search\LogisticsTransportOrderSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;

class LogisticsTransportOrder extends Model
{
    use LogisticsTransportOrderSearch, DataRangScopeTrait;
    // 表名
    public $name = 'logistics_transport_order';
    // 数据库字段映射
    public $field = array(
        'id',
        // 订单类型 0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)
        'order_type',
        // 物流付款单号编码
        'payaway_order_no',
        // 物流付款单ID
        'payaway_order_id',
        // 应付金额（总金额）
        'total_fee',
        // 客户id
        'company_id',
        // 客户名称
        'company_name',
        // 增值费用
        'increment_fee',
        // 申请付款状态 1-待申请 2-已申请
        'status',
        // 运单号
        'transport_order_no',
        // 所属物流公司
        'logistics_company',
        // 所属物流公司ID
        'logistics_id',
        // 所属发货单
        'invoice_order_no',
        // 发货日期
        'send_at',
        // 订单编号
        'order_no',
        // 原平台订单编号
        'platform_order_no',
        // 平台名称
        'platform',
        // 店铺名称
        'shop_name',
        // 店铺ID 关联平台店铺
        'shop_id',
        // 商品sku
        'sku',
        // 商品分类
        'category_name',
        // zone
        'zone',
        // 基础运费 FRT
        'basics_fee',
        // 燃油附加费 FSC
        'fuel_surchage',
        // 住宅地址附加费 RES/REP
        'residential_delivery',
        // 偏远地区附加费 RDC
        'DAS_comm',
        // 超偏远 地区附加费 LDC
        'DAS_extended_comm',
        // resi/偏远 地区附加费-住宅 RDR
        'DAS_reis',
        //  DAS  Extended  resi/超偏远 地区附加费-住宅  LDR
        'DAS_extended_reis',
        // 额外处理费  //AHG/AHL/AHW/AHS
        'AHS',
        // 高峰期额外处理费 SAH
        'peak_AHS_charge',
        // 地址修正  IRW
        'address_correction',
        // 超长超尺寸费 LPR/LPS
        'oversize_charge',
        // 高峰期超尺寸附加费 SLP
        'peak_oversize_charge',
        // 工作日派送
        'weekday_delivery',
        // 签名费
        'direct_signature',
        // 不可发  OVR
        'unauthorized_OS',
        // 高峰期 取消授权费用
        'peak_unauth_charge',
        // 快递取件费  OFW/OSW
        'courier_pickup_charge',
        // 打印快递面单费用  ALP
        'print_return_label',
        // 其他费用
        'other_fee',
        // 退件费
        'return_pickup_fee',
        //附加费确认状态 1-确认 0-未确认
        'is_confirm',
        // Actual Weight 实际重量
        'actual_weight',
        // Billed Weight 计费重量
        'billed_weight',
        // 修改人/操作人
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

    protected $append = ['surcharge', 'pay_amount', 'status_text'];


    public static function  exportField()
    {
        return [

            [
                'title' => '运单号',
                'filed' => 'transport_order_no',
            ],
            [
                'title' => '所属物流公司',
                'filed' => 'logistics_company',
            ],
            [
                'title' => '所属发货单',
                'filed' => 'invoice_order_no',
            ],
            [
                'title' => '订单编号',
                'filed' => 'order_no',
            ],
            [
                'title' => '平台订单编号',
                'filed' => 'platform_order_no',
            ],
            [
                'title' => '所属客户',
                'filed' => 'company_name',
            ],
            [
                'title' => '燃油附加费',
                'filed' => 'fuel_surchage',
            ],
            [
                'title' => '住宅地址附加费',
                'filed' => 'residential_delivery',
            ],
            [
                'title' => '额外处理费',
                'filed' => 'AHS',
            ],
            [
                'title' => 'Peak-AHS Charge',
                'filed' => 'peak_AHS_charge',
            ],
            [
                'title' => '地址修正',
                'filed' => 'address_correction',
            ],
            [
                'title' => '超长超尺寸费',
                'filed' => 'oversize_charge',
            ],
            [
                'title' => '高峰期超尺寸附加费',
                'filed' => 'peak_oversize_charge',
            ],
            [
                'title' => '工作日派送',
                'filed' => 'weekday_delivery',
            ],
            [
                'title' => '签名费',
                'filed' => 'direct_signature',
            ],
            [
                'title' => '不可发',
                'filed' => 'unauthorized_OS',
            ],
            [
                'title' => 'Peak - Unauth Charge',
                'filed' => 'peak_unauth_charge',
            ],
            [
                'title' => '快递取件费',
                'filed' => 'courier_pickup_charge',
            ],
            [
                'title' => '打印快递面单费用',
                'filed' => 'print_return_label',
            ],
            [
                'title' => '退件费',
                'filed' => 'return_pickup_fee',
            ],
            [
                'title' => '应扣额度',
                'filed' => 'surcharge',
            ],
            [
                'title' => '确认状态',
                'filed' => 'status_text',
            ],
            [
                'title' => '实际扣减额度',
                'filed' => 'pay_amount',
            ],
        ];
    }

    public function getSurchargeAttr()
    {
        // return $this->fuel_surchage + $this->residential_delivery + $this->AHS +
        //     $this->peak_AHS_charge + $this->address_correction + $this->oversize_charge +
        //     $this->peak_oversize_charge + $this->weekday_delivery + $this->direct_signature +
        //     $this->unauthorized_OS + $this->peak_unauth_charge + $this->courier_pickup_charge +
        //     $this->print_return_label + $this->return_pickup_fee;
        return $this->increment_fee;
    }

    public function getPayAmountAttr()
    {
        return $this->surcharge;
    }

    public function getStatusTextAttr()
    {
        return $this->status == 1 ? '确认' : '未确认';
    }
}
