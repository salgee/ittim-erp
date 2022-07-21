<?php
// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------

$router->group(function () use ($router) {

    // 物流应付账款查询
    $router->resource('logisticsTransportOrder', '\catchAdmin\finance\controller\LogisticsTransportOrder');
    // 导入物流应付账款
    $router->post('logisticsTransportOrders/importOrder', '\catchAdmin\finance\controller\LogisticsTransportOrder@importOrder');
    // 生成付款单
    $router->post('logisticsTransportOrders/createdPayawayOrder', '\catchAdmin\finance\controller\LogisticsTransportOrder@createdPayawayOrder');
    // 导入物流付款单 
    $router->post('logisticsTransportOrders/importOrderNew', '\catchAdmin\finance\controller\LogisticsTransportOrder@importOrderNew');
    // 导入模板下载  template
    $router->get('logisticsTransportOrders/template', '\catchAdmin\finance\controller\LogisticsTransportOrder@template');


    // 物流付款管理
    $router->resource('logisticsPayawayOrder', '\catchAdmin\finance\controller\LogisticsPayawayOrder');
    // 审核
    $router->post('logisticsPayawayOrder/examine/<id>', '\catchAdmin\finance\controller\LogisticsPayawayOrder@examine');
    // 录入实际付款金额
    $router->post('logisticsPayawayOrder/createActualPayment/<id>', '\catchAdmin\finance\controller\LogisticsPayawayOrder@createActualPayment');
    // 编辑
    $router->post('logisticsPayawayOrder/deleteTransportOrder/<id>', '\catchAdmin\finance\controller\LogisticsPayawayOrder@deleteTransportOrder');


    $router->resource('purchase-payment', '\catchAdmin\finance\controller\PurchasePayment');
    $router->post('purchase-payment/pay', '\catchAdmin\finance\controller\PurchasePayment@pay');
    $router->post('purchase-payment/change-audit-status', '\catchAdmin\finance\controller\PurchasePayment@changeAuditStatus');

    $router->resource('freight-bill', '\catchAdmin\finance\controller\FreightBill');
    $router->post('freight-bill/pay-order', '\catchAdmin\finance\controller\FreightBill@payOrder');
    $router->get('freight-bill-orders', '\catchAdmin\finance\controller\FreightBill@orders');
    $router->post('freight-bill-orders/pay', '\catchAdmin\finance\controller\FreightBill@pay');
    $router->post('freight-bill-orders/change-audit-status', '\catchAdmin\finance\controller\FreightBill@changeAuditStatus');
    $router->post('freight-bill-orders/freight-order-update', '\catchAdmin\finance\controller\FreightBill@freightOrderUpdate');
})->middleware('auth');
