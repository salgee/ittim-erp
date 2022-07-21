<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class LogisticsTransportOrder extends Migrator
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
        $table = $this->table('logistics_transport_order', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物流应付账款订单表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('payaway_order_no', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '物流付款单号编码',])
			->addColumn('payaway_order_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '物流付款单ID',])
			->addColumn('total_fee', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '应付金额（总金额）',])
			->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '申请付款状态 1-待申请 2-已申请',])
			->addColumn('transport_order_no', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '运单号',])
			->addColumn('logistics_company', 'string', ['limit' => 250,'null' => false,'default' => '','signed' => true,'comment' => '所属物流公司',])
			->addColumn('logistics_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '所属物流公司ID',])
			->addColumn('invoice_order_no', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '所属发货单',])
			->addColumn('order_no', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '订单编号',])
			->addColumn('platform_order_no', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '原平台订单编号',])
			->addColumn('platform', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '平台名称',])
			->addColumn('shop_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '店铺名称',])
			->addColumn('shop_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '店铺ID 关联平台店铺',])
			->addColumn('zone', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => 'zone',])
			->addColumn('basics_fee', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '基础运费',])
			->addColumn('fuel_surchage', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '燃油附加费',])
			->addColumn('residential_delivery', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '住宅地址附加费',])
			->addColumn('DAS_comm', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '偏远 地区附加费',])
			->addColumn('DAS_extended_comm', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '超偏远 地区附加费',])
			->addColumn('AHS', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '额外处理费',])
			->addColumn('peak_AHS_charge', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '高峰期额外处理费',])
			->addColumn('address_correction', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '地址修正',])
			->addColumn('oversize_charge', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '超长超尺寸费',])
			->addColumn('peak_oversize_charge', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '高峰期超尺寸附加费',])
			->addColumn('weekday_delivery', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '工作日派送',])
			->addColumn('direct_signature', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '签名费',])
			->addColumn('unauthorized_OS', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '不可发',])
			->addColumn('peak_unauth_charge', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '高峰期 取消授权费用',])
			->addColumn('courier_pickup_charge', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '快递取件费',])
			->addColumn('print_return_label', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '打印快递面单费用',])
			->addColumn('return_pickup_fee', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '退件费',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人/操作人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
