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

class PurchaseOrders extends Migrator {
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
        $table = $this->table('purchase_orders', array('engine' => 'Innodb'));
        $table->addColumn('code', 'string', array(
            'limit' => 32, 'default' => '', 'comment' => '采购申请单编码'
        ))
              ->addColumn('amount', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '采购金额'
              ))
              ->addColumn('audit_status', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '审核状态，0-未审核 1-已审核'
              ))
              ->addColumn('audit_notes', 'string', array(
                  'limit' => 500, 'default' => 0, 'comment' => '审核意见'
              ))
              ->addColumn('contract_status', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '是否生成合同，0-未生成 1-已生成'
              ))
              ->addColumn('contract_code', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '合同编码'
              ))
              ->addColumn('organization', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '所属组织'
              ))
              ->addColumn('notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '备注'
              ))
              ->addColumn('created_by', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '创建人'
              ))
              ->addColumn('updated_by', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '修改人'
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
