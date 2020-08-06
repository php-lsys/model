<?php
use Model\ModelUser;
use Model\EntityUser;
use LSYS\Model\Database\Builder;
include_once __DIR__."/boot.php";

//!!!!注意: 使用 model前必须配置数据库连接 配置方法 参阅 boot.php 文件


// //开箱即用方式
$tm=new \LSYS\Model\Table("user");
// //强制在从库查询一次
// //$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ONCE);
// //强制查询都在从库查询
// //$tm->db()->queryMode(\LSYS\Model\Database::QUERY_SLAVE_ALL);
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
$orm=new ModelUser();

$entity=$orm->dbBuilder()->find();
print_r($entity->self_mail->asArray());
print_r($entity->mail_one->asArray());

$entity->table()->related()->setBuilderCallback('mail_all',function (Builder $builder,callable $parent) {
    $builder->offset(0)->limit(2);
    $parent($builder);
});
$entity->table()->related()->setBuilderCallback('mail_alls',function (Builder $builder,callable  $parent) {
    $builder->offset(0)->limit(2);
    $parent($builder);
});
print_r($entity->mail_all->asArray());
print_r($entity->mail_alls->asArray());


$entity->table()->related()->setBuilderCallback('mail_all',function (Builder $builder,callable  $parent) {
    $parent($builder);
    $builder->limit(null);
});
$entity->table()->related()->setBuilderCallback('mail_alls',function (Builder $builder,callable  $parent) {
    $parent($builder);
    $builder->limit(null);
});

$res=$orm->dbBuilder()->limit(100)->findAll();
$res->setPreload('mail_all','self_mail','mail_one','mail_alls');

foreach ($res as $entity) {
   print_r($entity->self_mail->asArray());
   print_r($entity->mail_one->asArray());
    print_r($entity->mail_alls->asArray());
    print_r($entity->mail_all->asArray());
}
print_r($res->asarray());
print_r($res->fetchCount());
print_r($entity->asArray());
$orm->db()->foundRows();
print_r($orm->dbBuilder()->reset()->where("id", ">", 30)->limit(3)->findAll()->asArray());
print_r($orm->dbBuilder()->countAll());


//事务
$e=new EntityUser();


$e->name="fasdf".rand(0,10000);
$e1=new EntityUser();
$e1->name="fasdf".rand(0,10000);
$db=$e1->table()->db();
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
$tm->dbBuilder()->update($b[0],$res)->exec();

//批量删除
$tm->dbBuilder()->delete($tm->db()->expr("name=:bb",[":bb"=>"ddd"]))->exec();


echo PHP_EOL.\LSYS\Profiler\Utils::render();