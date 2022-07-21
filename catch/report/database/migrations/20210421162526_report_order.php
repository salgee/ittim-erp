<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class ReportOrder extends Migrator
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
        $table = $this->table('report_order', ['engine' => 'MyISAM', 'collation' => 'utf8mb4_general_ci', 'comment' => '订单报表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('order_no', 'string', ['limit' => 15,'null' => false,'default' => '','signed' => true,'comment' => 'erp系统自动生成的编号',])
			->addColumn('platform_no', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '渠道拉取的编号',])
			->addColumn('shipping_code', 'string', ['limit' => 200,'null' => true,'signed' => true,'comment' => '订单商品发货对应的物流单号',])
			->addColumn('shipping_company', 'string', ['limit' => 200,'null' => true,'signed' => true,'comment' => '订单发货对应的物流公司(非亚马逊物流)',])
			->addColumn('shop_basics_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '订单上所属店铺',])
			->addColumn('platform_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '订单上来源平台ID',])
			->addColumn('platform_name', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '订单上来源平台',])
			->addColumn('platform_sku', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => '渠道SKU编码',])
			->addColumn('product_sku', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => 'ERP系统中的SKU编码',])
			->addColumn('product_name', 'string', ['limit' => 100,'null' => false,'default' => '','signed' => true,'comment' => 'ERP系统中的SKU中文名称',])
			->addColumn('product_category_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '商品分类ID',])
			->addColumn('quantity', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '订单中该商品数量',])
			->addColumn('price_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '订单中该商品的销售金额',])
			->addColumn('tax_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '订单中该商品的税费',])
			->addColumn('purchase_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '采购基准价',])
			->addColumn('freight_fee', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '海运费',])
			->addColumn('tariff_fee', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '关税',])
			->addColumn('order_operation_fee', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '订单处理费',])
			->addColumn('express_fee', 'decimal', ['precision' => 15,'scale' => 0,'null' => true,'signed' => true,'comment' => '快递费',])
			->addColumn('express_surcharge_fee', 'decimal', ['precision' => 15,'scale' => 0,'null' => true,'signed' => true,'comment' => '快递增值附加费',])
			->addColumn('remark', 'string', ['limit' => 255,'null' => true,'signed' => true,'comment' => '备注',])
			->addColumn('order_type', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA',])
			->addColumn('purchase_amount_currencyid', 'string', ['limit' => 10,'null' => false,'default' => '','signed' => true,'comment' => '采购价单位usd,rmb',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
