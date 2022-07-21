<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class CityZip extends Migrator
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
        $table = $this->table('city_zip', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '城市邮编表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('city_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '城市表ID',])
            ->addColumn('zip', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '邮编',])
            ->addColumn('lon', 'decimal', ['precision' => 10,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '经度',])
			->addColumn('lat', 'decimal', ['precision' => 10,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '维度',])
			->addColumn('timezone', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '时区',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
