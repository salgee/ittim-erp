<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class Company extends Migrator
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
        $table = $this->table('company', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '客户表' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('is_status', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '状态，1：正常，0：禁用',])
			->addColumn('code', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '客户编码(代码)',])
			->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '客户名称',])
			->addColumn('type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '客户类型，1：代仓储，0：自营',])
			->addColumn('contacts', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '联系人',])
			->addColumn('mobile', 'string', ['limit' => 11,'null' => false,'default' => '','signed' => true,'comment' => '手机号码',])
			->addColumn('telephone', 'string', ['limit' => 20,'null' => false,'default' => '','signed' => true,'comment' => '座机',])
			->addColumn('salesman_username', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '业务员名称',])
			->addColumn('bank_name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '银行名称',])
			->addColumn('bank_number', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '银行卡号',])
			->addColumn('fax', 'string', ['limit' => 64,'null' => false,'default' => '','signed' => true,'comment' => '传真',])
			->addColumn('zip_code', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '邮编',])
			->addColumn('address', 'string', ['limit' => 500,'null' => false,'default' => '','signed' => true,'comment' => '地址',])
			->addColumn('remarks', 'string', ['limit' => 1000,'null' => false,'default' => '','signed' => true,'comment' => '备注说明',])
			->addColumn('user_type', 'boolean', ['null' => false,'default' => 0,'signed' => true,'comment' => '客户类型，1：外部客户，0：内部客户',])
			->addColumn('amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '总金额',])
			->addColumn('overage_amount', 'decimal', ['precision' => 15,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '剩余金额',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
