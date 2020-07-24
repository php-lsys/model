<?php
namespace LSYS\Model\Database\Swoole;
use LSYS\Model\Database\Expr;
trait MYSQLTrait{
    private $event_manager;
    /**
     * 设置事件管理器
     * @param \LSYS\EventManager $event_manager
     * @return static
     */
    public function setEventManager(\LSYS\EventManager $event_manager){
        $this->event_manager=$event_manager;
        return $this;
    }
    /**
     * 包裹字段
     * @param string $column
     * @return string
     */
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
    /**
     * 包裹表名
     * @param string $table
     * @return string
     */
    public function quoteTable($table)
    {
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
    /**
     * 转为字符串
     * @param mixed $value
     * @return [bool,string]
     */
    protected function quoteString($value)
    {
        if ($value === NULL) {
            return [true,'NULL'];
        } elseif ($value === TRUE) {
            return [true,"'1'"];
        } elseif ($value === FALSE) {
            return [true,"'0'"];
        } elseif (is_object ( $value )) {
            if ($value instanceof Expr) {
                // Compile the expression
                return [true,$value->compile($this)];
            } else {
                // Convert the object to a string
                return [false,$value];
            }
        } elseif (is_array ( $value )) {
            return [true,'(' . implode ( ', ', array_map ( array (
                $this,
                'quoteValue'
            ), $value ) ) . ')'];
        } elseif (is_int ( $value )) {
            return [true,( int ) $value];
        } elseif (is_float ( $value )) {
            // Convert to non-locale aware float to prevent possible commas
            return [true,sprintf ( '%F', $value )];
        }
        return [false,$value];
    }
}