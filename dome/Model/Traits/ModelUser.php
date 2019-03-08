<?php
namespace Model\Traits;
use LSYS\Entity\ColumnSet;
/**
 * @method entity1 find($columns=null)
 * @method \LSYS\Entity\Result|entity1[] findAll($columns=null)
 */
trait ModelUser {
public function tableColumns(){
    if (!$this->_table_columns_cache){
        $column=array();
        //             $table_columns=isset($this->table_columns)?$this->table_columns:[];
        //             foreach ($table_columns as $k=>$v){
        //                 $default=array_shift($v);
        //                 $set_is_primary_key=array_shift($v);
        //                 $set_allow_nullable=array_shift($v);
        //                 $set_type=array_shift($v);
        //                 $comment=array_shift($v);
        //                 $table_columns[$k]=(new Column($k,$comment))
        //                 ->setDefault($default)
        //                 ->setIsPrimaryKey($set_is_primary_key)
        //                 ->setAllowNullable($set_allow_nullable)
        //                 ->setType($set_type)
        //                 ;
        //             }
        $table_columns = new ColumnSet($table_columns);
        $this->_table_columns_cache=new ColumnSet($column);
        }
        return $this->_table_columns_cache;
    }
    public function entityClass()
    {
        return \Model\EntityUser::class;
    }
    public function tableName()
    {
        return "test";
    }
}
    