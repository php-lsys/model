<?php
namespace LSYS\Model\Database;
/**
 * @method 
 */
class ColumnSet{
    protected $_column_set;
    protected $_primary_key;
    public function __construct(\LSYS\Entity\ColumnSet $column_set,$primary_key) {
        $this->_column_set=$column_set;
        $this->_primary_key=$primary_key;
    }
    public function columnSet() {
        return $this->_column_set;
    }
    public function primaryKey() {
        return $this->_primary_key;
    }
}