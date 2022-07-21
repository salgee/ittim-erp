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

class PurchaseInvoice extends Migrator
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
        $table = $this->table('purchase_invoice', array('engine' => 'Innodb'));
        $table->addColumn('purchase_code', 'string', array(
            'limit' => 255, 'default' => '', 'comment' => '采购单号'
        ))
              ->addColumn('invoice_no', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '发票号'
              ))
              ->addColumn('invoice_date', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '发票日期'
              ))
              ->addColumn('payer', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '付款单位'
              ))
              ->addColumn('rate', 'string', array(
                  'limit' => 25, 'default' => '', 'comment' => '发票税率'
              ))
              ->addColumn('tax_amount', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '税额'
              ))
              ->addColumn('unpaid_amount', 'string', array(
                  'limit' => 50, 'default' => '', 'comment' => '未付金额'
              ))
              ->addColumn('supply', 'string', array(
                'limit' => 50, 'default' => '', 'comment' => '供应商'
              ))
              ->addColumn('notes', 'string', array(
                'limit' => 255, 'default' => '', 'comment' => '备注'
              ))
              ->addColumn('attachment', 'string', array(
                    'limit' => 255, 'default' => '', 'comment' => '附件'
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
