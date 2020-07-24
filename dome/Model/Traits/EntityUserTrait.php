<?php
namespace Model\Traits;
/**
 * @property int $id	
 * @property string $code	
 * @property string $name	
 * @property int $add_time	
 * @property int $email_id	
 * @property-read \Model\EntityEmail $self_mail define from BelongsTo
 * @property-read \Model\EntityEmail $mail_one define from hasOne
 * @property-read \LSYS\Model\EntitySet|\Model\EntityEmail[] $mail_all define from HasMany
 * @property-read \LSYS\Model\EntitySet|\Model\EntityUser[] $mail_alls define from HasMany
 * @method \Model\ModelUser table() return \Model\ModelUser object
*/
trait EntityUserTrait{
    public function tableClass():string
    {
        RETURN \Model\ModelUser::class;
    }
}