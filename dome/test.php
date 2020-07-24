<?php
use Model\ModelUser;
use Model\EntityUser;
use LSYS\Model\Database\Builder;
include_once __DIR__."/boot.php";

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
