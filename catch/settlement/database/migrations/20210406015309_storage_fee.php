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

class StorageFee extends Migrator
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
        $table = $this->table('storage_fee', array('engine' => 'Innodb'));
        $table->addColumn('company_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => '客户id'
        ))
              ->addColumn('company_name', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '客户名称'
              ))
              ->addColumn('company_code', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '客户编码'
              ))
              ->addColumn('company_type', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '客户类型，1：代仓储，0：自营'
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
