<?php
namespace LSYS\Model\Traits;
use LSYS\Entity\ColumnSet;
trait ModelTableColumnsFromFactory{
    private static $_table_columns_code;
    public function tableColumnsFactory(){
        return new ColumnSet([]);
    }
    public function tableColumns(){
        if (!isset(self::$_table_columns_code)){
            self::$_table_columns_code=$this->tableColumnsFactory();
        }
        return self::$_table_columns_code;
    }
}