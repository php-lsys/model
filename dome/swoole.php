<?php
use LSYS\DI\SingletonCallback;

require dirname(__DIR__)."/vendor/autoload.php";

//注册MODEL的数据库DI依赖
\LSYS\Model\DI::set(function(){
    return (new \LSYS\Model\DI())
    ->modelDB(new SingletonCallback(function(){
        //协程
        $mysql= new \LSYS\Model\Database\Swoole\MYSQL(function($mysql=null,$is_master=0){
            if($is_master){
                //返回主库连接
                return \LSYS\Swoole\Coroutine\DI::get()->swoole_mysql();
            }else{
                //返回从库连接
                return \LSYS\Swoole\Coroutine\DI::get()->swoole_mysql();
            }
        });
        
        $event=new \LSYS\EventManager();
        $event->attach(new \LSYS\Model\Database\Swoole\EventManager\ProfilerObserver());
            
        $mysql->setEventManager($event);
        return $mysql;
        //协程连接池
        $pool=\LSYS\Swoole\Coroutine\DI::get()->swoole_mysql_pool();
        $mp=new \LSYS\Model\Database\Swoole\MYSQLPool($pool);
        //$mp->queryConfig("master*", "read*"); //设置 主从库的配置,默认都是从master*上取连接
        return $mp;
    }));
});


go(function(){
   
    \LSYS\Profiler\DI::get()->profiler()->appInit();
    
    //model的使用示例 参见 lsys/dome 的示例
   //示例目录model库 dome/dome.php 
    $tm=new \LSYS\Model\Table("l_user");
    //强制在从库查询一次
    //$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ONCE);
    //强制查询都在从库查询
    //$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ALL);
    var_dump($tm->dbBuilder()->where("id", "=", 10)->find()->asArray());
    echo PHP_EOL.\LSYS\Profiler\Utils::render();
});