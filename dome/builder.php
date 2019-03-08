<?php
use Model\ModelUser;
use Model\EntityUser;
include_once __DIR__."/boot.php";
$e=new EntityUser();
$orm=new ModelUser();
$entity=$orm->wherePk(1)->find();
print_r($entity->asArray());
print_r($orm->countAll());
print_r($orm->findAll());