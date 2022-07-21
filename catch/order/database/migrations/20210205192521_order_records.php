<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderRecords extends Migrator
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
        $table = $this->table('order_records', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '订单表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_no', 'string', ['limit' => 15,'null' => true,'default' => '','signed' => true,'comment' => '订单编号（系统自动生成O+年月日+5位流水）',])
			->addColumn('platform_no', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '平台订单编号1',])
            ->addColumn('platform_no_ext', 'string', ['limit' => 50,'null' => true,'default' => '','signed' => true,'comment' => '平台订单编号2',])
			->addColumn('platform', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '平台名称',])
			->addColumn('platform_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '平台ID',])
			->addColumn('shop_name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '店铺名称',])
			->addColumn('shop_basics_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '店铺ID',])
			->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '订单状态(1-待发货、2-发货中、3-配送中、4-已收货、5-作废订单)',])
			->addColumn('after_sale_status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '是否存在售后(1-是；0-否)',])
			->addColumn('total_price', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '合计金额',])
			->addColumn('get_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '订单拉取时间',])
			->addColumn('total_num', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '合计数量',])
			->addColumn('shipped_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '发货时间',])
			->addColumn('paid_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '支付时间',])
            ->addColumn('currency', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '币种',])
            ->addColumn('shipping_code', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '运单号',])
            ->addColumn('shipping_method', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '运输方式',])
            ->addColumn('platform_remark', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '买家备注',])
            ->addColumn('order_type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)',])
            ->addColumn('order_source', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '订单来源;0-平台接口;1-录入;2-导入',])
            ->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
            ->addColumn('updater_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新人ID',])
            ->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
			->addIndex(['order_no'], ['unique' => true,'name' => 'unique_order_no'])
            ->create();
    }
}
