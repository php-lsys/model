<?php
namespace Model;

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
            ->addHasOne('orm1', __CLASS__, 'code')
            ->addBelongsTo('orm2', __CLASS__, 'code')
            ->addHasMany('orm3', __CLASS__, 'code')
            ->addThroughHasMany('orm4', __CLASS__,['user','t'],'id','code')
        ;
    }
    public function dataList() {
        return $this->dbBuilder()->where("id",">=", 1)->findAll();
    }
}