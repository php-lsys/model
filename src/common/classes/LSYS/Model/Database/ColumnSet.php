<?php
namespace LSYS\Model\Database;
class ColumnSet{
    protected $_column_set;
    protected $_primary_key;
    /**
     * 模型表字段集对象
     * @param \LSYS\Entity\ColumnSet $column_set
     * @param array|string $primary_key
     */
    public function __construct(\LSYS\Entity\ColumnSet $column_set,$primary_key) {
        $this->_column_set=$column_set;
        $this->_primary_key=$primary_key;
    }
    /**
     * 返回实体字段集
     * @return \LSYS\Entity\ColumnSet
     */
    public function columnSet() {
        return $this->_column_set;
    }
    /**
     * 返回表主键列表
     * @return string|array
     */
    public function primaryKey() {
        return $this->_primary_key;
    }
}