<?php
return array(
    "mysql"=>array(
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'password' => '',
        'fetch_mode' 		=> 1,
        'database' => 'test',
        "sleep"=>1,//在当前模块用到[model]
        "table_prefix"=>'l_',//在当前模块用到[model]
        //'charset' => 'utf8',//字符编码
     ),
    "mysql_pool"=>array(
        "try"=>true,//发送错误重试次数,设置为TRUE为不限制
        "sleep"=>1,//断开连接重连暂停时间
        "table_prefix"=>'l_',//在当前模块用到[model]
        "master"=>array(
            "size"=>1,//队列长度
			//设置下面两个会清理释放空闲链接
			//"keep_size"=>1,//空闲时保留链接数量
			//"keep_time"=>300,//空闲超过300关闭链接
            "weight"=>1,//权重
            "connection"=>array(//这里配置根据每个连接不同自定义.这里是MYSQL配置
                //'charset' => 'utf8',//字符编码
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'password' => '',
                'fetch_mode' 		=> 1,
                'database' => 'test',
            )
        ),
        "slave1"=>array(
            "size"=>1,//队列长度
            "weight"=>1,//权重
            "connection"=>array(
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'password' => '',
                'fetch_mode' 		=> 1,
                'database' => 'test',
            )
        ),
        "slave2"=>array(
            "size"=>1,//队列长度
            "weight"=>1,//权重
            "connection"=>array(
                'host' => '127.0.0.1',
                'port' => 3306,
                'user' => 'root',
                'password' => '',
                'fetch_mode' 		=> 1,
                'database' => 'test',
            )
        ),
    ),
);
