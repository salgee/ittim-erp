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

class PurchaseContracts extends Migrator {
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
        $table = $this->table('purchase_contracts', array('engine' => 'Innodb'));
        $table
            ->addColumn('purchase_order_id', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '采购单id'
            ))
            ->addColumn('purchase_order_code', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '采购单编号'
            ))
            ->addColumn('supply_id', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '供应商id'
            ))
            ->addColumn('supply_name', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '供应商名称'
            ))
            ->addColumn('code', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '合同编号'
            ))
            ->addColumn('amount', 'decimal', array(
                'limit' => '10,2', 'default' => 0, 'comment' => '采购金额'
            ))
            ->addColumn('audit_status', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '审核状态，0-未审核 1-已审核 -1 审核驳回'
            ))
            ->addColumn('transshipment', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '转出运状态 0-待出运 1-部分出运 -1 已出运'
            ))
            ->addColumn('content', 'text', array(
                 'default' => '', 'comment' => '合同内容'
            ))
            ->addColumn('created_by', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '创建人'
            ))
            ->addColumn('created_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '创建时间'
            ))
            ->addColumn('updated_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '修改时间'
            ))
            ->addColumn('deleted_at', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '删除时间'
            ))
            ->create();
    }
}
