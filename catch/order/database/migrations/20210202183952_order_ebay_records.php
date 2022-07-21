<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class OrderEbayRecords extends Migrator
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
        $table = $this->table('order_ebay_records', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => 'Ebay订单记录表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('shop_basics_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '关联店铺表ID',])
            ->addColumn('orderid', 'string', ['limit' => 50,'null' => true,'signed' => true,'comment' => 'EBay订单ID',])
			->addColumn('order_status', 'string', ['limit' => 15,'null' => true,'signed' => true,'comment' => '订单状态',])
			->addColumn('adjustment_amount_currencyid', 'string', ['limit' => 10,'null' => true,'signed' => true,'comment' => '订单调整金额（单位）',])
			->addColumn('adjustment_amount_value', 'string', ['limit' => 50,'null' => true,'signed' => true,'comment' => '订单调整金额（正数表示支付额外费用，负值表示折扣）',])
			->addColumn('amount_paid_currencyid', 'string', ['limit' => 10,'null' => true,'signed' => true,'comment' => '订单支付的总金额（单位）',])
			->addColumn('amount_paid_value', 'string', ['limit' => 50,'null' => true,'signed' => true,'comment' => '订单支付的总金额',])
			->addColumn('amount_saved_currencyid', 'string', ['limit' => 10,'null' => true,'signed' => true,'comment' => '订单节省的金额(单位)',])
			->addColumn('amount_saved_value', 'string', ['limit' => 50,'null' => true,'signed' => true,'comment' => '订单节省的金额',])
			->addColumn('checkout_date', 'datetime', ['null' => false,'default' => '0000-00-00 00:00:00','signed' => true,'comment' => '支付时间',])
			->addColumn('createdtime_date', 'datetime', ['null' => false,'default' => '0000-00-00 00:00:00','signed' => true,'comment' => '创建时间',])
			->addColumn('address_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '用户姓名',])
			->addColumn('address_addressid', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '用户地址在eBay数据库中用户地址的唯一ID',])
			->addColumn('address_address_owner', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => 'eBay与PayPal',])
			->addColumn('address_cityname', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '城市',])
			->addColumn('address_country', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '国家的两位数字代码',])
			->addColumn('address_country_name', 'string', ['limit' => 200,'null' => false,'default' => '','signed' => true,'comment' => '国家的全名',])
			->addColumn('address_street1', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '用户街道地址的第一行',])
			->addColumn('address_street2', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => '用户街道地址的第二行',])
			->addColumn('address_stateorprovince', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '用户地址中的州或省',])
			->addColumn('address_phone', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '用户地址的电话号码',])
			->addColumn('address_postalcode', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '用户地址的邮编',])
			->addColumn('subtotal_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '订单中所有订单项的累计商品成本',])
			->addColumn('subtotal_value', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '订单中所有订单项的累计商品成本',])
			->addColumn('total_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '该总金额显示订单的总成本:包括项目总成本,运费',])
			->addColumn('total_value', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '该总金额显示订单的总成本:包括项目总成本,运费',])
			->addColumn('buyer_email', 'string', ['limit' => 200,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的邮箱',])
			->addColumn('buyer_user_firstname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的名字',])
			->addColumn('buyer_user_lastname', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '购买订单的买方的姓氏',])
			->addColumn('buyer_userid', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '购买用户ID',])
			->addColumn('paidtime_date', 'datetime', ['null' => false,'default' => '0000-00-00 00:00:00','signed' => true,'comment' => '支付时间',])
			->addColumn('extended_orderid', 'string', ['limit' => 30,'null' => false,'default' => '','signed' => true,'comment' => 'eBay REST API模型中eBay订单的唯一标识符',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
			->addIndex(['orderid'], ['unique' => true,'name' => 'unique_orderid'])
            ->create();
    }
}
