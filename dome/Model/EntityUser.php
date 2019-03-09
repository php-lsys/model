<?php
namespace Model;
use LSYS\Model\Traits\EntityRelatedMethods;
use Model\Traits\EntityUserTrait;
use LSYS\Model\Entity;
/**
 * @method entity1 orm1();
 */
class EntityUser extends Entity{
    use EntityUserTrait;
    use EntityRelatedMethods;
}
