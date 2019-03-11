<?php
namespace LSYS\Model\Traits;
trait ModelTableColumnsFromDB{
    /**
	 * @var \LSYS\Model\Database\ColumnSet
	 */
	private static $_table_columns_cache;
	private function _tableColumns(){
	    $table_name=$this->tableName();
	    if (!isset(self::$_table_columns_cache[$table_name])){
	        $db=$this->db();
	        assert($db instanceof \LSYS\Model\Database);
	        self::$_table_columns_cache[$table_name]=$db->listColumns($db->quoteTable($table_name));
	    }
	    return self::$_table_columns_cache[$table_name];
	}
	public function tableColumns(){
	    return $this->_tableColumns()->columnSet();
	}
	public function primaryKey() {
	    return $this->_tableColumns()->primaryKey();
	}
}