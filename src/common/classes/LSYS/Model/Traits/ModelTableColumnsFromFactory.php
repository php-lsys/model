<?php
namespace LSYS\Model\Traits;
use LSYS\Entity\ColumnSet;
/**
 * 手动构造字段集对象
 */
trait ModelTableColumnsFromFactory{
    private static $_table_columns_code;
    /**
     * 自定义表字段列表,请重写此方法
     * @return \LSYS\Entity\ColumnSet
     */
    private function tableColumnsFactory(){
        return new ColumnSet([]);
    }
    /**
     * 返回表字段集对象
     * @return \LSYS\Entity\ColumnSet
     */
    public function tableColumns(){
        if (!isset(self::$_table_columns_code)){
            self::$_table_columns_code=$this->tableColumnsFactory();
        }
        return self::$_table_columns_code;
    }
}