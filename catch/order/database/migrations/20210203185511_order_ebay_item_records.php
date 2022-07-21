<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderEbayItemRecords extends Migrator
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
        $table = $this->table('order_ebay_item_records', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => 'Ebay订单商品记录表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_ebay_records_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => 'Ebay订单记录表ID',])
			->addColumn('itemid', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '商品ID',])
			->addColumn('site', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '站点',])
			->addColumn('title', 'string', ['limit' => 80,'null' => false,'default' => '','signed' => true,'comment' => '标题',])
			->addColumn('sku', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => 'sku标识',])
            ->addColumn('quantity_purchased', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '购买数量',])
			->addColumn('transaction_id', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '交易编号',])
			->addColumn('transaction_price_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '交易价格(单位)',])
			->addColumn('transaction_price_value', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '交易价格',])
            ->addColumn('variation_sku', 'string', ['limit' => 80,'null' => true,'default' => '','signed' => true,'comment' => '属性SKU标识',])
            ->addColumn('variation_title', 'string', ['limit' => 255,'null' => true,'default' => '','signed' => true,'comment' => '属性标题',])
            ->addColumn('variation_specifics', 'json', ['null' => true,'default' => '','signed' => true,'comment' => '属性规格(JSON)',])
            ->addColumn('buyer_email', 'string', ['limit' => 200,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的电子邮件',])
			->addColumn('buyer_user_firstname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的名字',])
			->addColumn('buyer_user_lastname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的姓氏',])
			->addColumn('shippedtime_date', 'datetime', ['null' => true,'default' => '0000-00-00 00:00:00','signed' => true,'comment' => '发货时间',])
			->addColumn('final_value_fee_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '最终费用(单位)',])
			->addColumn('final_value_fee_value', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '最终费用',])
            ->addColumn('total_tax_amount_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '总税额(单位)',])
            ->addColumn('total_tax_amount_value', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '总税额',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
