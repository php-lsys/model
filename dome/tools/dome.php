<?php
use LSYS\Config\File;
include_once __DIR__."/../vendor/autoload.php";
include_once __DIR__."/ModelBuild.php";
File::dirs(array(
    __DIR__."/config",
));
//执行此文件生成MODEL,配置参见 ModelBuild.php
(new DomeModelBuild())->build();
