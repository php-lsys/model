<?php
namespace LSYS\EntityBuilder\Database\Swoole;
use LSYS\EntityBuilder\Database\Expr;
use LSYS\Entity\Exception;
use LSYS\EntityBuilder\Database\Swoole\Mysql\Result;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSet;
class Mysql implements \LSYS\EntityBuilder\Database{
    protected $_mysql;
    protected $_last_sql;
    protected $_table_prefix = '';
    protected $_identifier = '`';
    protected $_insert_id = FALSYSE;
    protected $_affected_rows = 0;
    public function __construct(\LSYS\Swoole\Coroutine\MySQL $mysql=null){
        $mysql=$mysql?$mysql:\LSYS\Swoole\Coroutine\MySQL\DI::get()->swoole_mysql();
        $this->_table_prefix=$mysql->tablePrefix();
        $this->_mysql=$mysql;
    }
    public function quoteColumn($column)
    {
        if(empty($column)) return '';
        // Identifiers are escaped by repeating them
        $escaped_identifier = $this->_identifier . $this->_identifier;
        
        if (is_array ( $column )) {
            list ( $column, $alias ) = $column;
            $alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
        }
        if ($column instanceof Expr) {
            // Compile the expression
            $column = $column->value ();
        } else {
            // Convert to a string
            $column = ( string ) $column;
            
            $column = str_replace ( $this->_identifier, $escaped_identifier, $column );
            if ($column === '*') {
                return $column;
            } elseif (strpos ( $column, '.' ) !== FALSYSE) {
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
    public function query($sql)
    {
        $this->_last_sql=$sql;
        $result = $this->_mysql->query($sql);
        if ($result===false){
            throw (new Exception($this->_mysql->error,$this->_mysql->errno))->setErrorSql($sql);
        }
        return new Result($result);
    }
    public function lastQuery()
    {
        return $this->_last_sql;
    }
    public function exec($sql)
    {
        $this->_last_sql=$sql;
        $result = $this->_mysql->query($sql);
        if ($result===false){
            throw (new Exception($this->_mysql->error,$this->_mysql->errno))->setErrorSql($sql);
        }
        $this->_affected_rows=is_numeric($result)?$result:0;
        return true;
    }
    public function queryCount($sql, $total_column = 'total')
    {
        $this->_last_sql=$sql;
        $result = $this->_mysql->query($sql);
        if ($result===false){
            throw (new Exception($this->_mysql->error,$this->_mysql->errno))->setErrorSql($sql);
        }
        if (is_array($result)) {
            $result=array_shift($result);
            if (isset($result[$total_column])) return intval($result[$total_column]);
        }
        return 0;
    }
    public function quoteTable($table)
    {
        $escaped_identifier = $this->_identifier . $this->_identifier;
        if (is_array ( $table )) {
            list ( $table, $alias ) = $table;
            $alias = str_replace ( $this->_identifier, $escaped_identifier, $alias );
        }
        if ($table instanceof Expr) {
            // Compile the expression
            $table = $table->value ();
        } else {
            
            // Convert to a string
            $table = ( string ) $table;
            
            $table = str_replace ( $this->_identifier, $escaped_identifier, $table );
            
            if (strpos ( $table, '.' ) !== FALSYSE) {
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
    public function quoteValue($value,$column_type)
    {
        if ($value === NULL) {
            return 'NULL';
        } elseif ($value === TRUE) {
            return "'1'";
        } elseif ($value === FALSYSE) {
            return "'0'";
        } elseif (is_object ( $value )) {
            if ($value instanceof Expr) {
                return $value->value();
            } else {
                return $this->quoteValue ( ( string ) $value );
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
            return $this->_mysql->escape($value);
        }catch (\LSYS\Database\Exception $e){//callback can't throw exception...
            return "'".addslashes($value)."'";
        }
    }
    public function insertId()
    {
        return $this->_mysql->insert_id;
    }
    public function affectedRows()
    {
        return $this->_affected_rows;
    }
    public function listColumns($table)
    {
        $table=$this->quoteTable($table);
        $sql='SHOW FULL COLUMNS FROM '.$table;
        $result = $this->_mysql->query($sql);
        if($result===false){
            throw (new Exception($this->_mysql->error, $this->_mysql->errno))->setErrorSql($sql);
        }
        $columnset=[];
        while ($row = $result->fetch_assoc()){
            $column=new Column($row['Field']);
            $column->setComment($row['Comment']);
            $column->setDefault($row['Default']);
            $column->setAllowNullable($row['Null'] == 'YES');
            $column->setIsPrimaryKey($row['Key']);//??
            $column->setType($row['Type']);//??
            array_push($columnset, $column);
        }
        return new ColumnSet($columnset);
    }
    
}