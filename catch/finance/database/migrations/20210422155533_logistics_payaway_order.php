<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class LogisticsPayawayOrder extends Migrator
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
        $table = $this->table('logistics_payaway_order', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物流付款单' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '申请付款状态 1-待申请 2-已申请',])
			->addColumn('payaway_status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '付款单状态 0-待付款 1-已付款',])
			->addColumn('payaway_amount', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '应付款金额',])
			->addColumn('payaway_amount_real', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '实际付款金额',])
			->addColumn('pay_time', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '实际付款时间',])
			->addColumn('payaway_order_no', 'string', ['limit' => 25,'null' => false,'default' => 0,'signed' => true,'comment' => '物流付款单号',])
			->addColumn('logistics_company', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '所属物流公司',])
			->addColumn('logistics_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '所属物流公司ID',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
