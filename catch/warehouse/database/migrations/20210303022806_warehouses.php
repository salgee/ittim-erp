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

class Warehouses extends Migrator {
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
        $table = $this->table('warehouses', array('engine' => 'Innodb'));
        $table->addColumn('name', 'string', array(
            'limit' => 50, 'default' => '', 'comment' => '仓库名称'
        ))
              ->addColumn('code', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '仓库代码'
              ))
              ->addColumn('is_active', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '状态，1：正常，0：禁用'
              ))
              ->addColumn('type', 'boolean', array(
                  'limit' => 1, 'default' => 1, 'comment' => '仓库类型  1-实体仓 2-虚拟仓 3-残品仓 4-FBA仓'
              ))
              ->addColumn('parent_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '上级仓库'
              ))
              ->addColumn('department_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '所属组织'
              ))
              ->addColumn('state', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '州/省'
              ))
              ->addColumn('city', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '城市'
              ))
              ->addColumn('street', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '街道'
              ))
              ->addColumn('zipcode', 'string', array(
                  'limit' => 25, 'default' => '', 'comment' => '邮编'
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
              ))->create();
    }
}
