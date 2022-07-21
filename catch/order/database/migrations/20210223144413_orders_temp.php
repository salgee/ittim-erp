<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrdersTemp extends Migrator
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
        $table = $this->table('orders_temp', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '临时订单表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_no', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '平台订单号',])
			->addColumn('shop_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '店铺ID',])
			->addColumn('platform_name', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '平台名称',])
            ->addColumn('platform_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '平台ID',])
			->addColumn('is_sync_order', 'boolean', ['null' => false,'default' => 0,'signed' => false,'comment' => '是否同步到订单表，1已同步，0未同步',])
			->addColumn('sync_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '同步时间',])
			->addColumn('order_info', 'json', ['null' => false,'signed' => true,'comment' => '订单内容',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
