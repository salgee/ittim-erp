<?php
/*
 * @Version: 1.0
 * @Date: 2021-06-16 15:38:47
 * @LastEditTime: 2022-01-13 17:20:38
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
    //供应商管理
    $router->resource('supply', '\catchAdmin\supply\controller\Supply');
    $router->post('supply/change-audit-status', '\catchAdmin\supply\controller\Supply@changeAuditStatus');
    $router->post('supply/change-cooperation-status', '\catchAdmin\supply\controller\Supply@changeCooperationStatus');
    $router->post('supply/upload', '\catchAdmin\supply\controller\Supply@upload');
    $router->post('supply/set-contract-template', '\catchAdmin\supply\controller\Supply@setContractTemplate');
    $router->post('supply/submit-audit', '\catchAdmin\supply\controller\Supply@SubmitAudit');
    $router->get('supply/buyers/list', '\catchAdmin\supply\controller\Supply@buyers');

    //采购单
    $router->resource('purchase-order', '\catchAdmin\supply\controller\PurchaseOrder');
    $router->post('purchase-order/change-audit-status', '\catchAdmin\supply\controller\PurchaseOrder@changeAuditStatus');
    $router->post('purchase-order/batch-delete', '\catchAdmin\supply\controller\PurchaseOrder@batchDelete');
    $router->post('purchase-order/submit-audit', '\catchAdmin\supply\controller\PurchaseOrder@SubmitAudit');
    $router->post('purchase-order/export', '\catchAdmin\supply\controller\PurchaseOrder@export');
    $router->post('purchase-order/operate-change-audit-status', '\catchAdmin\supply\controller\PurchaseOrder@operateChangeAuditStatus');
    $router->get('purchaseorder/purchaseJsonFix', '\catchAdmin\supply\controller\PurchaseOrder@purchaseJsonFix');

    //采购合同
    $router->resource('purchase-contract', '\catchAdmin\supply\controller\PurchaseContract');
    $router->post('purchase-contract/upload-attachment', '\catchAdmin\supply\controller\PurchaseContract@uploadAttachment');
    $router->post('purchase-contract/products', '\catchAdmin\supply\controller\PurchaseContract@getProducts');
    $router->post('purchase-contract/change-audit-status', '\catchAdmin\supply\controller\PurchaseContract@changeAuditStatus');
    $router->post('purchase-contract/export', '\catchAdmin\supply\controller\PurchaseContract@export');
    $router->get('purchase-contract/product/barcorde/:id', '\catchAdmin\supply\controller\PurchaseContract@createBarCode');
    $router->get('purchase-contract/conver/contract-to-pdf/:id', '\catchAdmin\supply\controller\PurchaseContract@contractToPdf');

    //出运单
    $router->resource('transhipment-order', '\catchAdmin\supply\controller\TranshipmentOrder');
    $router->post('transhipment-order/submit-audit', '\catchAdmin\supply\controller\TranshipmentOrder@SubmitAudit');
    $router->post('transhipment-order/change-audit-status', '\catchAdmin\supply\controller\TranshipmentOrder@changeAuditStatus');
    $router->delete('transhipment-order/delete-product/:id', '\catchAdmin\supply\controller\TranshipmentOrder@deleteProduct');
    $router->post('transhipment-order/sub-orders', '\catchAdmin\supply\controller\TranshipmentOrder@subOrders'); //预分仓
    $router->post('transhipment-order/confirm-suborders', '\catchAdmin\supply\controller\TranshipmentOrder@confirmSubOrders'); //确认分仓
    $router->post('transhipment-order/modify-subnumber', '\catchAdmin\supply\controller\TranshipmentOrder@modifySubNumber'); //确认分仓
    $router->post('transhipment-order/confirm-arrive', '\catchAdmin\supply\controller\TranshipmentOrder@confirmArrive'); //确认到仓
    $router->get('transhipment-order/sub-order/detail/:id', '\catchAdmin\supply\controller\TranshipmentOrder@subOrderdetail'); //确认到仓
    $router->get('transhipment-order/warehouse-order/:id', '\catchAdmin\supply\controller\TranshipmentOrder@warehouseOrder'); //根据出运单查找入库单
    $router->post('transhipment-order/order/export', '\catchAdmin\supply\controller\TranshipmentOrder@export'); //出运单导出
    // 所有出运单列表 all
    $router->get('transhipment/order/all', '\catchAdmin\supply\controller\TranshipmentOrder@all');


    //费用单
    $router->resource('utilitybill', '\catchAdmin\supply\controller\UtilityBill');
    $router->get('utilitybill/ocean-shipping-bill/:id', '\catchAdmin\supply\controller\UtilityBill@oceanShippingBill');
    $router->get('utilitybill/domestic-trans-bill/:id', '\catchAdmin\supply\controller\UtilityBill@domesticTransBill');
    $router->get('utilitybill/total-bill/:id', '\catchAdmin\supply\controller\UtilityBill@totalBill');
    $router->post('utilitybill/batch-delete', '\catchAdmin\supply\controller\UtilityBill@batchDelete');
    $router->post('utilitybill/total-bill-export', '\catchAdmin\supply\controller\UtilityBill@totalBillExport');
    $router->post('utilitybill/domestic-trans-bill-export', '\catchAdmin\supply\controller\UtilityBill@domesticTransBillExport');
    $router->post('utilitybill/ocean-shipping-bill-export', '\catchAdmin\supply\controller\UtilityBill@oceanShippingBillExport');
    // 费用导出 
    $router->post('utilitybill/utility-bill-export', '\catchAdmin\supply\controller\UtilityBill@utilityBillExport');


    //采购发票
    $router->resource('purchase-invoice', '\catchAdmin\supply\controller\PurchaseInvoice');
    $router->post('purchase-invoice-export', '\catchAdmin\supply\controller\PurchaseInvoice@export');
})->middleware('auth');
