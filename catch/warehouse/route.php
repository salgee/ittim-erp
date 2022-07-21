<?php
/*
 * @Date: 2021-08-25 14:05:43
 * @LastEditTime: 2022-01-13 15:58:40
 */
// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~{$year} http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

// you should use `$router`
$router->group(function () use ($router) {
    $router->get('warehouse/all', '\catchAdmin\warehouse\controller\Warehouse@tree');
    $router->resource('warehouse', '\catchAdmin\warehouse\controller\Warehouse');
    $router->post('warehouse/change-active-status', '\catchAdmin\warehouse\controller\Warehouse@changeActiveStatus');
    // 仓库usps 账号设置
    $router->post('warehouse/update-usps-message/<id>', '\catchAdmin\warehouse\controller\Warehouse@updateUspsMessage');

    //入库单
    $router->resource('warehouse-order', '\catchAdmin\warehouse\controller\WarehouseOrder');
    $router->post('warehouse-order/change-audit-status', '\catchAdmin\warehouse\controller\WarehouseOrder@changeAuditStatus');
    $router->post('warehouse-order/submit-audit', '\catchAdmin\warehouse\controller\WarehouseOrder@SubmitAudit');
    $router->post('warehouse-order/batch-delete', '\catchAdmin\warehouse\controller\WarehouseOrder@batchDelete');
    $router->post('warehouse-order-export', '\catchAdmin\warehouse\controller\WarehouseOrder@export');

    $router->put('warehouse-order/in-stock/:id', '\catchAdmin\warehouse\controller\WarehouseOrder@inStock');
    $router->get('warehouse/product/list', '\catchAdmin\warehouse\controller\Warehouse@products');
    $router->get('warehouse/parts/list', '\catchAdmin\warehouse\controller\Warehouse@parts');
    // 入库单商品json 增加 orderJsonFix
    $router->get('warehouseorder/orderJsonFix', '\catchAdmin\warehouse\controller\WarehouseOrder@orderJsonFix');

    //调拨单
    $router->resource('allot-order', '\catchAdmin\warehouse\controller\AllotOrder');
    $router->post('allot-order/change-audit-status', '\catchAdmin\warehouse\controller\AllotOrder@changeAuditStatus');
    $router->post('allot-order/submit-audit', '\catchAdmin\warehouse\controller\AllotOrder@SubmitAudit');
    $router->post('allot-order/batch-delete', '\catchAdmin\warehouse\controller\AllotOrder@batchDelete');
    $router->post('allot-order-export', '\catchAdmin\warehouse\controller\AllotOrder@export');
    $router->post('allot-order-import', '\catchAdmin\warehouse\controller\AllotOrder@importOrder');
    $router->get('allot-order-import-templage', '\catchAdmin\warehouse\controller\AllotOrder@importOrderTemplate');
    // allotOrderJsonFix
    $router->get('allotorder/allotOrderJsonFix', '\catchAdmin\warehouse\controller\AllotOrder@allotOrderJsonFix');

    //fba调拨单
    $router->get('fba-allot-order/service-fee', '\catchAdmin\warehouse\controller\FbaAllotOrder@serviceFee');
    $router->resource('fba-allot-order', '\catchAdmin\warehouse\controller\FbaAllotOrder');
    $router->post('fba-allot-order/change-audit-status', '\catchAdmin\warehouse\controller\FbaAllotOrder@changeAuditStatus');
    $router->post('fba-allot-order/submit-audit', '\catchAdmin\warehouse\controller\FbaAllotOrder@SubmitAudit');
    $router->post('fba-allot-order/batch-delete', '\catchAdmin\warehouse\controller\FbaAllotOrder@batchDelete');
    $router->post('fba-allot-order-export', '\catchAdmin\warehouse\controller\FbaAllotOrder@export');
    $router->get('fbaAllotorder/allotOrderJsonFix', '\catchAdmin\warehouse\controller\FbaAllotOrder@fbaAllotOrderJsonFix');


    //出库单
    $router->resource('outbound-order', '\catchAdmin\warehouse\controller\OutboundOrder');
    $router->post('outbound-order/change-audit-status', '\catchAdmin\warehouse\controller\OutboundOrder@changeAuditStatus');
    $router->post('outbound-order/submit-audit', '\catchAdmin\warehouse\controller\OutboundOrder@SubmitAudit');
    $router->post('outbound-order/batch-delete', '\catchAdmin\warehouse\controller\OutboundOrder@batchDelete');
    $router->put('outbound-order/in-stock/:id', '\catchAdmin\warehouse\controller\OutboundOrder@outStock');
    $router->post('outbound-order-export', '\catchAdmin\warehouse\controller\OutboundOrder@export');
    $router->get('outboundorder/orderJsonFix', '\catchAdmin\warehouse\controller\OutboundOrder@OutboundOrderJsonFix');


    //盘点单
    $router->resource('check-order', '\catchAdmin\warehouse\controller\CheckOrder');
    $router->get('check-order/warehouse-goods/:id', '\catchAdmin\warehouse\controller\CheckOrder@warehouseGoods');
    $router->post('check-order/update-order-stock', '\catchAdmin\warehouse\controller\CheckOrder@updateOrderStock');
    $router->post('check-order/warehouse-goods-stock', '\catchAdmin\warehouse\controller\CheckOrder@warehouseGoodsStock');
    $router->post('check-order/update-warehouse-goods-stock', '\catchAdmin\warehouse\controller\CheckOrder@updateWarehouseGoodsStock');
    $router->post('check-order/delete', '\catchAdmin\warehouse\controller\CheckOrder@delete');
    $router->post('check-order-export', '\catchAdmin\warehouse\controller\CheckOrder@export');

    //销量预计
    $router->resource('sales-forecasts', '\catchAdmin\warehouse\controller\SalesForecasts');

    //销量预警
    $router->get('sales-warning/replenishment', '\catchAdmin\warehouse\controller\SalesWarning@replenishment');
    $router->post('sales-warning/replenishment-export', '\catchAdmin\warehouse\controller\SalesWarning@replenishmentExport');
    $router->get('sales-warning/unsalable', '\catchAdmin\warehouse\controller\SalesWarning@unsalable');
    $router->get('sales-warning/unsalable/detail', '\catchAdmin\warehouse\controller\SalesWarning@unsalableDetail');
    $router->post('sales-warning/unsalable-export', '\catchAdmin\warehouse\controller\SalesWarning@unsalableExport');
    $router->post('sales-warning/unsalable/detail/export', '\catchAdmin\warehouse\controller\SalesWarning@unsalableDetailExport');

    //库存查询
    $router->resource('stock', '\catchAdmin\warehouse\controller\Stock');
    $router->post('stock-export', '\catchAdmin\warehouse\controller\Stock@export');
    $router->post('stock-import', '\catchAdmin\warehouse\controller\Stock@importStock');
    $router->post('stock-change', '\catchAdmin\warehouse\controller\Stock@changeStock');
    $router->post('init-stock-change', '\catchAdmin\warehouse\controller\Stock@initChangeStock');
})->middleware('auth');
