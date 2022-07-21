<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Sender extends Migrator
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
        $table = $this->table('sender', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('shop_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '关联店铺id  关联店铺表 shop_basics',])
			->addColumn('warehouse_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '关联仓库id  关联仓库表 warehouse',])
			->addColumn('country_code', 'string', ['limit' => 3,'null' => false,'default' => 0,'signed' => true,'comment' => '国家代码默认中国',])
			->addColumn('company', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '寄件人公司',])
			->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '寄件人姓名',])
			->addColumn('phone', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '电话',])
			->addColumn('mobile', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '手机',])
			->addColumn('street', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '街道',])
			->addColumn('city', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '城市',])
			->addColumn('city_code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '州/省代码',])
			->addColumn('post_code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '邮编',])
			->addColumn('is_default', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '是否为默认配置，1是，0否',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
