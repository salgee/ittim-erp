<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class States extends Migrator
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
        $table = $this->table('states', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '州' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('country_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 226,'signed' => true,'comment' => '国家id默认',])
			->addColumn('country_code', 'char', ['limit' => 3,'null' => false,'default' => 0,'signed' => true,'comment' => '国家代码 默认美国',])
			->addColumn('code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '州代码',])
			->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '州名称—英文',])
			->addColumn('cname', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '州名称-中文',])
			->addColumn('lower_name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '小写名称',])
			->addColumn('code_full', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '州代码全称',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
