<?php
// 应用公共文件
function writeLog($fileName, $message, $level = 200)
{
    if (!is_string($message)) {
        $message = json_encode($message);
    }

    $log = new \Monolog\Logger($fileName);
    $log->pushHandler(new \Monolog\Handler\StreamHandler(runtime_path("log") . "{$fileName}.log", \Monolog\Logger::INFO));
    $log->addRecord($level, $message);
}

