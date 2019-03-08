<?php
namespace Model;
use LSYS\Entity;
use LSYS\Model\Traits\EntityRelatedMethods;
/**
 * @method entity1 orm1();
 */
class EntityUser extends Entity{
    use \Model\Traits\EntityUser;
    use EntityRelatedMethods;
}
