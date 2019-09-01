<?php
namespace LSYS\Model\Database;
use LSYS\Database\DI;
use LSYS\Model\Database\Database\Result;
use LSYS\Entity\Exception;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class Database implements \LSYS\Model\Database {
    protected $_db;
    protected $_use_found_rows=0;
    protected $_is_mysql=0;
    protected $mode=0;
    public function __construct(\LSYS\Database $db=null){
        $this->_db=$db?$db:DI::get()->db();
        $db=$this->_db;
        $cls=array(\LSYS\Database\MYSQLi::class,\LSYS\Database\PDO\MYSQL::class);
        $clss=array(get_class($db));
        while($db){
            $db=get_parent_class($db);
            if($db)$clss[]=$db;
        }
        $this->_is_mysql=count(array_intersect($cls, $clss))>0;
    }
    public function foundRows() {
        if($this->_is_mysql)$this->_use_found_rows=1;
        return $this;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::queryMode()
     */
    public function queryMode($mode){
        $this->mode=$mode;
        return $this;
    }
    public function query($sql,array $data=[])
    {
        $sql=ltrim($sql);
        if ($this->_is_mysql&&$this->_use_found_rows==1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS',6,0);
            $this->_use_found_rows=2;
        }
        if(in_array($this->mode, [\LSYS\Model\Database::QUERY_MASTER_ALL,\LSYS\Model\Database::QUERY_MASTER_ONCE])){
            $this->_db->setQuery(\LSYS\Database::QUERY_MASTER);
        }else{
            $this->_db->setQuery(\LSYS\Database::QUERY_AUTO);
        }
        try{
            $res=$this->_db->prepare(\LSYS\Database::DQL, $sql)->execute($data);
        }catch (\Exception $_e){
            if($this->mode==\LSYS\Model\Database::QUERY_MASTER_ONCE){
                $this->_db->setQuery(\LSYS\Database::QUERY_AUTO);
                $this->mode=\LSYS\Model\Database::QUERY_AUTO;
            }
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
        if($this->mode==\LSYS\Model\Database::QUERY_MASTER_ONCE){
            $this->_db->setQuery(\LSYS\Database::QUERY_AUTO);
            $this->mode=\LSYS\Model\Database::QUERY_AUTO;
        }
        return new Result($res);
    }
    public function queryCount($sql,array $data=[],$total_column='total')
    {
        if ($this->_is_mysql&&$this->_use_found_rows==2) {
            $sql="select FOUND_ROWS() as ".addslashes($total_column);
            $this->_use_found_rows=0;
        }
        try{
            $row=$this->_db->prepare(\LSYS\Database::DQL, $sql)->execute($data);
        }catch (\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
        return intval($row->get($total_column,0));
    }
    public function exec($sql,array $data=[])
    {
        try{
            return $this->_db->prepare(\LSYS\Database::DML, $sql)->execute($data);
        }catch (\LSYS\Database\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
    }
    public function listColumns($table)
    {
        $columns=[];
        $pk=[];
        foreach ($this->_db->listColumns($table) as $key=>$value) {
            $column=new Column($key);
            if($value['key']=='PRI')$pk[]=$key;
            if(isset($value['is_nullable'])&&$value['is_nullable'])$column->setAllowNull(1);
            if(isset($value['column_default']))$column->setDefault($value['column_default']);
            if(isset($value['type']))$column->setType($value['type']);
            if(isset($value['comment']))$column->setComment(trim($value['comment']));
            $columns[]=$column;
        }
        return new \LSYS\Model\Database\ColumnSet(new ColumnSet($columns), count($pk)==1?array_shift($pk):$pk);
    }
    public function insertId()
    {
        return $this->_db->insertId();
    }
    public function quoteColumn($column)
    {
        if ($column instanceof Expr) {
            $column=new \LSYS\Database\Expr($column->value());
        }
        return $this->_db->quoteColumn($column);
    }
    public function quoteTable($table)
    {
        if ($table instanceof Expr) {
            $table=new \LSYS\Database\Expr($table->value());
        }
       return $this->_db->quoteTable($table);
    }
    public function quoteValue($value, $column_type=null)
    {
        if ($value instanceof Expr) {
            $value=new \LSYS\Database\Expr($value->value());
        }
        return $this->_db->quote($value);
    }
    public function lastQuery()
    {
        return $this->_db->lastQuery();
    }
    public function affectedRows()
    {
        return $this->_db->affectedRows();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::beginTransaction()
     */
    public function beginTransaction(){
		if($this->inTransaction()) return true;
        return $this->_db->beginTransaction();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::inTransaction()
     */
    public function inTransaction(){
        return $this->_db->inTransaction();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::rollback()
     */
    public function rollback(){
        return $this->_db->rollback();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::commit()
     */
    public function commit(){
        return $this->_db->commit();
    }
    public function release() {
        if ($this->inTransaction()) {
            $this->rollback();
        }
        if($this->_db)$this->_db->disconnect();
    }
}
