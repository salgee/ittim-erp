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

class FbaOrderProducts extends Migrator {
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
        $table = $this->table('fba_allot_order_products', array('engine' => 'Innodb'));
        $table->addColumn('fba_allot_order_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => 'fab调拨单id'
        ))
              ->addColumn('goods_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '商品id'
              ))->addColumn('category_name', 'string', array(
                'limit' => 25, 'default' => 0, 'comment' => '商品分类'
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
              ->addColumn('goods_pic', 'string', array(
                  'limit' => 100, 'default' => 0, 'comment' => '商品缩率图'
              ))
              ->addColumn('number', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '调拨数量'
              ))
              ->addColumn('pallet_number', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '托盘数量'
              ))
              ->addColumn('label_price', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '贴标费'
              ))
              ->addColumn('pallet_price', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '打托费'
              ))
              ->addColumn('outbound_price', 'decimal', array(
                  'limit' => '10,2', 'default' => 0, 'comment' => '出库费'
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
