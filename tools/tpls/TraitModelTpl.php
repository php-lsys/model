<?php
/*__LSYS_TPL_P_AUTO_NAMESPACE__*/
/*__LSYS_TPL_DOC__*/
/**
 * @method entity1 find()
 * @method \LSYS\Entity\Result|__LSYS_TPL_ENTITY__[] findAll()
 */
trait __LSYS_TPL_AUTO_MODEL__ {
    use \LSYS\Model\Traits\ModelTableColumnsFromFactory;
    public function tableColumnsFactory(){
        return new \LSYS\Entity\ColumnSet([
            /*__LSYS_TPL_COLUMNS__*/
        ]);
    }
    public function primaryKey() {
        return /*__LSYS_TPL_PK__*/;
    }
    public function entityClass()
    {
        return /*__LSYS_TPL_ENTITY_NAME__*/;
    }
    public function tableName()
    {
        return /*__LSYS_TPL_TABLE_NAME__*/;
    }
}
