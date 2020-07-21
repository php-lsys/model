<?php
namespace LSYS\Model\Traits;
/**
 * 解析表字段生成字段集对象
 */
trait ModelTableColumnsFromDB{
    /**
	 * @var \LSYS\Model\Database\ColumnSet[]
	 */
	private static $_table_columns_cache;
	/**
	 * 从表中解析字段数据 
	 * @return \LSYS\Model\Database\ColumnSet
	 */
	private function _tableColumns(){
	    $table_name=$this->tableName();
	    if (!isset(self::$_table_columns_cache[$table_name])){
	        $db=$this->db();
	        assert($db instanceof \LSYS\Model\Database);
	        self::$_table_columns_cache[$table_name]=$db->listColumns($db->quoteTable($table_name));
	    }
	    return self::$_table_columns_cache[$table_name];
	}
	/**
	 * 表字段集合
	 * @return \LSYS\Model\Database\ColumnSet
	 */
	public function tableColumns(){
	    return $this->_tableColumns()->columnSet();
	}
	/**
	 * 主键字段
	 * @return string|array
	 */
	public function primaryKey() {
	    return $this->_tableColumns()->primaryKey();
	}
}