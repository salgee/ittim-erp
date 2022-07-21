<?php
declare(strict_types=1);

namespace catcher\exceptions;

use catcher\Code;

class LoginFailedException extends CatchException
{
    protected $code = Code::LOGIN_FAILED;

    protected $message = '登录失败，请检查你的账号和密码！';
}
