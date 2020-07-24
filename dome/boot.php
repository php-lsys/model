<?php
use LSYS\Config\File;
use LSYS\DI\SingletonCallback;
use LSYS\Database;
$loader=include_once __DIR__."/../vendor/autoload.php";
$loader->setPsr4("", "./");
LSYS\Core::sets(array(
    'environment'=>LSYS\Core::DEVELOP
));
File::dirs(array(
    __DIR__."/config",
));
//这里配置model 使用的数据库连接方式
\LSYS\Model\DI::set(function(){
    return (new \LSYS\Model\DI())
        ->modelDB(new SingletonCallback(function(){
            //使用以下数据库连接 请先引入  lsys/db 库
            $db = Database::factory(LSYS\Config\DI::get()->config("database.mysqli"));
            
            $event=\LSYS\EventManager\DI::get()->eventManager();
            $event->attach(new \LSYS\Database\EventManager\ProfilerObserver());
            $db->setEventManager($event);
            
            $mysql=new \LSYS\Model\Database\Database\MYSQL($db);//默认不传 $db 将从Database的DI中获取数据库对象
            return $mysql;
        }))
        ;
});