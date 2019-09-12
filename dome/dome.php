<?php
use Model\ModelUser;
use Model\EntityUser;
include_once __DIR__."/boot.php";
//!!!!注意: 使用 model前必须配置数据库连接 配置方法 参阅 boot.php 文件



//开箱即用方式
$tm=new \LSYS\Model\Table("address");
var_dump($tm->where("id", "=", 10)->find()->asArray());


//建议不要使用 LSYS\Model\Table 方式操作数据库
//已提供 src/tools 用于把数据库表直接生成出model代码
// 生成代码的使用 请参阅 dome/tools 目录下的文件
//如果你想手动写 model ,请参阅 Model 目录的方式进行手写 model 类文件

//model 创建好后,以下为使用方法:


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


//事务
$e=new EntityUser();
$e->name="fasdf".rand(0,10000);
$e1=new EntityUser();
$e1->name="fasdf".rand(0,10000);
$db=$e->table()->db();
$e1->table()->db($db);
$db->beginTransaction();
try{
    $e->save();
    $e1->save();
    $db->commit();
}catch (Exception $e){
    $db->rollback();
}