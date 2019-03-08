<?php
namespace Model\Traits;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\Column;
/**
 * @method entity1 find($columns=null)
 * @method \LSYS\Entity\Result|entity1[] findAll($columns=null)
 */
trait ModelUser {
    public function tableColumns(){
        if (!$this->_table_columns_cache){
            $table_columns = new ColumnSet(array(
                (new Column("id")),
                (new Column("name")),
                (new Column("code")),
            ));
            $this->_table_columns_cache=new ColumnSet($table_columns);
        }
        return $this->_table_columns_cache;
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
    