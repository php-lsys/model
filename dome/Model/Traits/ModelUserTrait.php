<?php
namespace Model\Traits;
/**
 * @method \Model\EntityUser find()
 * @method \LSYS\Entity\Result|\Model\EntityUser[] findAll()
 */
trait ModelUserTrait {
    use \LSYS\Model\Traits\ModelTableColumnsFromFactory;
    public function tableColumnsFactory(){
        return new \LSYS\Entity\ColumnSet([
            (new \LSYS\Entity\Column("id"))
        ]);
    }
    public function primaryKey() {
        return "id";
    }
    public function entityClass()
    {
        return \Model\EntityUser::class;
    }
    public function tableName()
    {
        return "address";
    }
}
    