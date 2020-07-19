<?php
namespace LSYS\Model;
class Table extends \LSYS\Model{
    use \LSYS\Model\Traits\ModelTableColumnsFromDB;
    protected $_table_name;
    public function __construct(string $table_name){
        $this->_table_name=$table_name;
    }
    public function tableName():string
    {
        return $this->_table_name;
    }
}