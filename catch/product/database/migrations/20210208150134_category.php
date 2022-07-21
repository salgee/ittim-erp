<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Category extends Migrator
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
        $table = $this->table('category', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '商品分类' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('parent_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '父分类id 父级0',])
			->addColumn('name', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '分类名',])
			->addColumn('code', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '分类编码',])
			->addColumn('remark', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '备注说明',])
			->addColumn('ZH_HS', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '国内(HS)',])
			->addColumn('EN_HS', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '国外(HS)',])
			->addColumn('tax_rebate_rate', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '国内退税率',])
			->addColumn('tax_tariff_rate', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '国外关税税率',])
			->addColumn('is_status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '是否可用，0-是，1-否',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
