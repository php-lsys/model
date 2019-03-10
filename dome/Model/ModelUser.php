<?php
namespace Model;
class ModelUser extends \LSYS\Model{
    //use \Model\Traits\ModelUserTrait;
    use \LSYS\Model\Traits\ModelTableColumnsFromDB;
    public function entityClass()
    {
        return \Model\EntityUser::class;
    }
    public function tableName()
    {
        return "address";
    }
    public function hasOne() {
        return [
            'orm1'=>[
                'model'=>__CLASS__,
                'foreign_key'=>'code'
            ]
        ];
    }
    public function belongsTo() {
        return [
            'orm2'=>[
                'model'=>__CLASS__,
                'foreign_key'=>'code'
            ]
        ];
    }
    public function hasMany() {
        return [
            'orm3'=>[
                'model'=>__CLASS__,
                'foreign_key'=>'code'
            ],
            'orm4'=>[
                'model'=>__CLASS__,
                'through'=>'l_order',
                'far_key'=>'id',
                'foreign_key'=>'sn'
            ]
        ];
    }
}