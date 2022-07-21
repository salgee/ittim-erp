<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class AfterSaleOrder extends Migrator
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
        $table = $this->table('after_sale_order', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('sale_order_no', 'string', ['limit' => 15,'null' => false,'default' => '','signed' => true,'comment' => '订单编号（系统自动生成SH+年月日+4位流水）',])
			->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '0-待审核 1-审核通过 2-审核驳回',])
			->addColumn('examine_reason', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '审核拒绝原因',])
			->addColumn('sale_reason', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '售后原因 1-客户无理由退款退货 2-描述不符 3-包装问题 4-质量问题 5-物流问题',])
			->addColumn('remarks', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '备注',])
			->addColumn('platform_order_no', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '平台订单编号',])
			->addColumn('platform_order_no2', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '原平台订单编号',])
			->addColumn('order_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '平台订单id',])
			->addColumn('shop_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '店铺id',])
			->addColumn('fill_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '申请售后时输入金额',])
			->addColumn('refund_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '退款金额（售后产生费用）',])
			->addColumn('type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '售后类型 1-仅退款 2-退货退款 3-补货 4-召回 5-修改地址',])
			->addColumn('refund_type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '退款类型 1-部分退款 2-全部退款',])
			->addColumn('modify_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '修改金额 type=5,4',])
			->addColumn('logistics_no', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '物流单号 type=2,3,4',])
			->addColumn('refund_logistics', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '退款物流 1-ups 2-usps',])
			->addColumn('recall_num', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => 'type=4召回数量 type=2退货数量',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
