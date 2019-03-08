<?php
use LSYS\Config\File;
use LSYS\DI\SingletonCallback;
include_once __DIR__."/../vendor/autoload.php";
File::dirs(array(
    __DIR__."/config",
));
//配置依赖
\LSYS\Model\DI::set(function(){
    return (new \LSYS\Model\DI())
    ->modelDB(new SingletonCallback(function(){
        return new \LSYS\Model\Database\Swoole\Mysql();
    }))
    ->modelI18n(new SingletonCallback(function(){
        return new \LSYS\Model\I18n\GetText("./i18n");
    }));
});