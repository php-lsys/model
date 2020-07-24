<?php
namespace LSYS\Model\Database\Swoole;
use LSYS\Model\Exception;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
use LSYS\DI\ShareCache;
use LSYS\Entity\Table;
use LSYS\Model\Database\Swoole\EventManager\DBEvent;
class MYSQL implements \LSYS\Model\Database {
    use MYSQLTrait;
    protected $_mysql;
    protected $_master_mysql;
    protected $_mysql_connect=0;
    protected $_master_mysql_connect=0;
    protected $_mysql_callback;
    protected $_use_found_rows=0;
    protected $_query_config;
    protected $_last_query;
    protected $_affected_rows=0;
    protected $_insert_id=null;
    protected $_identifier='`';
    protected $_table_prefix='';
    protected $_sleep=0;
    protected $_in_transaction=false;
    protected $_db;
    protected $mode=0;
    /**
     * $mysql_callback回调得到MYSQL客户端
     * 参数: $mysql 存在时为上一个无法连接的客户端 $is_master 是否必须是主库
     * @param callable $mysql_callback($mysql=null,$is_master=0)
     */
    public function __construct(callable $mysql_callback=null){
        $this->_mysql_callback=$mysql_callback;
    }
    /**
     * 初始化创建一个协程版连接对象
     * @return \LSYS\Swoole\Coroutine\MySQL
     */
    protected function _initCreateMysql(){
        if (is_object($this->_master_mysql)) return $this->_master_mysql;
        if (is_object($this->_mysql)) return $this->_mysql;
        $master=!in_array($this->mode, [\LSYS\Model\Database::QUERY_SLAVE_ALL,\LSYS\Model\Database::QUERY_SLAVE_ONCE]);
        return $this->createMysql($master);
    }
    /**
     * 连接数据库
     * @param  $mysql \LSYS\Swoole\Coroutine\MySQL
     */
    protected function connect($mysql) {
        if($this->_master_mysql==$this->_mysql){//主从相同,有一个连接说明两个都连接
            if($this->_master_mysql_connect||$this->_mysql_connect){
                $this->_mysql_connect=1;
                $this->_master_mysql_connect=1;
                return ;
            }
        }
        if($this->_master_mysql==$mysql){
            if($this->_master_mysql_connect)return ;
            $this->_master_mysql_connect=1;
        }else{
            if($this->_mysql_connect)return ;
            $this->_mysql_connect=1;
        }
        $mysql->connectFromConfig();
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
     * 使用FOUND_ROWS 获取结果数量
     * @param boolean $use_found
     * @return \LSYS\Model\Database\Swoole\MYSQL
     */
    public function foundRows() {
        $this->_use_found_rows=1;
        return $this;
    }
    public function query(string $sql,array $data=[])
    {
        $sql=ltrim($sql);
        if ($this->_use_found_rows==1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS',6,0);
            $this->_use_found_rows=2;
        }
        $res=$this->_query($sql,$data,false);
        return new Result($res);
    }
    public function queryCount(string $sql,array $data=[],string $total_column='total'):int
    {
        if ($this->_use_found_rows==2) {
            $sql="select FOUND_ROWS() as ".addslashes($total_column);
            $this->_use_found_rows=0;
        }
        $row=$this->_query($sql,$data,false);
        $row=$row->fetch();
        return intval($row[$total_column]??0);
    }
    /**
     * 重新创建一个对象
     * @param \LSYS\Swoole\Coroutine\MySQL $mysql
     * @return \LSYS\Swoole\Coroutine\MySQL
     */
    protected function reCreateMysql($mysql){
        @$mysql->close();
        if($mysql==$this->_master_mysql){
            return $this->createMysql(true);
        }elseif($mysql==$this->_mysql){
            return $this->createMysql(false);
        }
        return $mysql;
    }
    /**
     * 创建一个MYSQL协程版客户端对象
     * @param boolean $is_master
     * @return \LSYS\Swoole\Coroutine\MySQL
     */
    protected function createMysql(bool $is_master=true){
        if (is_callable($this->_mysql_callback)){
            $mysql=call_user_func($this->_mysql_callback,(!$is_master&&$this->_mysql)?$this->_mysql:$this->_master_mysql,$is_master);
            assert($mysql instanceof \LSYS\Swoole\Coroutine\MySQL);
            if(!$is_master){
                $this->_mysql=$mysql;
                $this->_mysql_connect=0;
            }else{
                $this->_master_mysql=$mysql;
                $this->_master_mysql_connect=0;
            }
        }else{
            if($this->_master_mysql){
                \LSYS\Swoole\Coroutine\DI::get()->swoole_mysql(new ShareCache());
            }
            $mysql=\LSYS\Swoole\Coroutine\DI::get()->swoole_mysql();
            $this->_master_mysql=$mysql;
            $this->_master_mysql_connect=0;
        }
        $config=$mysql->getConfig();
        $this->_table_prefix=isset($config['table_prefix'])?$config['table_prefix']:"";
        $this->_sleep=isset($config['sleep'])?intval($config['sleep']):0;
        return $mysql;
    }
    /**
     * 执行SQL
     * @param string $sql
     * @param array $data
     * @throws \LSYS\Entity\Exception
     */
    protected function _query(string $sql,$data,$exec){
        $this->_last_query=$sql;
        while (true) {
            if($this->mode==\LSYS\Model\Database::QUERY_MUST_MASTER){
                if(!$this->_master_mysql)$this->connect($this->createMysql(true));
            }
            $this->connect($this->_initCreateMysql());
            if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ALL
              ||$this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
                  $mysql=$this->_mysql?$this->_mysql:$this->_master_mysql;
            }else{
                $mysql=$this->_master_mysql;
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($sql,$exec));
            $result=$mysql->prepare($sql);
            if($result&&$result->execute($data)){
                if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
                    $this->mode=\LSYS\Model\Database::QUERY_MUST_MASTER;
                }
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlOk($sql,$exec));
                break;
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,$exec));
            if($this->_master_mysql==$mysql&&$this->inTransaction()){
                $e=new Exception($mysql->error,$mysql->errno);
                $e->setErrorSql($sql);
                throw $e;
            }
            if($mysql->errno=='2006'
                ||$mysql->errno=='2013'
                ||(isset($mysql->errCode)&&$mysql->errCode=='5001')
                ){
                    while (true) {
                        try{
                            $this->connect($this->reCreateMysql($mysql));
                            break;
                        }catch (\LSYS\Swoole\Exception $e){
                            \LSYS\Loger\DI::get()->loger()->add(\LSYS\Loger::ERROR,$e);
                            if($this->_sleep>0)\co::sleep($this->_sleep);
                        }
                    }
                    continue;
            }else{
                $e=new Exception($mysql->error,$mysql->errno);
                $e->setErrorSql($sql);
                throw $e;
            }
        }
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,$exec));
        return $result;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Database::exec()
     */
    public function exec($sql,array $data=[])
    {
        if (!$this->_master_mysql)$this->connect($this->createMysql(true));
        $this->_last_query=$sql;
        $mysql=$this->_master_mysql;
        if($this->inTransaction()){
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlStart($sql,true));
            $result=$mysql->prepare($sql);
            if($result&&$result->execute($data)){
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,true));
                $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
                goto succ;
            }
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlBad($sql,true));
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::sqlEnd($sql,true));
            $e=new Exception($mysql->error,$mysql->errno);
            $e->setErrorSql($sql);
            throw $e;
        }else{
            $this->_query($sql, $data,true);
            goto succ;
        }
        succ:
        $this->_insert_id=$mysql->insert_id;
        $this->_affected_rows=$mysql->affected_rows;
        return true;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Database::listColumns()
     */
    public function listColumns(string $table)
    {
        $sql='SHOW FULL COLUMNS FROM '.$table;
        $result=$this->_query($sql,[],false);
        $columns=[];
        $pk=[];
        foreach ($result->fetchall() as $row){
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
        list($status,$value)=$this->quoteString($value);
        if ($status)return $value;
        try{
            if(!$this->_mysql&&!$this->_master_mysql){
                $this->connect($this->_initCreateMysql());
            }
            $mysql=$this->_mysql?$this->_mysql:$this->_master_mysql;
            if(method_exists($mysql, "escape")){
                $value="'".$mysql->escape ( $value )."'";
            }else{
                $value="'".addslashes($value)."'";
            }
            return $value;
        }catch (\LSYS\Database\Exception $e){//callback can't throw exception...
            return "'".addslashes($value)."'";
        }
    }
    public function lastQuery():?string
    {
        return $this->_last_query;
    }
    public function affectedRows():int
    {
        return $this->_affected_rows;
    }
    public function beginTransaction():bool{
		if($this->inTransaction()) return true;
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->begin();
        $this->_in_transaction=true;
        if ($status) {
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionBegin());
        }else{
            $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionFail());
        }
        return $status;
    }
    public function inTransaction():bool{
        return $this->_in_transaction;
    }
    /**
     * 事务回滚
     */
    public function rollback():bool{
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->rollback();
        $this->_in_transaction=false;
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionRollback());
        return $status;
    }
    /**
     * 事务确认
     */
    public function commit():bool{
        
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->commit();
        $this->_in_transaction=false;
        $this->event_manager&&$this->event_manager->dispatch(DBEvent::transactionCommit());
        return $status;
    }
    public function __destruct() {
        $this->release();
    }
    public function release():void {
        if($this->_in_transaction){//事务发生,直接回滚.
            $this->rollback();
        }
        if(!is_callable($this->_mysql_callback)){
            \LSYS\Swoole\Coroutine\DI::get()->swoole_mysql(new ShareCache());
        }
        if($this->_mysql){
            @$this->_mysql->close();
            $this->_mysql_connect=0;
            $this->_mysql=null;
        }
        if($this->_master_mysql){
            @$this->_master_mysql->close();
            $this->_master_mysql_connect=0;
            $this->_master_mysql=null;
        }
    }
    public function expr($value, array $param=[])
    {
        return new \LSYS\Model\Database\Swoole\Expr($value, $param);
    }
    public function SQLBuilder(Table $table) {
        return new \LSYS\Model\Database\Builder($table);
    }

}
