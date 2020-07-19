<?php
namespace Model\Traits;
/**
 * @property int(11) $id	
 * @property varchar(100) $name	
 * @property int(11) $add_time	
 * @property varchar(100) $code	
 * @property-read \Model\EntityUser $orm1 在 hasOne 定义
 * @property-read \Model\EntityUser $orm2 在 belongsTo 定义
 * @property-read \LSYS\Model\EntitySet|\Model\EntityUser[] $orm3 在 hasMany 定义
 * @property-read \LSYS\Model\EntitySet|\Model\EntityUser[] $orm4 在 hasMany 定义
 * @method \Model\ModelUser table()
*/
trait EntityUserTrait{
    public function tableClass():string
    {
        RETURN \Model\ModelUser::class;
    }
}