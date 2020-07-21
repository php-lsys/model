<?php
namespace Model\Traits;
/**
 * @property int $id	
 * @property string $code	
 * @property string $name	
 * @property int $add_time	
 * @property-read \Model\EntityUser $orm2 define from BelongsTo
 * @property-read \Model\EntityUser $orm1 define from hasOne
 * @property-read \LSYS\Model\EntitySet|\Model\EntityUser[] $orm3 define from HasMany
 * @property-read \LSYS\Model\EntitySet|\Model\EntityUser[] $orm4 define from HasMany
 * @method \Model\ModelUser table() return \Model\ModelUser object
*/
trait EntityUserTrait{
    public function tableClass():string
    {
        RETURN \Model\ModelUser::class;
    }
}