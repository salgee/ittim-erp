<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderBuyerRecords extends Migrator
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
        $table = $this->table('order_buyer_records', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '订单收货人表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_record_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '订单表ID',])
			->addColumn('address_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '买家姓名',])
			->addColumn('address_phone', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '买家电话',])
			->addColumn('address_postalcode', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '买家的邮编',])
			->addColumn('address_country', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '买家的国家的代码',])
			->addColumn('address_country_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '买家的国家',])
			->addColumn('address_stateorprovince', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '买家的州/省',])
			->addColumn('address_cityname', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '买家的城市',])
			->addColumn('address_street1', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '买家的街道1',])
			->addColumn('address_street2', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '买家的街道2',])
			->addColumn('address_street3', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '买家的街道3',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
