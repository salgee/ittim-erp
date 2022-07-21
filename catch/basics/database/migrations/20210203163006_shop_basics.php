<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class ShopBasics extends Migrator
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
        $table = $this->table('shop_basics', ['engine' => 'MyISAM', 'collation' => 'utf8mb4_general_ci', 'comment' => '店铺表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('is_status', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '状态，1：启用，0：禁用',])
			->addColumn('code', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '编码',])
			->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '名称',])
			->addColumn('user_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '关联人员id 关联平台用户表users',])
			->addColumn('platform_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '所属平台id  关联 platform ',])
			->addColumn('order_origin', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '订单来源  1-download 2-import 3-create',])
			->addColumn('type', 'boolean', ['null' => false,'default' => 1,'signed' => true,'comment' => '运营类型  1-自营 2-代储存',])
			->addColumn('company_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '客户id 关联表 company',])
			->addColumn('remarks', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '备注',])
			->addColumn('platform_parameters', 'json', ['limit' => MysqlAdapter::TEXT_REGULAR,'null' => false,'signed' => true,'comment' => '归属平台参数集合 使用json 拼接',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('create_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
