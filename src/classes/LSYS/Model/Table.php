<?php
namespace LSYS\Model;
use LSYS\Model\Traits\ModelTableColumnsFromDB;
use LSYS\Model;
class Table extends Model{
    use ModelTableColumnsFromDB;
    protected $_table_name;
    public function __construct($table_name){
        $this->_table_name=$table_name;
    }
    public function tableName()
    {
        return $this->_table_name;
    }
}