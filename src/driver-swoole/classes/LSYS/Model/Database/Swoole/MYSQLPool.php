<?php
namespace LSYS\Model\Database\Swoole;
use LSYS\Model\Exception;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\Table;
use LSYS\Model\Database\Swoole\EventManager\DBEvent;
class MYSQLPool implements \LSYS\Model\Database {
    use MYSQLTrait;
    protected $_pool;
    protected $_use_found_rows=0;
    protected $_query_config;
    protected $_last_query;
    protected $_affected_rows=0;
    protected $_insert_id=null;
    protected $_identifier='`';
    protected $_table_prefix='';
    protected $_in_transaction=false;
    protected $_db;
    protected $mode=0;
    public function __construct(\LSYS\Swoole\Coroutine\MySQLPool $pool=null){
        $this->_pool=$pool?$pool:\LSYS\Swoole\Coroutine\DI::get()->swoole_mysql_pool();
        $this->_query_config=["master*","master*"];
        $this->_table_prefix=$this->_pool->config()->get("table_prefix","");
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::queryMode()
     */
    public function queryMode(int $mode){
        $this->mode=$mode;
        return $this;
    }
    /**
     * 使用SQL_CALC_FOUND_ROWS查询结果数量
     * @return $this
     */
    public function foundRows() {
        $this->_use_found_rows=1;
        return $this;
    }
    /**
     * 设置查询配置
     * @param string $config
     * @return $this
     */
    public function queryConfig($master,$read){
        $this->_query_config=[$master,$read];
        return $this;
    }
    public function query(string $sql,array $data=[])
    {
        $sql=ltrim($sql);
        if ($this->_use_found_rows==1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS',6,0);
            $this->_use_found_rows=2;
        }
        $res=$this->_query($sql,$data);
        return new Result($res);
    }
    public function queryCount(string $sql,array $data=[],string $total_column='total'):int
    {
        if ($this->_use_found_rows==2) {
            $sql="select FOUND_ROWS() as ".addslashes($total_column);
            $this->_use_found_rows=0;
        }
        $row=$this->_query($sql,$data);
        return intval($row->get($total_column,0));
    }
    /**
     * 执行SQL
     * @param string $sql
     * @param mixed $data
     * @throws \LSYS\Model\Exception
     */
    protected function _query($sql,$data){
        $this->_last_query=$sql;
        switch ($this->mode){
            case \LSYS\Model\Database::QUERY_SLAVE_ONCE:
                $this->mode=\LSYS\Model\Database::QUERY_MUST_MASTER;
                $index=1;
            break;
            case \LSYS\Model\Database::QUERY_SLAVE_ALL:
                $index=1;
            break;
            default:
                $index=0;
            break;
        }
        $db=$this->_pool->pop($this->_query_config[$index]);
        try{
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($sql,false));
            $row=$this->_pool->query($db, function()use($db,$sql,$data){
                $pre=$db->mysql()->prepare($sql);
                if($pre&&$pre->execute($data)){
                    $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($sql,false));
                    $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,false));
                    return $pre;
                }
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,false));
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,false));
                return false;
            });
        }catch (\Exception $_e){
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,false));
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,false));
            $this->_pool->push($db);
            $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
            $e->setErrorSql($sql);
            throw $e;
        }
        $this->_pool->push($db);
        return $row;
    }
    public function exec($sql,array $data=[])
    {
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($sql,true));
        $this->_last_query=$sql;
        if ($this->inTransaction()) {
            $db=$this->_db;
            $res=$db->mysql()->prepare($sql);
            if(!$res||!$res->execute($data)){
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,true));
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
                $e=new Exception($db->mysql()->error,$db->mysql()->errno);
                $e->setErrorSql($sql);
                throw $e;
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($sql,true));
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
            $this->_insert_id=$db->mysql()->insert_id;
            $this->_affected_rows=$db->mysql()->affected_rows;
        }else{
            $db=$this->_pool->pop($this->_query_config[0]);
            try{
                $res=$this->_pool->query($db, function()use($db,$sql,$data){
                    $pre=$db->mysql()->prepare($sql);
                    if($pre&&$pre->execute($data)){
                        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($sql,true));
                        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
                        return $pre;
                    }
                    $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,true));
                    $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
                    return false;
                });
            }catch (\Exception $_e){
                $this->_pool->push($db);
                $e=new Exception($_e->getMessage(),$_e->getCode(),$_e);
                $e->setErrorSql($sql);
                throw $e;
            }
            $this->_insert_id=$db->mysql()->insert_id;
            $this->_affected_rows=$db->mysql()->affected_rows;
            $this->_pool->push($db);
        }
        return !empty($res);
    }
    public function listColumns(string $table)
    {
        $sql='SHOW FULL COLUMNS FROM '.$table;
        $result=$this->_query($sql);
        $columns=[];
        $pk=[];
        foreach ($result as $row){
            $column=new Column($row['Field']);
            if($row['Key']=='PRI')$pk[]=$row['Field'];
            if($row['Null'] == 'YES')$column->setAllowNull(1);
            if ($row['Default']!='CURRENT_TIMESTAMP'){
                $column->setDefault($row['Default']);
            }
            $column->setType($row['Type']);
            $column->setComment(trim($row['Comment'],"\t\r\n"));
            $columns[]=$column;
        }
        return new \LSYS\Model\Database\ColumnSet(new ColumnSet($columns), count($pk)==1?array_shift($pk):$pk);
    }
    public function insertId()
    {
        return $this->_insert_id;
    }
    public function quoteValue($value, $column_type=null)
    {
        static $no_esc;
        list($status,$value)=$this->quoteString($value);
        if ($status)return $value;
        if($no_esc)return "'".addslashes($value)."'";
        $db=$this->_pool->pop($this->_query_config[1]);
        $mysql=$db->mysql();
        $no_esc=!method_exists($mysql, "escape");
        try{
            $value="'".$mysql->escape ( $value )."'";
            $this->_pool->push($db);
            return $value;
        }catch (\LSYS\Database\Exception $e){//callback can't throw exception...
            $this->_pool->push($db);
        }
        return "'".addslashes($value)."'";
    }
    public function lastQuery():?string
    {
        return $this->_last_query;
    }
    public function affectedRows():int
    {
        return $this->_affected_rows;
    }
    /**
     * 事务开始[注意:开始后一定要调用回滚或确认,否则有连接池泄漏风险]
     */
    public function beginTransaction():bool{
		if($this->inTransaction()) return true;
        $this->_db_free();
        $this->_db=$this->_pool->pop($this->_query_config[0]);
        $status=$this->_db->mysql()->begin();
        if ($status) {
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin());
        }else{
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionFail());
        }
        $this->_in_transaction=true;
        return $status;
    }
    public function inTransaction():bool{
        return $this->_in_transaction;
    }
    /**
     * 事务回滚
     */
    public function rollback():bool{
        if(!$this->_db){
            $this->_in_transaction=false;
            return false;
        }
        $state=$this->_db->mysql()->rollback();
        $this->_db_free();
        $this->_in_transaction=false;
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback());
        return $state;
    }
    /**
     * 事务确认
     */
    public function commit():bool{
        if(!$this->_db){
            $this->_in_transaction=false;
            return false;
        }
        $state=$this->_db->mysql()->commit();
        $this->_db_free();
        $this->_in_transaction=false;
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit());
        return $state;
    }
    public function __destruct() {
        $this->_db_free();
    }
    protected function _db_free(){
        if($this->_db)$this->_pool->push($this->_db);
        $this->_db=null;
    }
    public function release():void {
        //因为使用连接池资源.这里只有在事务时才会占用资源
        //事务进行时有调用,无法确定是否是事务完全完结.所以这里直接回滚
        $this->rollback();
    }
    public function expr($value, array $param=[])
    {
        return new \LSYS\Model\Database\Swoole\Expr($value, $param);
    }
    public function SQLBuilder(Table $table) {
        return new \LSYS\Model\Database\Builder($table);
    }
}
