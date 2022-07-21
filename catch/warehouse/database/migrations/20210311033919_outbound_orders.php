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

class OutboundOrders extends Migrator {
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
        $table = $this->table('outbound_orders', array('engine' => 'Innodb'));
        $table->addColumn('code', 'string', array(
            'limit' => 50, 'default' => '', 'comment' => '出库单号'
        ))
              ->addColumn('entity_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '出库实体仓id'
              ))
              ->addColumn('virtual_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '出库虚拟仓id'
              ))
              ->addColumn('source', 'string', array(
                  'limit' => 50, 'default' => '',
                  'comment' => '出库单来源 sales 销售， manual 手工，  allot 调拨，  check 盘点'
              ))
              ->addColumn('audit_status', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '审核状态 0 待提交 1待审核 2 审核通过 -1 审核驳回'
              ))
              ->addColumn('audit_notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '审核原因'
              ))
              ->addColumn('outbound_status', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '审核状态 0 待出库  1已出库'
              ))
              ->addColumn('outbound_time', 'string', array(
                  'limit' => 25, 'default' => 0, 'comment' => '出库时间'
              ))
              ->addColumn('notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '出库原因'
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
              ))->create();
    }
}
