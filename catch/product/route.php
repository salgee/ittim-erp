<?php
/*
 * @Version: 1.0

 * @Date: 2021-01-23 18:47:00
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-04 15:46:38
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
    // 商品分类
    $router->resource('category', '\catchAdmin\product\controller\Category');
    // 所有分类
    $router->get('getListTree', '\catchAdmin\product\controller\Category@getListTree');
    // 二级分类列表
    $router->get('category/getChildList/<id>', '\catchAdmin\product\controller\Category@getChildList');
    // 分类禁用启用
    $router->put('category/verify/<id>', '\catchAdmin\product\controller\Category@verify');

    // 商品管理
    $router->resource('product', '\catchAdmin\product\controller\Product');
    // 商品审核
    $router->put('product/examine/<id>', '\catchAdmin\product\controller\Product@examine');
    // 商品禁用
    $router->post('product/disable', '\catchAdmin\product\controller\Product@disable');
    // 商品启用
    $router->post('product/enable', '\catchAdmin\product\controller\Product@enable');
    // 提交审核
    $router->post('product/batchExamine', '\catchAdmin\product\controller\Product@batchExamine');
    // 商品订单选择列表
    $router->get('products/list', '\catchAdmin\product\controller\Product@orderProductList');
    // 导出商品
    $router->post('products/export', '\catchAdmin\product\controller\Product@export');
    // 商品导入
    $router->post('products/import', '\catchAdmin\product\controller\Product@productImport');
    // 导入模板002 productImportTwo
    $router->post('products/productImportTwo', '\catchAdmin\product\controller\Product@productImportTwo');
    // 商品模板下载
    $router->get('products/import/template', '\catchAdmin\product\controller\Product@template');
    // 修改采购员
    $router->post('products/updatePurchase/<id>', '\catchAdmin\product\controller\Product@updatePurchase');
    // 查询商品供应商和采购价
    $router->get('products/price/list', '\catchAdmin\product\controller\Product@findProductPrice');
    // 导出商品供应商和采购价
    $router->post('products/exportProductPrice', '\catchAdmin\product\controller\Product@exportProductPrice');
    // 查询店铺下商品（内部
    $router->get('products/getShopProductList/<id>', '\catchAdmin\product\controller\Product@getShopProductList');
    // 多箱商品商品列表分组信息查询
    $router->get('products/getMultiGroupList', '\catchAdmin\product\controller\Product@getMultiGroupList');
    // 开发商品导入模板
    $router->get('products/templateDevelop', '\catchAdmin\product\controller\Product@templateDevelop');
    // 开发商品导入
    $router->post('products/developImprot', '\catchAdmin\product\controller\Product@developImprot');


    // 开发商品列表
    $router->get('products/developList', '\catchAdmin\product\controller\Product@developList');
    // 添加开发商品
    $router->post('products/developAdd', '\catchAdmin\product\controller\Product@developAdd');
    // 编辑开发商品
    $router->put('products/developEdit/<id>', '\catchAdmin\product\controller\Product@developEdit');
    // 开发商品转化正式商品
    $router->post('products/conversion', '\catchAdmin\product\controller\Product@conversion');
    // 批量删除
    $router->post('products/delDevelop', '\catchAdmin\product\controller\Product@delDevelop');
    // 开发商品导出
    $router->post('products/developmentProductExport', '\catchAdmin\product\controller\Product@developmentProductExport');
    // 多箱包装商品 客户模板
    $router->post('products/import/productGroupTest', '\catchAdmin\product\controller\Product@productGroupTest');
    // 导入组合商品 客户模板
    // $router->post('products/import/productCombination', '\catchAdmin\product\controller\Product@productCombination');

    // 商品价格管理
    $router->resource('productPrice', '\catchAdmin\product\controller\ProductPrice');
    // 商品价格审核
    $router->put('productPrice/examine/<id>', '\catchAdmin\product\controller\ProductPrice@examine');
    // 价格导出
    $router->post('productPrice/export', '\catchAdmin\product\controller\ProductPrice@export');
    // 商品价格生成 updateProductPrice
    $router->post('productPrice/updateProductPrice', '\catchAdmin\product\controller\ProductPrice@updateProductPrice');

    // 商品sku与平台商品映射
    $router->resource('productPlatformSku', '\catchAdmin\product\controller\ProductPlatformSku');
    // 商品映射列表，选择商品使用
    $router->get('getProductList', '\catchAdmin\product\controller\ProductPlatformSku@getProductList');
    // 映射导出
    $router->post('productPlatformSkus/export', '\catchAdmin\product\controller\ProductPlatformSku@export');
    // 导入编码映射 importSku
    $router->post('productPlatformSkus/importSku', '\catchAdmin\product\controller\ProductPlatformSku@importSku');
    // 商品sku模板下载
    $router->get('productPlatformSkus/import/template', '\catchAdmin\product\controller\ProductPlatformSku@template');
    // 映射导入 客户模板
    $router->post('productPlatformSkus/importSkuTwo', '\catchAdmin\product\controller\ProductPlatformSku@importSkuTwo');
    // 编码映射商品选择 systemGoodsList
    $router->get('productPlatformSkus/systemGoodsList', '\catchAdmin\product\controller\ProductPlatformSku@systemGoodsList');


    // 组合商品sku与平台商品映射
    $router->resource('combinationPlatformSku', '\catchAdmin\product\controller\CombinationPlatformSku');
    // 组合商品映射列表，选择商品使用
    $router->get('getCombinationList', '\catchAdmin\product\controller\CombinationPlatformSku@getProductList');
    // 组合商品映射导出
    $router->post('combinationPlatformSkus/export', '\catchAdmin\product\controller\CombinationPlatformSku@export');
    // 组合商品导入编码映射 importSku
    $router->post('combinationPlatformSkus/importSku', '\catchAdmin\product\controller\CombinationPlatformSku@importSku');
    // 苏荷商品sku模板下载
    $router->get('combinationPlatformSkus/import/template', '\catchAdmin\product\controller\CombinationPlatformSku@template');
    // 组合商品列表
    $router->get('combinationPlatformSkus/systemGoodsList', '\catchAdmin\product\controller\CombinationPlatformSku@systemGoodsList');
    // 组合商品导入 productCombination
    $router->post('productCombination/productCombination', '\catchAdmin\product\controller\ProductCombination@productCombination');
    // 组合商品导入模板
    $router->get('productCombination/templateCombination', '\catchAdmin\product\controller\ProductCombination@templateCombination');
    // 导出组合商品 export
    $router->post('productCombinations/export', '\catchAdmin\product\controller\ProductCombination@export');



    // 组合商品管理
    $router->resource('productCombination', '\catchAdmin\product\controller\ProductCombination');
    // 组合商品禁用
    $router->post('productCombination/disable', '\catchAdmin\product\controller\ProductCombination@disable');
    // 组合商品启用
    $router->post('productCombination/enable', '\catchAdmin\product\controller\ProductCombination@enable');

    // 商品促销价格模板
    $router->resource('productSalesPrice', '\catchAdmin\product\controller\ProductSalesPrice');
    // 商品促销价格审核
    $router->put('productSalesPrice/examine/<id>', '\catchAdmin\product\controller\ProductSalesPrice@examine');
    // 商品促销价格禁用
    $router->post('productSalesPrice/disable', '\catchAdmin\product\controller\ProductSalesPrice@disable');
    // 商品促销价格启用
    $router->post('productSalesPrice/enable', '\catchAdmin\product\controller\ProductSalesPrice@enable');
    // 商品促销价格提交审核
    $router->post('productSalesPrice/batchExamine', '\catchAdmin\product\controller\ProductSalesPrice@batchExamine');

    // 预售活动商品管理
    $router->resource('productPresale', '\catchAdmin\product\controller\ProductPresale');
    // 预售活动禁用
    $router->post('productPresale/disable', '\catchAdmin\product\controller\ProductPresale@disable');
    // 预售活动启用
    $router->post('productPresale/enable', '\catchAdmin\product\controller\ProductPresale@enable');

    // 配件管理
    $router->resource('parts', '\catchAdmin\product\controller\Parts');
    // 配件禁用
    $router->post('parts/disable', '\catchAdmin\product\controller\Parts@disable');
    // 配件启用
    $router->post('parts/enable', '\catchAdmin\product\controller\Parts@enable');
    // 配件导出
    $router->post('parts/export', '\catchAdmin\product\controller\Parts@export');
    // 商品配件列表
    $router->get('parts/partListProduct/<id>', '\catchAdmin\product\controller\Parts@partListProduct');
    // 导入配件 
    $router->post('part/importPart', '\catchAdmin\product\controller\Parts@importPart');
    // 导入配件模板 
    $router->get('part/template', '\catchAdmin\product\controller\Parts@template');
})->middleware('auth');
