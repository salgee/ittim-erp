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

class Supplies extends Migrator {
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
        $table = $this->table('supplies', array('engine' => 'Innodb'));
        $table->addColumn('name', 'string', array(
            'limit' => 50, 'default' => '', 'comment' => '供应商名称'
        ))
              ->addColumn('code', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '供应商代码'
              ))
              ->addColumn('billing_cycles', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '结算周期'
              ))
              ->addColumn('buyer', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '采购员'
              ))
              ->addColumn('contacts', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '联系人'
              ))
              ->addColumn('contacts_phone', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '联系人手机'
              ))
              ->addColumn('phone', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '固定电话'
              ))
              ->addColumn('fax', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '传真'
              ))
              ->addColumn('address', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '地址'
              ))
              ->addColumn('zipcode', 'string', array(
                  'limit' => 32, 'default' => '', 'comment' => '邮编'
              ))
              ->addColumn('pay_ratio', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '预付款比例'
              ))
              ->addColumn('notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '备注说明'
              ))
              ->addColumn('business_license', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '备注说明'
              ))
              ->addColumn('contract_template', 'text', array(
                  'default' => '', 'comment' => '合同模板'
              ))
              ->addColumn('audit_status', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '审核状态，0-待提交 1-待审核 2-已审核 -1 -审核拒绝'
              ))
              ->addColumn('audit_notes', 'string', array(
                  'limit' => 500, 'default' => '', 'comment' => '审核意见'
              ))
              ->addColumn('cooperation_status', 'boolean', array(
                  'limit' => 1, 'default' => 0, 'comment' => '合作状态，0-暂停 1-正常'
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
              ->addIndex(array('name'), array('unique' => true))
              ->addIndex(array('code'), array('unique' => true))
              ->create();
    }
}
