<?php
/*
 * @Date: 2021-07-07 09:53:05
 * @LastEditTime: 2021-10-19 18:34:59
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
    // reportOrder路由
    $router->get('reportOrder/all', '\catchAdmin\report\controller\ReportOrder@index');
    // fbm报表
    $router->get('reportOrder/fbm', '\catchAdmin\report\controller\ReportOrder@fbmList');
    // fba报表
    $router->get('reportOrder/fba', '\catchAdmin\report\controller\ReportOrder@fbaList');
    //销售统计
    $router->get('reportOrder/salseReport', '\catchAdmin\report\controller\ReportOrder@salseReport');

    // 导出财务报表
    $router->post('reportOrder/export', '\catchAdmin\report\controller\ReportOrder@export');
    // 导出fbm报表
    $router->post('reportOrder/export/fbm', '\catchAdmin\report\controller\ReportOrder@exportFBM');
    // 导出fba报表
    $router->post('reportOrder/export/fba', '\catchAdmin\report\controller\ReportOrder@exportFBA');
    // 导出销售报表
    $router->post('reportOrder/export/sale', '\catchAdmin\report\controller\ReportOrder@exportSale');
    // 修复订单采购基准价格 orderPriceFix
    $router->get('reportOrder/orderPriceFix', '\catchAdmin\report\controller\ReportOrder@orderPriceFix');
})->middleware('auth');
