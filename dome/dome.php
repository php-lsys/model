<?php
use Model\ModelUser;
use Model\EntityUser;
use LSYS\Model\Transaction;
include_once __DIR__."/boot.php";


//开箱即用方式
$tm=new \LSYS\Model\Table("address");
var_dump($tm->where("id", "=", 10)->find()->asArray());

//预先配置,可通过 lsys/model-tools 辅助生成表的对应的Trait
$e=new EntityUser();
$e->name="fasdf".rand(0,10000);
$transaction=new Transaction();
$e->table()->setTransaction($transaction);
$transaction->beginTransaction();
$e->save();
$transaction->commit();
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