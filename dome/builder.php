<?php
use LSYS\DI\SingletonCallback;
use LSYS\Entity;
use LSYS\EntityBuilder\Model;
use LSYS\EntityBuilder\Entity\RelatedMethods;
include_once __DIR__."/boot.php";

//配置依赖
\LSYS\EntityBuilder\DI::set(function(){
    return (new \LSYS\EntityBuilder\DI())
        ->lsorm_db(new SingletonCallback(function(){
            return new \LSYS\EntityBuilder\Database\Swoole\Mysql();
        }))
        ->lsorm_i18n(new SingletonCallback(function(){
            return new \LSYS\EntityBuilder\I18n\GetText("./i18n");
        }));
});
include_once __DIR__.'/auto_create.php';
class orm1 extends Model{
    use orm11;
    public function hasOne() {
        return [
            'orm1'=>[
                'model'=>orm1::class,
                'foreign_key'=>'uid'
            ]
        ];
    }
    public function belongsTo() {
        return [];
    }
    public function hasMany() {
        return [];
    }
}
/**
 * @method entity1 orm1();
 */
class entity1 extends Entity{
    use RelatedMethods;
    use entity11;
}
$e=new entity1();
$e->is_post;
$e->orm1()->is_post_confirm;

$orm=new orm1();
$entity=$orm->wherePk(1)->find();
print_r($entity->asArray());
//print_r($entity->ggg());

print_r($orm->countAll());

print_r($orm->findAll());