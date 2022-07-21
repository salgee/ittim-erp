<?php
// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~{$year} http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

use think\migration\Migrator;
use think\migration\db\Column;
use Phinx\Db\Adapter\MysqlAdapter;

class PurchasePayment extends Migrator {
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
    public function change () {
        $table = $this->table('purchase_payment', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '采购付款单',
            'id' => 'id', 'signed' => true, 'primary_key' => ['id']
        ]);
        $table
            ->addColumn('payment_no', 'string', [
                'limit' => 25, 'null' => false, 'default' => '', 'signed' => true,
                'comment' => '付款单号',
            ])
            ->addColumn('source', 'string', [
                'limit' => 25, 'null' => false, 'default' => '0',
                'comment' => '付款单来源',
            ])
            ->addColumn('trans_code', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '出运单号',
            ])
            ->addColumn('contract_code', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '合同单号',
            ])
            ->addColumn('supply_id', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => true, 'comment' => '供应商id',
            ])
            ->addColumn('supply_name', 'string', [
                'limit' => 25, 'null' => false, 'default' => '', 'signed' => true,
                'comment' => '供应商名称',
            ])
            ->addColumn('order_amount', 'string', [
                'limit' => 25, 'null' => false, 'default' => 0,
                'comment' => '应付款金额',
            ])->addColumn('estimated_pay_time', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '预计付款时间',
            ])
            ->addColumn('pay_title', 'string', [
                'null' => false, 'default' => '',
                'comment' => '付款抬头',
            ])
            ->addColumn('pay_status', 'boolean', [
                'null' => false, 'default' => 0, 'signed' => true,
                'comment' => '付款单状态 0-待付款 1-已付款',
            ])
            ->addColumn('pay_amount', 'string', [
                'limit' => 25, 'null' => false, 'default' => 0, 'signed' => true,
                'comment' => '实际付款金额',
            ])
            ->addColumn('pay_time', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '实际付款时间',
            ])
            ->addColumn('update_by', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => true, 'comment' => '修改人',
            ])
            ->addColumn('creator_id', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => false, 'comment' => '创建人ID',
            ])
            ->addColumn('created_at', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => false, 'comment' => '创建时间',
            ])
            ->addColumn('updated_at', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => false, 'comment' => '更新时间',
            ])
            ->addColumn('deleted_at', 'integer', [
                'limit' => MysqlAdapter::INT_REGULAR, 'null' => false, 'default' => 0,
                'signed' => false, 'comment' => '软删除',
            ])
            ->create();
    }
}
