<?php
namespace LSYS\Model\Traits;
trait ModelDatabaseBuilder{
     /**
      * 不使用静态,防止model对象多个时覆盖问题
     * @var \LSYS\Model\Database\Builder
     */
    private $_db_builder;
    /**
     * {@inheritDoc}
     * @return \LSYS\Model\Database\Builder
     */
    public function dbBuilder() {
        $table_name=$this->tableName();
        if (!isset($this->_db_builder[$table_name])){
            $this->_db_builder[$table_name]=new \LSYS\Model\Database\Builder($this);
        }
        return $this->_db_builder[$table_name];
    }
}