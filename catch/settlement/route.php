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

// you should use `$router`
$router->group(function () use ($router){
    $router->get('settlement/order-fee', '\catchAdmin\settlement\controller\Settlement@orderFee');
    $router->post('settlement/order-fee-export', '\catchAdmin\settlement\controller\Settlement@orderFeeExport');
    $router->get('settlement/order-fee-info/:id', '\catchAdmin\settlement\controller\Settlement@orderFeeInfo');
    $router->get('settlement/storage-fee', '\catchAdmin\settlement\controller\Settlement@storageFee');
    $router->get('settlement/storage-product-fee/:id/:deparmentId', '\catchAdmin\settlement\controller\Settlement@storageProductFee');
    $router->post('settlement/storage-fee-export', '\catchAdmin\settlement\controller\Settlement@storageFeeExport');
    $router->post('settlement/storage-product-fee-export', '\catchAdmin\settlement\controller\Settlement@storageProductFeeExport');

    $router->resource('discharge-cargo-fee', '\catchAdmin\settlement\controller\DischargeCargo');
    $router->post('discharge-cargo-fee/confirm', '\catchAdmin\settlement\controller\DischargeCargo@confirm');
    $router->post('discharge-cargo-fee-export', '\catchAdmin\settlement\controller\DischargeCargo@export');

    $router->resource('logistics-transport-order', '\catchAdmin\settlement\controller\LogisticsTransportOrder');
    $router->post('logistics-transport-order/confirm', '\catchAdmin\settlement\controller\LogisticsTransportOrder@confirm');
    $router->post('logistics-transport-order-export', '\catchAdmin\settlement\controller\LogisticsTransportOrder@export');
    $router->post('logistics-transport-order-import', '\catchAdmin\settlement\controller\LogisticsTransportOrder@importOrder');
    $router->get('third-logistics-transport-order', '\catchAdmin\settlement\controller\LogisticsTransportOrder@thirdPartLogisticsFee');
})->middleware('auth');

