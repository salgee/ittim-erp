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
$router->group(function () use ($router) {
	// platforms路由
	$router->resource('platforms', '\catchAdmin\store\controller\Platforms');
	// 绑定账号
	$router->post('platforms/setAccount/<id>', '\catchAdmin\store\controller\Platforms@setAccount');
})->middleware('auth');
