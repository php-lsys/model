<?php
namespace Model\Traits;
/**
 * @property int $id	
 * @property int $user_id	
 * @property string $mail	
 * @property int $my_user_id	
 * @method \Model\ModelEmail table() return \Model\ModelEmail object
*/
trait EntityEmailTrait{
    public function tableClass():string
    {
        RETURN \Model\ModelEmail::class;
    }
}