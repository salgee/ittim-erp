<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class LforwarderCompany extends Migrator
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
        $table = $this->table('lforwarder_company', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('type', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '公司类型，1：物流，2：货代',])
			->addColumn('code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '代码',])
			->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '名称',])
			->addColumn('settlement_cycle', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '结算周期',])
			->addColumn('is_status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '状态，1：正常，0：禁用',])
			->addColumn('remarks', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '备注',])
			->addColumn('update_by', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '修改人id',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
