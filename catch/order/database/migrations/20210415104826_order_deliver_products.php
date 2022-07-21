<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderDeliverProducts extends Migrator
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
        $table = $this->table('order_deliver_products', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_deliver_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '关联发货单订单id',])
			->addColumn('order_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '关联订单id',])
			->addColumn('goods_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '商品id',])
			->addColumn('category_name', 'string', ['limit' => 220,'null' => false,'default' => '','signed' => true,'comment' => '商品分类',])
			->addColumn('goods_code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '商品编码',])
			->addColumn('goods_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '商品名称',])
			->addColumn('goods_name_en', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '商品名称(英文)',])
			->addColumn('goods_pic', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '商品缩率图',])
			->addColumn('transaction_price_currencyid', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '商品交易价格(单位)',])
			->addColumn('transaction_price_value', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '交易价格',])
			->addColumn('tax_amount_currencyid', 'string', ['limit' => 25,'null' => false,'default' => '','signed' => true,'comment' => '税额(单位)',])
			->addColumn('tax_amount_value', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '税额',])
			->addColumn('number', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '发货数量',])
			->addColumn('type', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '类型 1-普通商品 2-配件',])
			->addColumn('batch_no', 'integer', ['limit' => MysqlAdapter::INT_BIG,'null' => false,'default' => 0,'signed' => true,'comment' => '批次号',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
