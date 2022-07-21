<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderItemRecords extends Migrator
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
        $table = $this->table('order_item_records', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '订单商品表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_record_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '订单表ID',])
			->addColumn('goods_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '商品表ID',])
			->addColumn('name', 'string', ['limit' => 200,'null' => false,'default' => '','signed' => true,'comment' => '商品名称',])
			->addColumn('variation', 'json', ['null' => true,'default' => '','signed' => true,'comment' => '商品属性',])
			->addColumn('sku', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '商品SKU',])
			->addColumn('transaction_price_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '商品交易价格(单位)',])
			->addColumn('transaction_price_value', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '商品交易价格',])
			->addColumn('quantity_purchased', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '购买数量',])
			->addColumn('tax_amount_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '税额(单位)',])
			->addColumn('tax_amount_value', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '税额',])
			->addColumn('warehouse_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => true,'default' => 0,'signed' => true,'comment' => '发货仓库',])
            ->addColumn('buyer_email', 'string', ['limit' => 200,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的电子邮件',])
            ->addColumn('buyer_user_firstname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的名字',])
            ->addColumn('buyer_user_lastname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的姓氏',])
            ->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
