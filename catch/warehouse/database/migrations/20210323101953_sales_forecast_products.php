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

class SalesForecastProducts extends Migrator {
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
        $table = $this->table('sales_forecast_products', array('engine' => 'Innodb'));
        $table->addColumn('sales_forecast_id', 'string', array(
            'limit' => 50, 'default' => '', 'comment' => '年份'
        ))
              ->addColumn('goods_id', 'integer', array(
                  'limit' => 10, 'default' => 0, 'comment' => '商品id'
              ))
              ->addColumn('category_name', 'string', array(
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
              ->addColumn('packing_method', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '商品类型 1-普通商品 2-多箱包装'
              ))
              ->addColumn('jan', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '1月'
              ))
              ->addColumn('feb', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '2月'
              ))
              ->addColumn('mar', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '3月'
              ))
              ->addColumn('apr', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '4月'
              ))
              ->addColumn('may', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '5月'
              ))
              ->addColumn('jun', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '6月'
              ))
              ->addColumn('jul', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '7月'
              ))
              ->addColumn('aug', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '8月'
              ))
              ->addColumn('sept', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '9月'
              ))
              ->addColumn('oct', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '10月'
              ))
              ->addColumn('mov', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '11月'
              ))
              ->addColumn('dec', 'integer', array(
                  'limit' => '10', 'default' => 0, 'comment' => '12月'
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
