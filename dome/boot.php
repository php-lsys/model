<?php
use LSYS\Config\File;
use LSYS\DI\SingletonCallback;
use LSYS\Database;
$loader=include_once __DIR__."/../vendor/autoload.php";
$loader->setPsr4("", "./");
File::dirs(array(
    __DIR__."/config",
));
//这里配置model 使用的数据库连接方式
\LSYS\Model\DI::set(function(){
    return (new \LSYS\Model\DI())
        ->modelDB(new SingletonCallback(function(){
            //使用以下数据库连接 请先引入 lsys/model-db-database 和 lsys/db-mysqli 两个库
            $db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));
            return new \LSYS\Model\Database\Database($db);//默认不传 $db 将从Database的DI中获取数据库对象
        }));
});