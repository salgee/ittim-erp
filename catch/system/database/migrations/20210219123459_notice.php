<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Notice extends Migrator
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
        $table = $this->table('notice', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统公告表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('title', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '公告标题',])
			->addColumn('content', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR,'null' => false,'signed' => true,'comment' => '公告内容',])
			->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => false,'comment' => '状态：1-已发布，2-未发布',])
			->addColumn('sort', 'boolean', ['null' => true,'signed' => false,'comment' => '排序',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
            ->addColumn('updater_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新人ID',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
