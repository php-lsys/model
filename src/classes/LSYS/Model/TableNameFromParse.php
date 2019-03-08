<?php
namespace LSYS\EntityBuilder\Model;
trait TableNameFromParse{
    public function tableName(){
        static $table_name;
        if(!$table_name){
            $cls=get_called_class ();
            $table_name=substr($cls,strrpos($cls,"\\")+2);
        }
        return $table_name;
    }
}