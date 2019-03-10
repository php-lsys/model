<?php
use Model\ModelUser;
use Model\EntityUser;
include_once __DIR__."/boot.php";
$e=new EntityUser();
$e->name="fasdf".rand(0,10000);
$e->save();
print_r($e->asArray());
$orm=new ModelUser();
$entity=$orm->wherePk(1)->find();
print_r($entity->asArray());
$orm->db()->foundRows();
print_r($orm->reset()->where("id", ">", 30)->findAll()->asArray());
print_r($orm->countAll());


//$tm=new \LSYS\Model\Table("user");