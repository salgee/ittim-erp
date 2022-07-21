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

class AllotOrders extends Migrator {
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
        $table = $this->table('allot_orders', array('engine' => 'Innodb'));
        $table->addColumn('entity_warehouse_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => '实体仓id'
        ))
              ->addColumn('transfer_in_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '调入仓库'
              ))
              ->addColumn('transfer_out_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '调出仓库'
              ))
              ->addColumn('audit_status', 'boolean', array(
                  'limit' => 1, 'default' => 0,
                  'comment' => '审核状态 0 待提交 1 待审核 2调出审核通过 3 调入审核通过 -1 调出审核驳回 -2 调入审核驳回'
              ))
              ->addColumn('audit_notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '审核意见'
              ))
              ->addColumn('notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '调拨原因'
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
