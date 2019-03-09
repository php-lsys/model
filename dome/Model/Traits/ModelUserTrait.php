<?php
namespace Model\Traits;
/**
 * @method entity1 find($columns=null)
 * @method \LSYS\Entity\Result|entity1[] findAll($columns=null)
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
    