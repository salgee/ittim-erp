<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class ProductGroupAnnex extends Migrator
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
        $table = $this->table('product_group_annex', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('product_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '产品id 关联产品product',])
			->addColumn('size', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '尺寸',])
			->addColumn('weight', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '重量',])
			->addColumn('color', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '颜色',])
			->addColumn('material', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '材质',])
			->addColumn('parts', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '配件',])
			->addColumn('other_remark', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '其他备注',])
			->addColumn('pictures', 'string', ['limit' => 2000,'null' => false,'default' => '','signed' => true,'comment' => '整箱产品组成图片多张使用 ","',])
			->addColumn('detail_pictures', 'string', ['limit' => 2000,'null' => false,'default' => '','signed' => true,'comment' => '整箱产品细节图片多张使用 "," ',])
			->addColumn('description', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '说明书 文件 pdf',])
			->addColumn('box_mark', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '箱唛 文件 pdf',])
			->addColumn('other', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '其他内容 文件或 pdf',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
