<?php
namespace LSYS\Model\Database;
use LSYS\Database\DI;
use LSYS\Model\Database\Database\Result;
use LSYS\Model\Exception;
use LSYS\Entity\Table;
use LSYS\Database\ConnectSlave;
use LSYS\Database\ConnectMaster;
abstract class Database implements \LSYS\Model\Database {
    /**
     * @var \LSYS\Database
     */
    protected $_db;
    protected $_use_found_rows=0;
    protected $_is_mysql=0;
    protected $mode=0;
    /**
     * @var ConnectSlave
     */
    protected $last_slave_connect;
    /**
     * @var ConnectMaster
     */
    protected $last_master_connect;
    /**
     * 
     * @var ConnectSlave|ConnectMaster
     */
    protected $last_connect;
    public function __construct(\LSYS\Database $db=null){
        $this->_db=$db?$db:DI::get()->db();
        $db=$this->_db;
        $cls=array(\LSYS\Database\MYSQLi::class,\LSYS\Database\MYSQLPDO::class);
        $clss=array(get_class($db));
        while($db){
            $db=get_parent_class($db);
            if($db)$clss[]=$db;
        }
        $this->_is_mysql=count(array_intersect($cls, $clss))>0;
    }
    protected function getMasterConnect() {
        if (!$this->last_master_connect) {
            $this->last_master_connect=$this->_db->getMasterConnect();
        }
        return $this->last_master_connect;
    }
    /**
     * {@inheritDoc}
     * @return \LSYS\Model\Database\Builder
     */
    public function SQLBuilder(Table $table) {
        return new \LSYS\Model\Database\Builder($table);
    }
    /**
     * 使用SQL_CALC_FOUND_ROWS获取行数
     * @return \LSYS\Model\Database\Database
     */
    public function foundRows(){
        if($this->_is_mysql)$this->_use_found_rows=1;
        return $this;
    }
    /**
     * 使用此驱动会自动是否选择将查询派发到从库 
     * @see \LSYS\Model\Database::queryMode()
     */
    public function queryMode(int $mode){
        $this->mode=$mode;
        return $this;
    }
    public function query(string $sql,array $data=[])
    {
        $sql=ltrim($sql);
        if ($this->_is_mysql&&$this->_use_found_rows==1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS',6,0);
            $this->_use_found_rows=2;
        }
        try{
            if(in_array($this->mode, [\LSYS\Model\Database::QUERY_SLAVE_ALL,\LSYS\Model\Database::QUERY_SLAVE_ONCE])){
                $this->last_slave_connect=$this->_db->getSlaveConnect();
            }else{
                $this->last_slave_connect=$this->getMasterConnect();
            }
            $res=$this->last_slave_connect->query($sql,$data);
            $this->last_connect=$this->last_slave_connect;
        }catch (\Exception $_e){
            if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
                $this->mode=\LSYS\Model\Database::QUERY_MUST_MASTER;
            }
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
        
        if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
            $this->mode=\LSYS\Model\Database::QUERY_MUST_MASTER;
        }
        return new Result($res);
    }
    public function queryCount(string $sql,array $data=[],string $total_column='total'):int
    {
        if ($this->_is_mysql&&$this->_use_found_rows==2) {
            $sql="select FOUND_ROWS() as ".addslashes($total_column);
            $this->_use_found_rows=0;
        }
        if(!$this->last_slave_connect){
            if(in_array($this->mode, [\LSYS\Model\Database::QUERY_SLAVE_ALL,\LSYS\Model\Database::QUERY_SLAVE_ONCE])){
                $this->last_slave_connect=$this->_db->getSlaveConnect();
            }else{
                $this->last_slave_connect=$this->getMasterConnect();
            }
        }
        try{
            $row=$this->last_slave_connect->query($sql,$data);
            $this->last_connect=$this->last_slave_connect;
        }catch (\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
        return intval($row->get($total_column,0));
    }
    public function exec($sql,array $data=[])
    {
        $this->last_master_connect=$this->getMasterConnect();
        $this->last_connect=$this->last_master_connect;
        try{
            return  $this->last_master_connect->exec($sql,$data);
        }catch (\LSYS\Database\Exception $_e){
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
    }
    public function insertId()
    {
        return $this->getMasterConnect()->insertId();
    }
    public function quoteColumn($column)
    {
        if ($column instanceof Expr) {
            $column=new \LSYS\Database\Expr($column->compile($this));
        }
        return $this->_db->getConnect()->quoteColumn($column);
    }
    public function quoteTable($table)
    {
        if ($table instanceof Expr) {
            $table=new \LSYS\Database\Expr($table->compile($this));
        }
       return $this->_db->getConnect()->quoteTable($table);
    }
    public function quoteValue($value, $column_type=null)
    {
        if ($value instanceof Expr) {
            $value=new \LSYS\Database\Expr($value->compile($this));
        }
        return $this->_db->getConnect()->quote($value);
    }
    public function lastQuery():?string
    {
        return $this->last_connect?$this->last_connect->lastQuery():null;
    }
    public function affectedRows():int
    {
        return $this->getMasterConnect()->affectedRows();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::beginTransaction()
     */
    public function beginTransaction():bool{
		if($this->inTransaction()) return true;
        return $this->getMasterConnect()->beginTransaction();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::inTransaction()
     */
    public function inTransaction():bool{
        return $this->getMasterConnect()->inTransaction();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::rollback()
     */
    public function rollback():bool{
        return $this->getMasterConnect()->rollback();
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::commit()
     */
    public function commit():bool{
        return $this->getMasterConnect()->commit();
    }
    public function release():void{
        if ($this->inTransaction()) {
            $this->rollback();
        }
        if($this->_db&&$this->_db->isConnected()){
            $this->_db->disConnect();
        }
    }
    public function expr($value,array $param=[]) {
        return (new \LSYS\Model\Database\Database\Expr($value,$param));
    }
    /**
     * @return \LSYS\Database
     */
    public function getDatabase() {
        return $this->_db;
    }
}
