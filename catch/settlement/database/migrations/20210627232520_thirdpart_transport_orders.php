<?php
// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~{$year} http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

use think\migration\Migrator;
use think\migration\db\Column;

class ThirdpartTransportOrders extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('thirdpart_transport_orders', array('engine' => 'Innodb'));
        $table->addColumn('send_date', 'string', array(
            'limit' => 10, 'default' => '', 'comment' => '发货日期'
        ))
            ->addColumn('city', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '发货日期'
            ))->addColumn('address', 'string', array(
                'limit' => 255, 'default' => '', 'comment' => '联系地址'
            ))->addColumn('zipcode', 'string', array(
                'limit' => 20, 'default' => '', 'comment' => '收货人邮编'
            ))->addColumn('shippment', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '运输方式'
            ))->addColumn('reference_number', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '参考号'
            ))
            ->addColumn('tracking_number', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '跟踪号'
            ))
            ->addColumn('warehouse_code', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '仓库代码'
            ))
            ->addColumn('order_no', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '订单号'
            ))
            ->addColumn('sku', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => 'SKU'
            ))
            ->addColumn('actual_weight', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => 'Actual Weight'
            ))
            ->addColumn('billed_weight', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => 'Billed Weight'
            ))
            ->addColumn('zone', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => 'Zone'
            ))
            ->addColumn('shipping_price_total', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => '报价运费求和'
            ))
            ->addColumn('shipping_price', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => '报价基础运费'
            ))
            ->addColumn('outbound_price', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => '出库处理费'
            ))
            ->addColumn('fuel_surchage', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Fuel surchage/燃油附加费'
            ))
            ->addColumn('residential_delivery', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Residential Delivery/住宅地址附加费'
            ))
            ->addColumn('das_comm', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'DAS Comm/偏远 地区附加费-商业'
            ))
            ->addColumn('das_extended_comm', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'DAS Extended Comm/超偏远 地区附加费-商业'
            ))
            ->addColumn('das_resi', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'DAS resi/偏远 地区附加费-住宅'
            ))
            ->addColumn('das_extended_resi', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'DAS  Extended  resi/超偏远 地区附加费-住宅'
            ))
            ->addColumn('ahs', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'AHS报价'
            ))
            ->addColumn('price', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => '报价金额'
            ))
            ->addColumn('address_correction', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => 'Address Correction/地址修正'
            ))
            ->addColumn('oversize_charge', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Oversize Charge/超长超尺寸费'
            ))
            ->addColumn('peak_oversize_charge', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Peak - Oversize Charge/高峰期超尺寸附加费'
            ))
            ->addColumn('weekday_delivery', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Weekday Delivery/工作日派送'
            ))
            ->addColumn('direct_signature', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Direct Signature/签名费'
            ))
            ->addColumn('unauthorized_os', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Unauthorized OS/不可发'
            ))
            ->addColumn('peak_unauth_charge', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Peak - Unauth Charge'
            ))
            ->addColumn('ourier_pickup_charge', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Courier Pickup Charge/快递取件费'
            ))
            ->addColumn('print_return_label', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Print Return Label/打印快递面单费用'
            ))
            ->addColumn('return_pickup_fee', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Return Pickup Fee/退件费'
            ))
            ->addColumn('ndoc', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'NDOC P/U- Auto Comm'
            ))
            ->addColumn('date_certain', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => 'Date Certain'
            ))
            ->addColumn('return_label', 'string', array(
                'limit' => 25, 'default' => '', 'comment' => '退件'
            ))
            ->addColumn('created_by', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '创建人'
            ))
            ->addColumn('updated_by', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '修改人'
            ))
            ->addColumn('created_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '创建时间'
            ))
            ->addColumn('updated_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '修改时间'
            ))
            ->addColumn('deleted_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '删除时间'
            ))
            ->save();
    }
}
