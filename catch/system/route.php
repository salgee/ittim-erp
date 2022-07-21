<?php
/*
 * @Version: 1.0
 * @Date: 2021-02-23 09:59:14
 * @LastEditTime: 2021-04-24 10:16:43
 * @Description: 
 */
$router->group(function () use ($router) {
    // 登录日志
    $router->get('log/login', '\catchAdmin\system\controller\LoginLog@list');
    $router->delete('log/login/<id>', '\catchAdmin\system\controller\LoginLog@empty');
    // 操作日志
    $router->get('log/operate', '\catchAdmin\system\controller\OperateLog@list');
    // $router->delete('empty/log/operate', '\catchAdmin\system\controller\OperateLog@empty');
    $router->delete('log/operate/<id>', '\catchAdmin\system\controller\OperateLog@delete');

    // 数据结构
    $router->get('tables', '\catchAdmin\system\controller\DataDictionary@tables');
    $router->get('table/view/<table>', '\catchAdmin\system\controller\DataDictionary@view');
    $router->post('table/optimize', '\catchAdmin\system\controller\DataDictionary@optimize');
    $router->post('table/backup', '\catchAdmin\system\controller\DataDictionary@backup');

    // 附件
    $router->resource('attachments', '\catchAdmin\system\controller\Attachments');

    // 配置
    $router->get('config/parent', '\catchAdmin\system\controller\Config@parent');
    $router->resource('config', '\catchAdmin\system\controller\Config');
    // 根据id 获取系统配置项
    $router->get('config/info/<id>', '\catchAdmin\system\controller\Config@info');

    // 代码生成
    $router->post('generate', '\catchAdmin\system\controller\Generate@save');
    $router->post('generate/preview', '\catchAdmin\system\controller\Generate@preview'); // 预览
    $router->post('generate/create/module', '\catchAdmin\system\controller\Generate@createModule'); // 创建模块

    // 敏感词
    $router->resource('sensitive/word', '\catchAdmin\system\controller\SensitiveWord');

    // 字典以及字典数据列表
    $router->get('dictionary/list/withdata', '\catchAdmin\system\controller\Dictionary@listWithData');
    // delDictionary
    $router->post('dictionary/del', '\catchAdmin\system\controller\Dictionary@delData');
    // 数据字典详情
    $router->get('dictionary/data/info/<dictValue>', '\catchAdmin\system\controller\Dictionary@detail');
    // 添加数据字典
    $router->post('dictionary/add/data', '\catchAdmin\system\controller\Dictionary@addDict');
    // 修改数据字典
    $router->put('dictionary/update/data/<dictValue>', '\catchAdmin\system\controller\Dictionary@updateDict');
    //dictionary路由
	$router->resource('dictionary', '\catchAdmin\system\controller\Dictionary')->middleware('auth');
	//dictionaryData路由
    $router->resource('dictionaryData', '\catchAdmin\system\controller\DictionaryData')->middleware('auth');
    // 根据类型查询数据字段
    $router->get('getListType/<id>', '\catchAdmin\system\controller\Dictionary@getListType');

    //developer路由
    $router->resource('developer', '\catchAdmin\system\controller\Developer')->middleware('auth');
    // 开发者认证
    $router->post('developer/authenticate', '\catchAdmin\system\controller\Developer@authenticate');

    // 模块管理
    $router->get('modules', '\catchAdmin\system\controller\Module@index');
    $router->put('modules/<module>', '\catchAdmin\system\controller\Module@disOrEnable');
    $router->put('cache/modules', '\catchAdmin\system\controller\Module@cache');
    $router->delete('clear/modules', '\catchAdmin\system\controller\Module@clear');

	// notice路由
	$router->resource('notice', '\catchAdmin\system\controller\Notice');
    $router->put('notice/publish/<id>', '\catchAdmin\system\controller\Notice@publish');
})->middleware('auth');
// 上传
$router->group('upload', function () use ($router) {
    $router->post('image', '\catchAdmin\system\controller\Upload@image');
    $router->post('file', '\catchAdmin\system\controller\Upload@file');
})->middleware(\catcher\middlewares\JsonResponseMiddleware::class);
