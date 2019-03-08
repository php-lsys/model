<?php
namespace Model\Traits;
/**
 * @property int $id ID
 * @property int $name
 * @property int $code
 * @method orm1 table()
 */
trait EntityUser {
    public function tableClass()
    {
        return \Model\ModelUser::class;
    }
}