<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-06 18:24:23
 * @LastEditTime: 2021-11-30 10:51:27
 * @Description:
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
    // 发货列表
    $router->get('deliveryOrder', '\catchAdmin\delivery\controller\DeliveryOrder@index');
    // 导出货物列表信息
    // $router->post('deliveryExport', '\catchAdmin\delivery\controller\DeliveryOrder@deliveryExport');
    // 订单发货
    $router->post('deliveryOrder/getOrderdeLivery', '\catchAdmin\delivery\controller\DeliveryOrder@getOrderDelivery');

    // 获取快递单号【向ups/usps发货】
    $router->post('delivery', '\catchAdmin\delivery\controller\DeliveryOrder@delivery');
    // 获取快递单号【向ups发货】
    $router->post('deliveryUps', '\catchAdmin\delivery\controller\DeliveryOrder@deliveryUps');
    // 发货单详情
    $router->post('deliveryInfo', '\catchAdmin\delivery\controller\DeliveryOrder@deliveryInfo');
    // 发货确认
    $router->post('confirmDeliver/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@confirmDeliver');
    // 获取物流信息
    $router->post('deliveryUpsTracking', '\catchAdmin\delivery\controller\DeliveryOrder@deliveryUpsTracking');
    // 获取面单
    $router->post('deliveryGetLabel', '\catchAdmin\delivery\controller\DeliveryOrder@getLabel');
    // 打印面单
    $router->post('deliveryPrintLabel', '\catchAdmin\delivery\controller\DeliveryOrder@printLabel');
    // 获取面单
    $router->post('deliveryGetLabelAll', '\catchAdmin\delivery\controller\DeliveryOrder@getLabelAll');
    // 取消usps面单 cancel  
    $router->post('cancelUspsLabe', '\catchAdmin\delivery\controller\DeliveryOrder@cancelUspsLabe');
    // 获取物流信息 
    $router->post('deliveryUspsTracking', '\catchAdmin\delivery\controller\DeliveryOrder@deliveryUspsTracking');
    // 管理员作废已打印（异常发货单)
    $router->post('delOrderPrinted/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@delOrderPrinted');



    // 订单批量发货
    $router->post('deliveryOrder/ordersDeliver', '\catchAdmin\delivery\controller\DeliveryOrder@ordersDeliver');
    // 商品库存列表
    $router->get('deliveryOrders/warehouseStockList/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@warehouseStockList');
    // 手工发货
    $router->post('deliveryOrders/manualDelivery/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@manualDelivery');
    // 配件发货
    $router->post('deliveryOrders/partDelivery/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@partDelivery');
    // 导出发货单
    $router->post('deliveryOrders/importDeliverOrder', '\catchAdmin\delivery\controller\DeliveryOrder@importDeliverOrder');
    // 捡出单导出
    $router->post('deliveryOrders/importPickOrder', '\catchAdmin\delivery\controller\DeliveryOrder@importPickOrder');
    // 发货列表 getList
    $router->get('deliveryOrder/getList', '\catchAdmin\delivery\controller\DeliveryOrder@getList');
    // 异常物流导出 importAbnormalLogistics
    $router->post('deliveryOrders/importAbnormalLogistics', '\catchAdmin\delivery\controller\DeliveryOrder@importAbnormalLogistics');
    // 获取第三方商品库存
    $router->get('deliveryOrders/warehouseStockListOther/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@warehouseStockListOther');
    // 获取第三方物流导入模板  template
    $router->get('deliveryOrders/template', '\catchAdmin\delivery\controller\DeliveryOrder@template');
    // 导入第三方物流编号
    $router->post('deliveryOrders/importLogisticsOrderNo', '\catchAdmin\delivery\controller\DeliveryOrder@importLogisticsOrderNo');
    // 导出第三方发货单
    $router->post('deliveryOrders/exportThirdPartOrder', '\catchAdmin\delivery\controller\DeliveryOrder@exportThirdPartOrder');
    // 导出发货单
    $router->post('deliveryOrders/exportDeliveryOrder', '\catchAdmin\delivery\controller\DeliveryOrder@exportDeliveryOrder');
    // 导入物流单号
    $router->post('deliveryOrders/importLogisticsOrder', '\catchAdmin\delivery\controller\DeliveryOrder@importLogisticsOrder');
    // 修改发货物流
    $router->post('deliveryOrders/updateLogisticsType', '\catchAdmin\delivery\controller\DeliveryOrder@updateLogisticsType');

    // 客户订单批量发货 ordersDeliverCustomer
    $router->post('deliveryOrder/ordersDeliverCustomer', '\catchAdmin\delivery\controller\DeliveryOrder@ordersDeliverCustomer');
    // 作废发货单 voidOrder
    $router->post('deliveryOrders/voidOrder/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@voidOrder');
    // 同步物流信息
    $router->post('deliveryOrders/orderSynchronous', '\catchAdmin\delivery\controller\DeliveryOrder@orderSynchronous');
    // 批量确认发货单
    $router->post('deliveryOrders/confirmDeliverBatch', '\catchAdmin\delivery\controller\DeliveryOrder@confirmDeliverBatch');
    // 批量手工发货
    $router->post('deliveryOrders/batchManualShipment', '\catchAdmin\delivery\controller\DeliveryOrder@batchManualShipment');
    // 获取可发货启用虚拟仓库
    $router->get('deliveryOrders/getWarehouse', '\catchAdmin\delivery\controller\DeliveryOrder@getWarehouse');


    // 修改发货订单关联地址
    $router->post('deliveryOrders/updateAddress/<id>', '\catchAdmin\delivery\controller\DeliveryOrder@updateAddress');
    //usps
    // $router->get('usps', '\catchAdmin\delivery\controller\DeliveryOrder@usps');

    // 发货单 他有物流 批量确认收货
    $router->post('deliveryOrders/batchThridOrderDeliver', '\catchAdmin\delivery\controller\DeliveryOrder@batchThridOrderDeliver');
})->middleware('auth');
