<?php
namespace Model\Traits;
/**
 * @property int(11) $id	
 * @property varchar(100) $name	
 * @property int(11) $add_time	
 * @property varchar(100) $code	
 * @method \Model\ModelUser table()
*/
trait EntityUserTrait{
    public function tableClass():string
    {
        RETURN \Model\ModelUser::class;
    }
}