<?php
namespace LSYS\Model\Database\Swoole;
use LSYS\Entity\Exception;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
use LSYS\Model\Database\Expr;
use LSYS\DI\ShareCache;
use LSYS\Entity\Table;
class MYSQL implements \LSYS\Model\Database {
    protected $_mysql;
    protected $_master_mysql;
    protected $_mysql_connect=0;
    protected $_master_mysql_connect=0;
    protected $_mysql_callback;
    protected $_use_found_rows=0;
    protected $_query_config;
    protected $_last_query;
    protected $_affected_rows;
    protected $_insert_id='';
    protected $_identifier='`';
    protected $_table_prefix='';
    protected $_sleep=0;
    protected $_in_transaction=0;
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
    protected function _initCreateMysql(){
        if (is_object($this->_master_mysql)) return $this->_master_mysql;
        if (is_object($this->_mysql)) return $this->_mysql;
        $master=!in_array($this->mode, [\LSYS\Model\Database::QUERY_SLAVE_ALL,\LSYS\Model\Database::QUERY_SLAVE_ONCE]);
        return $this->createMysql($master);
    }
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
    public function queryMode($mode){
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
    public function query($sql,array $data=[])
    {
        $sql=ltrim($sql);
        if ($this->_use_found_rows==1&&strncasecmp($sql,"select",6)==0){
            $sql=substr_replace($sql,' SQL_CALC_FOUND_ROWS',6,0);
            $this->_use_found_rows=2;
        }
        $res=$this->_query($sql,$data);
        return new Result($res);
    }
    public function queryCount($sql,array $data=[],$total_column='total')
    {
        if ($this->_use_found_rows==2) {
            $sql="select FOUND_ROWS() as ".addslashes($total_column);
            $this->_use_found_rows=0;
        }
        $row=$this->_query($sql,$data);
        $row=$row->fetch();
        return intval($row[$total_column]??0);
    }
    protected function reCreateMysql($mysql){
        @$mysql->close();
        if($mysql==$this->_master_mysql){
            return $this->createMysql(true);
        }elseif($mysql==$this->_mysql){
            return $this->createMysql(false);
        }
        return $mysql;
    }
    protected function createMysql($is_master=true){
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
    protected function _query($sql,$data){
        $this->_last_query=$sql;
        while (true) {
            if($this->mode==\LSYS\Model\Database::QUERY_AUTO){
                if(!$this->_master_mysql)$this->connect($this->createMysql(true));
            }
            $this->connect($this->_initCreateMysql());
            if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ALL
              ||$this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
                  $mysql=$this->_mysql?$this->_mysql:$this->_master_mysql;
            }else{
                $mysql=$this->_master_mysql;
            }
            $result=$mysql->prepare($sql);
            if($result&&$result->execute($data)){
                if($this->mode==\LSYS\Model\Database::QUERY_SLAVE_ONCE){
                    $this->mode=\LSYS\Model\Database::QUERY_AUTO;
                }
                break;
            }
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
        return $result;
    }
    public function exec($sql,array $data=[])
    {
        if (!$this->_master_mysql)$this->connect($this->createMysql(true));
        $this->_last_query=$sql;
        $mysql=$this->_master_mysql;
        if($this->inTransaction()){
            $result=$mysql->prepare($sql);
            if($result&&$result->execute($data))goto succ;
            $e=new Exception($mysql->error,$mysql->errno);
            $e->setErrorSql($sql);
            throw $e;
        }else{
            $this->_query($sql, $data);
            goto succ;
        }
        succ:
        $this->_insert_id=$mysql->insert_id;
        $this->_affected_rows=$mysql->affected_rows;
        return true;
    }
    public function listColumns($table)
    {
        $sql='SHOW FULL COLUMNS FROM '.$table;
        $result=$this->_query($sql,[]);
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
    public function quoteColumn($column)
    {
        if(empty($column)) return '';
        $this->_initCreateMysql();
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier . $this->_identifier;
        
        if (is_array ( $column )) {
            list ( $column, $alias ) = $column;
            $alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
        }
        if ($column instanceof Expr) {
            // Compile the expression
            $column = $column->compile($this);
        } else {
            // Convert to a string
            $column = ( string ) $column;
            
            $column = str_replace ( $this->_identifier, $escaped_identifier, $column );
            if ($column === '*') {
                return $column;
            } elseif (strpos ( $column, '.' ) !== FALSE) {
                $parts = explode ( '.', $column );
                
                if ($prefix = $this->_table_prefix) {
                    // Get the offset of the table name, 2nd-to-last part
                    $offset = count ( $parts ) - 2;
                    
                    // Add the table prefix to the table name
                    $parts [$offset] = $prefix . $parts [$offset];
                }
                
                foreach ( $parts as & $part ) {
                    if ($part !== '*') {
                        // Quote each of the parts
                        $part = $this->_identifier . $part . $this->_identifier;
                    }
                }
                
                $column = implode ( '.', $parts );
            } else {
                $column = $this->_identifier . $column . $this->_identifier;
            }
        }
        if (isset ( $alias )) {
            $column .= ' AS ' . $this->_identifier . $alias . $this->_identifier;
        }
        return $column;
    }
    public function quoteTable($table)
    {
        $this->_initCreateMysql();
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier . $this->_identifier;
        
        if (is_array ( $table )) {
            list ( $table, $alias ) = $table;
            $alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
        }
        
        if ($table instanceof Expr) {
            // Compile the expression
            $table = $table->compile ($this);
        } else {
            // Convert to a string
            $table = ( string ) $table;
            
            $table = str_replace ( $this->_identifier, $escaped_identifier, $table );
            
            if (strpos ( $table, '.' ) !== FALSE) {
                $parts = explode ( '.', $table );
                
                if ($prefix = $this->_table_prefix) {
                    // Get the offset of the table name, last part
                    $offset = count ( $parts ) - 1;
                    
                    // Add the table prefix to the table name
                    $parts [$offset] = $prefix . $parts [$offset];
                }
                
                foreach ( $parts as & $part ) {
                    // Quote each of the parts
                    $part = $this->_identifier . $part . $this->_identifier;
                }
                
                $table = implode ( '.', $parts );
            } else {
                // Add the table prefix
                $table = $this->_identifier . $this->_table_prefix . $table . $this->_identifier;
            }
        }
        
        if (isset ( $alias )) {
            // Attach table prefix to alias
            $table .= ' AS ' . $this->_identifier.$this->_table_prefix. $alias . $this->_identifier;
        }
        return $table;
    }
    public function quoteValue($value, $column_type=null)
    {
        if ($value === NULL) {
            return 'NULL';
        } elseif ($value === TRUE) {
            return "'1'";
        } elseif ($value === FALSE) {
            return "'0'";
        } elseif (is_object ( $value )) {
            if ($value instanceof Expr) {
                // Compile the expression
                return $value->compile($this);
            } else {
                // Convert the object to a string
                return $this->quoteValue ( ( string ) $value,$column_type );
            }
        } elseif (is_array ( $value )) {
            return '(' . implode ( ', ', array_map ( array (
                $this,
                __FUNCTION__
            ), $value ) ) . ')';
        } elseif (is_int ( $value )) {
            return ( int ) $value;
        } elseif (is_float ( $value )) {
            // Convert to non-locale aware float to prevent possible commas
            return sprintf ( '%F', $value );
        }
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
    public function lastQuery()
    {
        return $this->_last_query;
    }
    public function affectedRows()
    {
        return $this->_affected_rows;
    }
    public function beginTransaction(){
		if($this->inTransaction()) return true;
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->begin();
        $this->_in_transaction=true;
        return $status;
    }
    public function inTransaction(){
        return $this->_in_transaction;
    }
    /**
     * 事务回滚
     */
    public function rollback(){
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->rollback();
        $this->_in_transaction=false;
        return $status;
    }
    /**
     * 事务确认
     */
    public function commit(){
        
        if(!$this->_master_mysql)$this->connect($this->createMysql(true));
        $status=$this->_master_mysql->commit();
        $this->_in_transaction=false;
        return $status;
    }
    public function __destruct() {
        $this->release();
    }
    public function release() {
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
    public function expr($value, array $param)
    {
        return new \LSYS\Model\Database\Swoole\Expr($value, $param);
    }
    public function builder(Table $table) {
        return new \LSYS\Model\Database\Builder($table);
    }

}
