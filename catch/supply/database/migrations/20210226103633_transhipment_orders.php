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

class TranshipmentOrders extends Migrator
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
        $table = $this->table('transhipment_orders', array('engine' => 'Innodb'));
        $table
            ->addColumn('code', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '出运单号'
            ))
            ->addColumn('shipment', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '运输方式'
            ))
            ->addColumn('shipment_port', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '起运港'
            ))
            ->addColumn('destination_port', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '目的港'
            ))
            ->addColumn('lcl_type', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '拼柜类型'
            ))
            ->addColumn('cabinet_type', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '柜型'
            ))
            ->addColumn('cabinet_no', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '柜号'
            ))
            ->addColumn('seal_no', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '封箱号'
            ))
            ->addColumn('ships_name', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '船名航次'
            ))
            ->addColumn('lforwarder_company', 'string', array(
                'limit' => 50, 'default' => 0, 'comment' => '货代公司'
            ))
            ->addColumn('loading_date', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '装箱日期'
            ))
            ->addColumn('shipment_date', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '起运日期'
            ))
            ->addColumn('arrive_date', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '预计到期日期'
            ))
            ->addColumn('bl_no', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '提单号'
            ))
            ->addColumn('notes', 'string', array(
                'limit' => 255, 'default' => '', 'comment' => '备注'
            ))
            ->addColumn('audit_status', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '审核状态，0-待提交 1-待审核 2-已审核 -1 -审核拒绝'
            ))
            ->addColumn('audit_notes', 'string', array(
                'limit' => 500, 'default' => '', 'comment' => '审核意见'
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
