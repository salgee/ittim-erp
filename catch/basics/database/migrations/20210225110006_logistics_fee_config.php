<?php

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class LogisticsFeeConfig extends Migrator
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
        $table = $this->table('logistics_fee_config', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '物流台阶费用' ,'id' => 'id','signed' => true ,'primary_key' => ['id']]);
        $table->addColumn('name', 'string', ['limit' => 225,'null' => false,'default' => '','signed' => true,'comment' => '模板名称',])
			->addColumn('company_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '客户（公司）id 关联表 company',])
			->addColumn('insurance_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '保价费设置费用(USD)/每100usd',])
			->addColumn('gross_weight', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '毛重(lbs)',])
			->addColumn('gross_weight_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '毛重费用(包裹)',])
			->addColumn('big_side_length', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '最大边长（英寸）',])
			->addColumn('big_side_length_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '最大边长费用(包裹)',])
			->addColumn('second_side_length', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '次长边（英寸）',])
			->addColumn('second_side_length_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '次长边长费用(包裹)',])
			->addColumn('oversize_min_size', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '最小oversize（英寸）',])
			->addColumn('oversize_max_size', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '最大oversize（英寸）',])
			->addColumn('oversize_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '大于最小小于最大值费用(包裹)',])
			->addColumn('oversize_other_size', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '大于oversize尺寸（英寸），规则二',])
			->addColumn('oversize_other_size_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '大于oversize尺寸，规则二',])
			->addColumn('remote_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '偏远地区附加费(/包裹)',])
			->addColumn('super_remote_fee', 'decimal', ['precision' => 12,'scale' => 0,'null' => false,'default' => 0,'signed' => true,'comment' => '超偏远地区附加费',])
			->addColumn('update_by', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => true,'comment' => '修改人',])
			->addColumn('creator_id', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建人ID',])
			->addColumn('created_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '创建时间',])
			->addColumn('updated_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '更新时间',])
			->addColumn('deleted_at', 'integer', ['limit' => MysqlAdapter::INT_REGULAR,'null' => false,'default' => 0,'signed' => false,'comment' => '软删除',])
            ->create();
    }
}
