<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 18:36:03
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-11-20 11:10:21
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
    // orderEbayRecords路由
    $router->resource('orderEbayRecords', '\catchAdmin\order\controller\OrderEbayRecords');
    // orderEbayItemRecords路由
    $router->resource('orderEbayItemRecords', '\catchAdmin\order\controller\OrderEbayItemRecords');
    // orderGetRecords路由
    $router->resource('orderGetRecords', '\catchAdmin\order\controller\OrderGetRecords');
    // 订单管理
    $router->resource('orderRecords', '\catchAdmin\order\controller\OrderRecords');
    // 作废
    $router->put('orderRecords/invalid/<id>', '\catchAdmin\order\controller\OrderRecords@invalid');
    // 导出
    $router->post('orderRecords/export', '\catchAdmin\order\controller\OrderRecords@export');
    // 异常订单转化为正常订单
    $router->put('orderRecords/orderConvert/<id>', '\catchAdmin\order\controller\OrderRecords@orderConvert');
    // 订单关联商品列表
    $router->get('orders/productList/<id>', '\catchAdmin\order\controller\OrderRecords@productList');
    // 申请售后
    $router->put('orders/createdAfterSale/<id>', '\catchAdmin\order\controller\OrderRecords@createdAfterSale');
    // 订单发货单信息
    $router->get('orders/orderDeliverList/<id>', '\catchAdmin\order\controller\OrderRecords@orderDeliverList');
    // Fba订单确认出库
    $router->post('orderRecords/deliveryFba/<id>', '\catchAdmin\order\controller\OrderRecords@deliveryFba');
    // fab订单报表补充 fabOrderReportFix
    $router->get('orderRecord/fabOrderReportFix', '\catchAdmin\order\controller\OrderRecords@fabOrderReportFix');
    // fbm fbmOrderReportFix
    $router->get('orderRecord/fbmOrderReportFix', '\catchAdmin\order\controller\OrderRecords@fbmOrderReportFix');
    // 手工拆单 
    $router->post('ordersTemp/manualSplitOrder/<id>', '\catchAdmin\order\controller\OrdersTemp@manualSplitOrder');
    // 修正订单已打印面单数量 
    $router->get('orderRecord/orderDeliveryNumFix', '\catchAdmin\order\controller\OrderRecords@orderDeliveryNumFix');
    // 获取所有订单列表  
    $router->get('orderRecord/getAllOrder', '\catchAdmin\order\controller\OrderRecords@getAllOrder');
    // 批量修改商品编码 modifyGoodMapping
    $router->post('orderRecords/modifyGoodMapping', '\catchAdmin\order\controller\OrderRecords@modifyGoodMapping');





    // 异常订单商品关联修改
    $router->post('orderRecords/updateProduct/<id>', '\catchAdmin\order\controller\OrderRecords@updateProduct');
    // 导入订单
    $router->post('orderRecords/import', '\catchAdmin\order\controller\OrderRecords@orderImport');
    // 下载导入模板
    $router->get('orderRecords/import/template', '\catchAdmin\order\controller\OrderRecords@template');
    // 客户订单选择商品列表
    $router->get('orderRecords/orderCustomerProduct/<id>', '\catchAdmin\order\controller\OrderRecords@orderCustomerProduct');
    // 导入亚马逊订单用户缺失地址
    $router->post('orderRecords/amazon/import', '\catchAdmin\order\controller\OrderRecords@orderAmazonImport');
    // 下载导入亚马逊订单用户缺失地址模板
    $router->get('orderRecords/import/amazon/template', '\catchAdmin\order\controller\OrderRecords@amazonTemplate');

    // 客户订单 客户列表 getCompanyList
    $router->get('orderRecord/getCompanyList', '\catchAdmin\order\controller\OrderRecords@getCompanyList');
    // 客户订单模板下载
    $router->get('orderRecords/import/customerTemplate', '\catchAdmin\order\controller\OrderRecords@customerTemplate');
    // 客户订单导入 
    $router->post('orderRecords/importCustomerOrder', '\catchAdmin\order\controller\OrderRecords@importCustomerOrder');

    // 借卖订单 店铺选择
    $router->get('orderRecord/borrowSellShopList', '\catchAdmin\order\controller\OrderRecords@borrowSellShopList');
    // 借卖订单选择客户 
    $router->get('orderRecord/borrowSellCompanyList', '\catchAdmin\order\controller\OrderRecords@borrowSellCompanyList');
    // 借卖订单商品选择 
    $router->get('orderRecord/borrowSellGoodsList', '\catchAdmin\order\controller\OrderRecords@borrowSellGoodsList');
    // 借卖订单模板下载 
    $router->get('orderRecords/import/borrowSellTemplate', '\catchAdmin\order\controller\OrderRecords@borrowSellTemplate');
    // 借卖订单导入
    $router->post('orderRecords/importBorrowSellOrder', '\catchAdmin\order\controller\OrderRecords@importBorrowSellOrder');

    // 订单批量作废 
    $router->post('orderRecords/orderInvalidMore', '\catchAdmin\order\controller\OrderRecords@orderInvalidMore');
    // 组合商品返回combinationProductList
    $router->get('orderRecord/combinationProductList/<id>', '\catchAdmin\order\controller\OrderRecords@combinationProductList');


    // orderBuyerRecords路由
    $router->resource('orderBuyerRecords', '\catchAdmin\order\controller\OrderBuyerRecords');
    // orderItemRecords路由
    $router->resource('orderItemRecords', '\catchAdmin\order\controller\OrderItemRecords');
    // ordersTemp路由
    $router->resource('ordersTemp', '\catchAdmin\order\controller\OrdersTemp');
    //测试
    $router->resource('ebay', '\catchAdmin\order\controller\Ebay');

    // 售后管理
    $router->resource('afterSaleOrder', '\catchAdmin\order\controller\AfterSaleOrder');
    // 地址详情
    $router->get('afterSaleOrders/addressInfo/<id>', '\catchAdmin\order\controller\AfterSaleOrder@addressInfo');
    // 地址审核
    $router->put('afterSaleOrders/orderCheck/<id>', '\catchAdmin\order\controller\AfterSaleOrder@orderCheck');
    // 修改金额
    $router->put('afterSaleOrders/setModifyAmount/<id>', '\catchAdmin\order\controller\AfterSaleOrder@setModifyAmount');
    // 导出
    $router->post('afterSaleOrders/export', '\catchAdmin\order\controller\AfterSaleOrder@export');
    // 退货入库
    $router->post('afterSaleOrders/returnsWarehous/<id>', '\catchAdmin\order\controller\AfterSaleOrder@returnsWarehous');
    // 召回入库
    $router->post('afterSaleOrders/recallWarehous/<id>', '\catchAdmin\order\controller\AfterSaleOrder@recallWarehous');
    // 补发出库
    $router->post('afterSaleOrders/reissueWarehous', '\catchAdmin\order\controller\AfterSaleOrder@reissueWarehous');
    // 获取发货仓库
    $router->get('afterSaleOrders/warehousesSubclass', '\catchAdmin\order\controller\AfterSaleOrder@warehousesSubclass');
    // 店铺绑定的实体仓库
    $router->get('afterSaleOrders/warehouseShop', '\catchAdmin\order\controller\AfterSaleOrder@warehouseShop');
    // 售后产生费用
    $router->put('afterSaleOrders/warehouseFee/<id>', '\catchAdmin\order\controller\AfterSaleOrder@warehouseFee');
    // 退款退货 获取物流单号  deliveryUps
    $router->post('afterSaleOrders/deliveryUps', '\catchAdmin\order\controller\AfterSaleOrder@deliveryUps');
    // 退款退货 打印物流面单 printLableUps
    $router->post('afterSaleOrders/printLableUps', '\catchAdmin\order\controller\AfterSaleOrder@printLableUps');
    // 详情 getUpsInfo
    $router->get('afterSaleOrders/getUpsInfo', '\catchAdmin\order\controller\AfterSaleOrder@getUpsInfo');
})->middleware('auth');
