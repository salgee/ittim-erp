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

class FbaOrders extends Migrator {
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
        $table = $this->table('fba_allot_orders', array('engine' => 'Innodb'));
        $table->addColumn('entity_warehouse_id', 'integer', array(
            'limit' => 10, 'default' => 0, 'comment' => '调出仓库实体仓id'
        ))
              ->addColumn('virtual_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '调出虚拟仓id'
              ))
              ->addColumn('fba_warehouse_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => 'fba仓库id'
              ))
              ->addColumn('delivery_type', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '发货类型'
              ))
              ->addColumn('shipping_type', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '发货类型'
              ))
              ->addColumn('customer_type', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '是否由客户安排发货'
              ))
              ->addColumn('packing_service', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '装箱服务'
              ))
              ->addColumn('value_added_services', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '增值服务'
              ))
              ->addColumn('label_change_service', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '产品换标服务'
              ))
              ->addColumn('containers_label_service', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '外箱贴标服务'
              ))
              ->addColumn('pallet_label_service', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '托盘贴标服务'
              ))
              ->addColumn('logistics_fee', 'integer', array(
                  'limit' => 10, 'default' => 0,
                  'comment' => '托盘贴标服务'
              ))
              ->addColumn('attachment', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '特殊说明文件'
              ))
              ->addColumn('audit_status', 'boolean', array(
                  'limit' => 1, 'default' => 0,
                  'comment' => '//0 待提交 1待审核 2 审核通过 -1 审核驳回'
              ))
              ->addColumn('audit_notes', 'string', array(
                  'limit' => 255, 'default' => '', 'comment' => '审核意见'
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
