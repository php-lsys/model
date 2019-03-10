<?php
use Model\ModelUser;
use Model\EntityUser;
include_once __DIR__."/boot.php";


//开箱即用方式
$tm=new \LSYS\Model\Table("address");
var_dump($tm->where("id", "=", 10)->find()->asArray());

//预先配置
$e=new EntityUser();
$e->name="fasdf".rand(0,10000);
$e->save();
print_r($e->asArray());
$orm=new ModelUser();
$entity=$orm->wherePk(1)->find();
print_r($entity->orm1()->asarray());
print_r($entity->orm2()->asarray());
print_r($entity->orm3()->findall()->asarray());
$t=$entity->orm4();
print_r($t->findall()->asarray());
print_r($t->countall());
print_r($entity->asArray());
$orm->db()->foundRows();
print_r($orm->reset()->where("id", ">", 30)->findAll()->asArray());
print_r($orm->countAll());

