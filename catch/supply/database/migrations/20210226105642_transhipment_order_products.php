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

class TranshipmentOrderProducts extends Migrator {
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
        $table = $this->table('transhipment_order_products', array('engine' => 'Innodb'));
        $table->addColumn('trans_order_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => '转运单id'
        ))
              ->addColumn('purchase_order_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '采购单id'
              ))
              ->addColumn('purchase_contract_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '采购合同id'
              ))
              ->addColumn('purchase_product_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '商品id'
              ))
              ->addColumn('trans_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '转运数量'
              ))
              ->addColumn('arrive_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '到仓数量'
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
