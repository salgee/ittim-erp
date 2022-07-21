<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-04 09:50:34
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-14 14:01:33
 * @Description:
 */

// +----------------------------------------------------------------------
// | CatchAdmin [Just Like ～ ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2021 http://catchadmin.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://github.com/yanwenwu/catch-admin/blob/master/LICENSE.txt )
// +----------------------------------------------------------------------
// | Author: JaguarJack [ njphper@gmail.com ]
// +----------------------------------------------------------------------


if (!empty($router)) {
    $router->group(function () use ($router) {

        // 店铺管理
        $router->resource('shop', '\catchAdmin\basics\controller\Shop');
        // 店铺禁用
        $router->post('shop/disable', '\catchAdmin\basics\controller\Shop@disable');
        // 店铺启用
        $router->post('shop/enable', '\catchAdmin\basics\controller\Shop@enable');
        // 店铺用户信息
        $router->resource('sender', '\catchAdmin\basics\controller\Sender');
        // 店铺关联仓库
        $router->resource('shopWarehouse', '\catchAdmin\basics\controller\ShopWarehouse');
        // 仓库绑定
        $router->put('shop/bindWarehouse/<id>', '\catchAdmin\basics\controller\Shop@bindWarehouse');
        // 用户列表
        $router->get('user/list', '\catchAdmin\basics\controller\Shop@userList');
        // 绑定用户
        $router->put('shop/bindUser/<id>', '\catchAdmin\basics\controller\Shop@bindUser');
        // 采购员列表
        $router->get('shops/buyers', '\catchAdmin\basics\controller\Shop@buyers');
        // 查看店铺绑定仓库
        $router->get('shops/seeBindWarehouse/<id>', '\catchAdmin\basics\controller\Shop@seeBindWarehouse');
        // 查看绑定用户
        $router->get('shops/seeBindUser/<id>', '\catchAdmin\basics\controller\Shop@seeBindUser');
        // 根据平台查看店铺列表
        $router->get('shops/getShopPlatform/<id>', '\catchAdmin\basics\controller\Shop@getShopPlatform');
        // 导出
        $router->post('shops/export', '\catchAdmin\basics\controller\Shop@export');
        // 手工拉取订单 manualPull
        $router->post('shops/manualPull/<id>', '\catchAdmin\basics\controller\Shop@manualPull');


        // 币别管理
        $router->resource('currency', '\catchAdmin\basics\controller\Currency');
        // 币别禁用
        $router->post('currency/disable', '\catchAdmin\basics\controller\Currency@disable');
        // 币别启用
        $router->post('currency/enable', '\catchAdmin\basics\controller\Currency@enable');
        // 所有币别列表
        $router->get('currencyAll', '\catchAdmin\basics\controller\Currency@getAll');
        // 关联账户
        $router->resource('lfCurrency', '\catchAdmin\basics\controller\LfCurrency');


        // 物流-货代公司管理
        $router->resource('lforwarder', '\catchAdmin\basics\controller\Lforwarder');
        // 物流-货代公司禁用
        $router->post('lforwarder/disable', '\catchAdmin\basics\controller\Lforwarder@disable');
        // 物流-货代公司启用
        $router->post('lforwarder/enable', '\catchAdmin\basics\controller\Lforwarder@enable');

        // 货代公司管理
        $router->resource('LforwarderFreight', '\catchAdmin\basics\controller\LforwarderFreight');
        // 货代公司禁用
        $router->post('LforwarderFreight/disable', '\catchAdmin\basics\controller\LforwarderFreight@disable');
        // 货代公司启用
        $router->post('LforwarderFreight/enable', '\catchAdmin\basics\controller\LforwarderFreight@enable');


        // 地址管理
        $router->resource('address', '\catchAdmin\basics\controller\Address');
        // 地址验证
        $router->post('address/checkAddress', '\catchAdmin\basics\controller\Address@checkAddress');
        // 州
        $router->resource('states', '\catchAdmin\basics\controller\States');
        // 城市
        $router->resource('city', '\catchAdmin\basics\controller\City');
        // 城市邮编查询
        $router->get('cityPostalCode/<id>', '\catchAdmin\basics\controller\City@cityPostalCode');


        // 邮编分区管理
        $router->resource('zipCode', '\catchAdmin\basics\controller\ZipCode');
        // 导入邮编分区
        $router->post('zipImport', '\catchAdmin\basics\controller\ZipCode@zipImport');
        // 导入模板下载 template
        $router->get('zipImport/template', '\catchAdmin\basics\controller\ZipCode@template');
        // 导入超级偏远邮编 zipImport
        $router->post('zipCodeSpecialMax/zipImport', '\catchAdmin\basics\controller\ZipCodeSpecialMax@zipImport');
        // 超级偏远邮编
        $router->resource('zipCodeSpecialMax', '\catchAdmin\basics\controller\ZipCodeSpecialMax');


        // 特殊邮编管理偏远邮编
        $router->resource('zipCodeSpecial', '\catchAdmin\basics\controller\ZipCodeSpecial');
        // 导入偏远邮编 zipImport
        $router->post('zipCodeSpecial/zipImport', '\catchAdmin\basics\controller\ZipCodeSpecial@zipImport');

        // 客户管理
        $router->resource('company', '\catchAdmin\basics\controller\Company');
        // 币别禁用
        $router->post('company/disable', '\catchAdmin\basics\controller\Company@disable');
        // 币别启用
        $router->post('company/enable', '\catchAdmin\basics\controller\Company@enable');
        // 客户币别管理
        $router->resource('company_quota', '\catchAdmin\basics\controller\CompanyQuota');
        // 客户账号设置
        $router->put('companys/accountCreated/<id>', '\catchAdmin\basics\controller\Company@accountCreated');
        // 客户设置仓库 bindWarehouse
        $router->put('company/bindWarehouse/<id>', '\catchAdmin\basics\controller\Company@bindWarehouse');
        // 查看客户绑定仓库  seeBindWarehouse
        $router->get('companys/seeBindWarehouse/<id>', '\catchAdmin\basics\controller\Company@seeBindWarehouse');
        // 所有客户列表 (无权限)
        $router->get('companys/allCompany', '\catchAdmin\basics\controller\Company@allCompany');



        // 订单台阶费设置
        $router->resource('orderFeeSetting', '\catchAdmin\basics\controller\OrderFeeSetting');
        // 仓储台阶费禁用
        $router->post('orderFeeSetting/disable', '\catchAdmin\basics\controller\OrderFeeSetting@disable');
        // 仓储台阶费启用
        $router->post('orderFeeSetting/enable', '\catchAdmin\basics\controller\OrderFeeSetting@enable');

        // 仓储台阶费设置
        $router->resource('storageFeeConfig', '\catchAdmin\basics\controller\StorageFeeConfig');
        // 仓储台阶费禁用
        $router->post('storageFeeConfig/disable', '\catchAdmin\basics\controller\StorageFeeConfig@disable');
        // 仓储台阶费启用
        $router->post('storageFeeConfig/enable', '\catchAdmin\basics\controller\StorageFeeConfig@enable');

        // 物流台阶费设置
        $router->resource('logisticsFeeConfig', '\catchAdmin\basics\controller\LogisticsFeeConfig');
        // 仓储台阶费禁用
        $router->post('logisticsFeeConfig/disable', '\catchAdmin\basics\controller\LogisticsFeeConfig@disable');
        // 仓储台阶费启用
        $router->post('logisticsFeeConfig/enable', '\catchAdmin\basics\controller\LogisticsFeeConfig@enable');
        // 首页统计数据
        $router->get('home/index', '\catchAdmin\basics\controller\Home@index');
        // 物流台阶费导入模板
        $router->get('logisticsFeeConfig/import/template', '\catchAdmin\basics\controller\LogisticsFeeConfig@template');
    })->middleware('auth');
}
