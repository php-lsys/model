<?php
namespace Model;
use LSYS\Model;
use Model\Traits\ModelUserTrait;
class ModelUser extends Model{
    use ModelUserTrait;
    public function hasOne() {
        return [
            'orm1'=>[
                'model'=>__CLASS__,
                'foreign_key'=>'uid'
            ]
        ];
    }
}