<?php
namespace LSYS\Model\Database\LSYS;
use LSYS\Database\DI;
use LSYS\Database;
use LSYS\Model\Database\LSYS\MYSQLi\Result;
use LSYS\Entity\Exception;
use LSYS\Model\Database\Expr;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class MYSQLi implements \LSYS\Model\Database {
    protected $_db;
    protected $_use_found_rows=0;
    public function __construct(Database $db=null){
        $this->_db=$db?$db:DI::get()->db();
    }
    public function foundRows($use_found=true) {
        if ($use_found)$this->_use_found_rows|=1<<1;
        else $this->_use_found_rows&=~1<<1;
        return $this;
    }
    public function query($sql)
    {
        $sql=ltrim($sql);
        if ($this->_use_found_rows&1<<1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS ',6,0);
            $this->_use_found_rows|=1<<0;
        }
        try{
            $res=$this->_db->query(Database::DQL, $sql);
        }catch (\LSYS\Database\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($_e->get_error_sql());
            throw $e;
        }
        return new Result($res);
    }
    public function queryCount($sql,$total_column='total')
    {
        if ($this->_use_found_rows&1<<1&1<<0) {
            $sql="select sql_found_rows() as ".addslashes($total_column);
        }
        try{
            $row=$this->_db->query(Database::DQL, $sql);
        }catch (\LSYS\Database\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($_e->get_error_sql());
            throw $e;
        }
        return intval($row->get($total_column,0));
    }
    public function exec($sql)
    {
        try{
            return $this->_db->query(Database::DML, $sql);
        }catch (\LSYS\Database\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($_e->get_error_sql());
            throw $e;
        }
    }
    public function listColumns($table)
    {
        $columns=[];
        foreach ($this->_db->list_columns($table) as $key=>$value) {
            $columns[]=(new Column($key))
            ->setDefault($value['Default'])
            ->setAllowNullable($value['Default'])
            ->setType($value['Default'])
            ;
        }
        return new ColumnSet($columns);
    }
    public function insertId()
    {
        $this->_db->insert_id();
    }
    public function quoteColumn($column)
    {
        if ($column instanceof Expr) {
            $column=new \LSYS\Database\Expr($column->value());
        }
        return $this->_db->quote_column($column);
    }
    public function quoteTable($table)
    {
        if ($table instanceof Expr) {
            $table=new \LSYS\Database\Expr($table->value());
        }
        $this->_db->quote_table($table);
    }
    public function quoteValue($value, $column_type)
    {
        if ($value instanceof Expr) {
            $value=new \LSYS\Database\Expr($value->value());
        }
        $this->_db->quote($value);
    }
    public function lastQuery()
    {
        return $this->_db->last_query();
    }
    public function affectedRows()
    {
        return $this->_db->affected_rows();
    }
}
