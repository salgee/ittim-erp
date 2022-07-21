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

class StorageProductFee extends Migrator {
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
        $table = $this->table('storage_product_fee', array('engine' => 'Innodb'));
        $table->addColumn('storage_fee_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => ''
        ))
              ->addColumn('goods_code', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '商品编码'
              ))
              ->addColumn('goods_name', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '商品名称'
              ))
              ->addColumn('batch_no', 'string', array(
                  'limit' => 25, 'default' => '', 'comment' => '批次号'
              ))
              ->addColumn('department_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '所属组织'
              ))
              ->addColumn('storage_number', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '即时库存'
              ))
              ->addColumn('warehousing_time', 'datetime', array(
                  'null' => false, 'default' => '0000-00-00 00:00:00', 'comment' => '入库时间'
              ))
              ->addColumn('storage_days', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '在库时长'
              ))
              ->addColumn('fee', 'decimal', array(
                  'precision' => 15, 'scale' => 0, 'null' => false, 'default' => 0,
                  'signed' => true, 'comment' => '仓储费用'
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
