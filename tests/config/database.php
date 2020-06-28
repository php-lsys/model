<?php
return array(
	"mysqli"=>array(
		"type"=>\LSYS\Database\MYSQLi::class,
		"charset"=>"UTF8",
		"table_prefix"=>"l_",
		"connection"=>array(
			'database' => 'test',
			'hostname' => '127.0.0.1',
			'username' => 'root',
			'password' => '',
		    "try_re_num"=>"2",
		    "try_re_sleep"=>"1",
			'persistent' => FALSE,
			"variables"=>array(
			),
		),
	    'slave_connection'=>array(
	        array(
	            'database' => 'test',
	            'hostname' => '127.0.0.1',
	            'username' => 'root',
	            'password' => '',
	            'persistent' => FALSE,
	            "variables"=>array(
	            ),
	        )
	    ),
	),
);