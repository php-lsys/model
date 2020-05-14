<?php
use Model\ModelUser;
use Model\EntityUser;
include_once __DIR__."/boot.php";
//!!!!注意: 使用 model前必须配置数据库连接 配置方法 参阅 boot.php 文件


// CREATE TABLE `user` (
//     `id` int(11) NOT NULL AUTO_INCREMENT,
//     `name` varchar(100) DEFAULT NULL,
//     `add_time` int(11) DEFAULT NULL,
//     `code` varchar(100) DEFAULT NULL,
//     PRIMARY KEY (`id`)
//     ) ENGINE=InnoDB DEFAULT CHARSET=utf8

//开箱即用方式
$tm=new \LSYS\Model\Table("user");
//强制在从库查询一次
//$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ONCE);
//强制查询都在从库查询
//$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ALL);
var_dump($tm->dbBuilder()->where("id", "=", 10)->find()->asArray());



//建议不要使用 LSYS\Model\Table 方式操作数据库
//已提供 src/tools 用于把数据库表直接生成出model代码
// 生成代码的使用 请参阅 dome/tools 目录下的文件
//如果你想手动写 model ,请参阅 Model 目录的方式进行手写 model 类文件

//model 创建好后,以下为使用方法:

$e=new EntityUser();

//单记录操作
$e->name="fasdf".rand(0,10000);
$e->save();
print_r($e->asArray());
$orm=ModelUser::factory();

$entity=$orm->dbBuilder()->wherePk(1)->find();
print_r($entity->orm1()->asarray());
print_r($entity->orm2()->asarray());
print_r($entity->orm3()->findall()->asarray());
// $t=$entity->orm4();
// print_r($t->findall()->asarray());
// print_r($t->countall());
print_r($entity->asArray());
$orm->db()->foundRows();
print_r($orm->dbBuilder()->reset()->where("id", ">", 30)->findAll()->asArray());
print_r($orm->dbBuilder()->countAll());


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
}catch (Exception $err){
    $db->rollback();
}

//批量操作

//批量插入
$data=[];
$b=array(["name"=>"bbb"],["name"=>"ddd"]);
$e->clear();
foreach ($b as $bb){
    $data[]=$e->values($bb)->check()->createData();
}
$e->table()->dbBuilder()->insert($data)->exec();
//未查找记录批量更新
$tm->dbBuilder()->update(array(
    'name'=>'11'
),$tm->db()->expr("id=:id",[":id"=>"1"]))->exec();
//查找记录后批量更新
$res=$e->table()->dbBuilder()->findAll();
foreach ($res as $bb){
    $bb->values($b[0])->check();
}
$tm->dbBuilder()->update($res->current()->updateData(),$res)->exec();

//批量删除
$tm->dbBuilder()->delete($tm->db()->expr("name=:bb",[":bb"=>"ddd"]))->exec();