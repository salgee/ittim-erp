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

namespace catchAdmin\basics;

use catcher\ModuleService;

class BasicsService extends ModuleService
{
    public function loadRouteFrom()
    {
        // TODO: Implement loadRouteFrom() method.
        return __DIR__ . DIRECTORY_SEPARATOR . 'route.php';
    }
}
