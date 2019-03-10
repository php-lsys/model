<?php
namespace Model;
use LSYS\Model;
use Model\Traits\ModelUserTrait;
class ModelUser extends Model{
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
                'foreign_key'=>'uid'
            ]
        ];
    }
}