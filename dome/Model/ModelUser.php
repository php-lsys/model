<?php
namespace Model;
use LSYS\Model;
class ModelUser extends Model{
    use \Model\Traits\ModelUser;
    public function hasOne() {
        return [
            'orm1'=>[
                'model'=>__CLASS__,
                'foreign_key'=>'uid'
            ]
        ];
    }
}