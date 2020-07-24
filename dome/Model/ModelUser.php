<?php
namespace Model;

use LSYS\Model\Database\Builder;

class ModelUser extends \LSYS\Model{
    
    //方式1.通过表生成代码
    use \Model\Traits\ModelUserTrait;
    
    //方式2.运行时从表中解析
   // use \LSYS\Model\Traits\ModelTableColumnsFromDB;
//     //重置字段定义
//     public function tableColumns(){
//         return $this->_tableColumns()
//             ->columnSet()
//             //重设添加记录时自动补充时间的字段
//             //->add((new \LSYS\Model\Column\TimeColumn('add_time'))->setCreate(),true)
//             //重设修改记录时自动补充时间的字段
//             //->add((new \LSYS\Model\Column\TimeColumn('change_time'))->setUpdate(),true)
//             ;
//     }
       
    
    public function entityClass():string
    {
        return \Model\EntityUser::class;
    }
    public function tableName():string
    {
        return "user";
    }
    public function relatedFactory(){
        return (new \LSYS\Model\Related())
            ->addHasOne('mail_one', ModelEmail::class, 'user_id')
            ->addBelongsTo('self_mail', ModelEmail::class, 'email_id')
            ->addHasMany('mail_all', ModelEmail::class, 'my_user_id')
            ->addThroughHasMany('mail_alls', ModelEmail::class,'user_nx','email_id','user_id')
            ->setBuilderCallback('mail_alls',function(Builder $builder,callable $parent){
                $parent($builder);
                $builder->where('user_nx.is_del', '=', "0");
            })
        ;
    }
    public function dataList() {
        return $this->dbBuilder()->where("id",">=", 1)->findAll();
    }
}