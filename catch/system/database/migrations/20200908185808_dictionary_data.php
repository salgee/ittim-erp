<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class DictionaryData extends Migrator
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
        $table = $this->table('dictionary_data', ['engine' => 'MyISAM', 'collation' => 'utf8mb4_general_ci', 'comment' => '数据字典集合表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('dict_value', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => 'all_dictionary表中的字典值',])
			->addColumn('dict_data_name', 'string', ['limit' => 100,'null' => false,'default' => 0,'signed' => false,'comment' => '字典名称',])
			->addColumn('dict_data_value', 'string', ['limit' => 100,'null' => false,'default' => 0,'signed' => false,'comment' => '字典值(固定的不可变)',])
			->addColumn('sort', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '排序',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '软删除',])
			->addIndex(['dict_value'], ['name' => 'dict_value'])
            ->create();
    }
}
