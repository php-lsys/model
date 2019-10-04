<?php
/**
 * lsys database 
 * 配置示例 未引入
 * @author     Lonely <shan.liu@msn.com>
 * @copyright  (c) 2017 Lonely <shan.liu@msn.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 */
return array(
	"mysqli"=>array(
		//PMYSQLi 配置
		"type"=>\LSYS\Database\MYSQLi::class,
		"charset"=>"UTF8",
		"table_prefix"=>"",
		"connection"=>array(
			//单数据库使用此配置
			'database' => 'test',
			'hostname' => '127.0.0.1',
			'username' => 'root',
			'password' => '',
			//下面两参数一般在命令行中运行用到
			'try_re_num' => 0,//连接断开尝试重连次数 -1 不限制,默认为0 不重连
			'try_re_sleep' => 0,//连接断开重连时暂停秒数,默认为:0 最好不要为0 如果mysql出问题为0可能导致连接数用光问题 
			//'port' => '3306',
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	)
);