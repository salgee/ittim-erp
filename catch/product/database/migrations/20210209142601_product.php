<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Product extends Migrator
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
        $table = $this->table('product', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商品基础表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '审核状态，0-待审核 1-审核通过 2-审核驳回',])
			->addColumn('reason', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '驳回原因',])
			->addColumn('image_url', 'string', ['limit' => 1024,'null' => false,'default' => '','signed' => true,'comment' => '封面图',])
			->addColumn('category_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '二级分类id 关联 category',])
			->addColumn('code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '编码',])
			->addColumn('name_ch', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '中文名称',])
			->addColumn('name_en', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '英文名称',])
			->addColumn('operate_type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '运营类型：1-代营 2-自营',])
			->addColumn('ZH_HS', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '国内(HS)',])
			->addColumn('EN_HS', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '国外(HS)',])
			->addColumn('tax_rebate_rate', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '国内退税率',])
			->addColumn('tax_tariff_rate', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '国外关税税率',])
			->addColumn('bar_code_upc', 'string', ['limit' => 255,'null' => false,'default' => '','signed' => true,'comment' => 'upc条码',])
			->addColumn('bar_code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '产品条码',])
			->addColumn('supplier_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '供应商id 关联 supplier',])
			->addColumn('purchase_name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '采购员',])
			->addColumn('purchase_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '采购员id 关联 users',])
			->addColumn('company_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '所属客户id 关联 company',])
			->addColumn('purchase_price_rmb', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '采购价格-rmb',])
			->addColumn('purchase_price_usd', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '采购价格-usd',])
			->addColumn('insured_price', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '是否保价：1-保价 0-不保价',])
			->addColumn('packing_method', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '包装方式 ：1-普通商品 2-多箱包装',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
