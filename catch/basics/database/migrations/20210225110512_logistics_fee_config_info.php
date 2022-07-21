<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class LogisticsFeeConfigInfo extends Migrator
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
        $table = $this->table('logistics_fee_config_info', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物流台阶费用详情' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('logistics_fee_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '关联物流台阶费用ID logistics_fee_config',])
			->addColumn('weight', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '毛重 计费重量(lbs)',])
			->addColumn('zone2', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone2(USD)',])
			->addColumn('zone3', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone3(USD)',])
			->addColumn('zone4', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone4(USD)',])
			->addColumn('zone5', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone5(USD)',])
			->addColumn('zone6', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone6(USD)',])
			->addColumn('zone7', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone7(USD)',])
			->addColumn('zone8', 'string', ['limit' => 20,'null' => false,'default' => 0,'signed' => true,'comment' => 'zone8(USD)',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
