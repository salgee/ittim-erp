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

class FreightBills extends Migrator
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
        $table = $this->table('freight_bill', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '采购付款单',
            'id' => 'id', 'signed' => true, 'primary_key' => ['id']
        ]);
        $table
            ->addColumn('bl_no', 'string', [
                'limit' => 25, 'null' => false, 'comment' => '提货单',
            ])
            ->addColumn('cabinet_no', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '柜号',
            ])
            ->addColumn('loading_date', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '装柜日期',
            ])
            ->addColumn('shipment_date', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '起运日期',
            ])
            ->addColumn('arrive_date', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '预计到仓日期',
            ])
            ->addColumn('pay_status', 'boolean', [
                'null' => false, 'default' => 0, 'signed' => true,
                'comment' => '付款单状态 0-待申请 1-已申请',
            ])
            ->addColumn('payment_no', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '付款单号',
            ])
            ->addColumn('type', 'string', [
                'limit' => 25, 'null' => false, 'default' => '',
                'comment' => '类型 domestic-国内陆运 ocean-海运 overseas-国外陆运',
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
