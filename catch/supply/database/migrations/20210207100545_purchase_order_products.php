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

class PurchaseOrderProducts extends Migrator {
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
        $table = $this->table('purchase_order_products', array('engine' => 'Innodb'));
        $table->addColumn('supply_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => '供应商id'
        ))
              ->addColumn('supply_name', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '供应商名称'
              ))
              ->addColumn('purchase_order_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '采购单id'
              ))
              ->addColumn('goods_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '商品id'
              ))
              ->addColumn('goods_code', 'string', array(
                  'limit' => 25, 'default' => 0, 'comment' => '商品编码'
              ))
              ->addColumn('goods_name', 'string', array(
                  'limit' => 100, 'default' => 0, 'comment' => '商品名称'
              ))
              ->addColumn('goods_name_en', 'string', array(
                  'limit' => 100, 'default' => 0, 'comment' => '商品名称(英文)'
              ))
              ->addColumn('container_rate', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '箱率'
              ))
              ->addColumn('goods_pic', 'string', array(
                  'limit' => 100, 'default' => 0, 'comment' => '商品缩率图'
              ))
              ->addColumn('buyer', 'string', array(
                  'limit' => 25, 'default' => 0, 'comment' => '采购员'
              ))
              ->addColumn('number', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '采购数量'
              ))
              ->addColumn('price', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '采购单价'
              ))
              ->addColumn('amount', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '采购金额'
              ))
              ->addColumn('delivery_date', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '交货时间'
              ))
              ->addColumn('arrive_date', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '交货时间'
              ))
              ->addColumn('notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '备注'
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
