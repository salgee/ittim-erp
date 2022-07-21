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

class DischargeCargoFee extends Migrator {
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
        $table = $this->table('discharge_cargo_fee', array('engine' => 'Innodb'));
        $table->addColumn('company_id', 'integer', array(
                'limit' => 10, 'default' => 0, 'comment' => '客户id'
              ))
              ->addColumn('bill_time', 'string', array(
                  'limit' => 10, 'default' => '', 'comment' => '账单月份'
              ))
              ->addColumn('warehouse_id','integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '仓库id'
              ))
              ->addColumn('discharge_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '卸货商品数量'
              ))
              ->addColumn('discharge_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0,'comment' => '卸货单价'
              ))
              ->addColumn('check_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '入库验收商品数量'
              ))
              ->addColumn('check_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0,'comment' => '入库验收单价'
              ))
              ->addColumn('outbound_service_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '标准订单出库服务商品数量'
              ))
              ->addColumn('outbound_service_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0,'comment' => '标准订单出库服务单价'
              ))
              ->addColumn('return_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '拒收/退货入库商品数量'
              ))
              ->addColumn('return_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0,'comment' => '拒收/退货入库单价'
              ))
              ->addColumn('pallet_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '打托商品数量'
              ))
              ->addColumn('pallet_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0, 'comment' => '打托单价'
              ))
              ->addColumn('label_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '贴标商品数量'
              ))
              ->addColumn('label_fee', 'decimal', array(
                  'precision' => 10, 'scale' => 2, 'null' => false, 'default' => 0,'comment' => '贴标单价'
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
              ->save();
    }
}
