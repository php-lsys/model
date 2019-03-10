<?php
use LSYS\Config\File;
use LSYS\DI\SingletonCallback;
$loader=include_once __DIR__."/../vendor/autoload.php";
$loader->setPsr4("", "./");
File::dirs(array(
    __DIR__."/config",
));
//配置依赖
\LSYS\Model\DI::set(function(){
    return (new \LSYS\Model\DI())
        ->modelDB(new SingletonCallback(function(){
            return new \LSYS\Model\Database\MYSQLi();
        }));
});