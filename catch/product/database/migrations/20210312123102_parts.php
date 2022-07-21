<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Parts extends Migrator
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
        $table = $this->table('parts', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '配件管理' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('image_url', 'string', ['limit' => 1024,'null' => false,'default' => '','signed' => true,'comment' => '配件主图',])
			->addColumn('category_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '二级分类id 关联 category',])
			->addColumn('code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '编码',])
			->addColumn('name_ch', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '中文名称',])
			->addColumn('flow_to', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '流向 1-国内 2-国外',])
			->addColumn('purchase_name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '采购员',])
			->addColumn('purchase_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '采购员id 关联 users',])
			->addColumn('length', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '长cm',])
			->addColumn('width', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '宽cm',])
			->addColumn('height', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '高cm',])
			->addColumn('volume', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '体积',])
			->addColumn('weight', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '重',])
			->addColumn('length_outside', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '外箱长cm',])
			->addColumn('width_outside', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '外箱宽cm',])
			->addColumn('height_outside', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '外箱高cm',])
			->addColumn('volume_outside', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '外箱体积',])
			->addColumn('box_rate', 'string', ['limit' => 50,'null' => false,'default' => '','signed' => true,'comment' => '箱率',])
			->addColumn('product_id', 'string', ['limit' => 2000,'null' => false,'default' => '','signed' => true,'comment' => '商品id， 多个使用 ，号隔开',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
