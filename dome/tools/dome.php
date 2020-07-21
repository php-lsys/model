<?php
use LSYS\Config\File;
$load=include_once __DIR__."/../../vendor/autoload.php";
$load->setPsr4("Model\\", dirname(__DIR__)."/Model");
include_once __DIR__."/ModelBuild.php";
File::dirs(array(
    dirname(__DIR__)."/config",
));
//执行此文件生成MODEL,配置参见 ModelBuild.php
(new DomeModelBuild())->build();
