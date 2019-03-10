<?php
namespace Model;
use LSYS\Model\Traits\EntityRelatedMethods;
use Model\Traits\EntityUserTrait;
use LSYS\Model\Entity;
/**
 * @method EntityUser orm1(); 在 hasOne 定义
 * @method EntityUser orm2();　在 belongsTo 定义
 * @method ModelUser orm3();　在 hasMany 定义
 * @method ModelUser orm4();　在 hasMany 定义
 */
class EntityUser extends Entity{
    use EntityUserTrait;
    use EntityRelatedMethods;
}
