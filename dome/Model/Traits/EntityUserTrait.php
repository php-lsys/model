<?php
namespace Model\Traits;
/**
 * @property int $id ID
 * @property int $name
 * @property int $code
 * @method \Model\ModelUser table()
 */
trait EntityUserTrait {
    public function tableClass()
    {
        return \Model\ModelUser::class;
    }
}