<?php
namespace LSYS\EntityBuilder\Model;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\Column;
trait TableColumnsFromArray{
//     protected $table_columns=array(
//         array(
//             'name'=>array(0,true,false,'int')
//         )
//     );
    public function tableColumns(){
        static $table_columns;
        if(!$table_columns){
            $table_columns=isset($this->table_columns)?$this->table_columns:[];
            foreach ($table_columns as $k=>$v){
                $default=array_shift($v);
                $set_is_primary_key=array_shift($v);
                $set_allow_nullable=array_shift($v);
                $set_type=array_shift($v);
                $comment=array_shift($v);
                $table_columns[$k]=(new Column($k,$comment))
                    ->setDefault($default)
                    ->setIsPrimaryKey($set_is_primary_key)
                    ->setAllowNullable($set_allow_nullable)
                    ->setType($set_type)
                ;
            }
            $table_columns = new ColumnSet($table_columns);
        }
        return $table_columns;
    }
}