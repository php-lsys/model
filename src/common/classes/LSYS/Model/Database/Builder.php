<?php
namespace LSYS\Model\Database;
use LSYS\Entity\EntitySet;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\EntityColumnSet;
use LSYS\Entity;
class Builder extends \LSYS\Entity\Database\Builder{
    /**
     * @var array
     */
    protected $_db_pending=array();
    /**
     * @var EntityColumnSet
     */
    protected $_column_set;
    /**
     * 查找一个实体
     * @return Entity
     */
    public function find() {
        $field=$this->_buildField($this->_columnSet());
        $sql = $this->_buildSelect ($field,1);
        return $this->queryOne($sql,$this->_column_set);
    }
    /**
     * @param array|EntityColumnSet|string $column_set $column_set
     * @return EntityColumnSet
     */
    protected function _asEntityColumnSet($column_set,array $patch_columns=[]) {
        if($column_set instanceof EntityColumnSet)return $column_set;
        if (is_null($column_set)){
            if(empty($patch_columns))return $column_set;
            $column_set=new EntityColumnSet($column_set,$patch_columns);
        }else{
            if (is_array($column_set)) {
                $column_set=new EntityColumnSet($column_set,$patch_columns);
            }else if (is_string($column_set)) {
                $column_set=explode(",", $column_set);
                $column_set=array_map('trim',$column_set);
                $column_set=array_filter($column_set);
                $column_set=new EntityColumnSet($column_set,$patch_columns);
            }
        }
        return $column_set;
    }
    /**
     * 设置获取字段列表
     * @param array|EntityColumnSet|string $column_set
     * @return static
     */
    public function columnSet($column_set) {
        $this->_column_set=$this->_asEntityColumnSet($column_set);
        return $this;
    }
    /**
     * 请求当前表一批记录
     * @param string $sql
     * @param array|string $column_set
     * @param array $patch_columns
     * @return EntitySet
     */
    public function queryAll($sql,$column_set=null,array $patch_columns=[]) {
        $result = $this->db()->query ($sql);
        $column_set=$this->_asEntityColumnSet($column_set,$patch_columns);
        return new EntitySet($result ,$this->entityClass(),$column_set,$this->table());
    }
    /**
     * 请求一个记录
     * @param string $sql
     * @param array|string $column_set
     * @param array $patch_columns
     * @return \LSYS\Entity
     */
    public function queryOne($sql,$column_set=null,array $patch_columns=[]) {
        $entity=(new \ReflectionClass($this->table()->entityClass()))->newInstance($this->table());
        $res = $this->db()->query ($sql)->current();
        $column_set=$this->_asEntityColumnSet($column_set,$patch_columns);
        if(!is_null($res))$entity->loadData($res,$column_set,true);
        return $entity;
    }
    /**
     * 查找一批记录
     * @return EntitySet
     */
    public function findAll() {
        $field=$this->_buildField ($this->_columnSet());
        $sql = $this->_buildSelect ($field);
        return $this->queryAll($sql,$this->_column_set);
    }
    /**
     * 统计一批记录数量
     * @return number
     */
    public function countAll() {
        $sql = $this->_buildSelect ("count(*) as total");
        return $this->db()->queryCount($sql,[],"total");
    }
    protected function _columnSet() {
        if ($this->_column_set){
            $columnset=$this->_column_set->asColumnSet($this->tableColumns(),true);
        }else $columnset=$this->tableColumns();
        return $columnset;
    }
    public function reset() {
        $this->_db_pending = array ();
        return $this;
    }
    protected function _buildWhereOp($field, $op, $value) {
        $db=$this->db();
        $columnset=$this->_columnSet();
        $columntype=$columnset->getType($field);
        $op = preg_replace ( "/\s+/", ' ', $op );
        switch ($op) {
            case 'in' :
            case 'not in' :
            case 'exists' :
            case 'not exists' :
                return $db->quoteColumn ( $field ) . ' ' . $op . " (" . $db->quoteValue ( $value,$columntype ) . ") ";
                break;
            default :
                if(empty($op)&&empty($value)) return $db->quoteColumn ( $field );
                return $db->quoteColumn ( $field ) . ' ' . $op . " " . $db->quoteValue ( $value,$columntype ) . " ";
        }
    }
    protected function _buildWhereJoin($where_fragment, $op,&$where) {
        $swhere = trim ( $where );
        if (empty ( $swhere ) || substr ( $swhere, - 1, 1 ) == '(') $where .= ' ' . $where_fragment . ' ';
        else $where .= ' ' . $op . ' ' . $where_fragment . ' ';
    }
    /**
     * 编译条件
     *
     * @return string
     */
    protected function _buildWhere() {
        $where = ' ';
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'where' :
                case 'andWhere' :
                    $this->_buildWhereJoin( call_user_func_array ( array($this,'_buildWhereOp'), $method ['args'] ), 'and' ,$where);
                    break;
                case 'orWhere' :
                    $this->_buildWhereJoin( call_user_func_array ( array($this,'_buildWhereOp'), $method ['args'] ), 'or' ,$where);
                    break;
                case 'whereOpen' :
                case 'andWhereOpen' :
                    $this->_buildWhereJoin( '(', 'and' ,$where);
                    break;
                case 'orWhereOpen' :
                    $this->_buildWhereJoin( '(', 'or' ,$where);
                    break;
                case 'whereClose' :
                case 'andWhereClose' :
                case 'orWhereClose' :
                    $where .= ' ) ';
                    break;
            }
        }
        return $where;
    }
    protected function _buildGroupHavingOp($field, $op, $value){
        $db=$this->db();
        $columnset=$this->_columnSet();
        $columntype=$columnset->getType($field);
        return $db->quoteColumn ( $field ) . ' ' . $op . " " . $db->quoteValue ( $value,$columntype ) . " ";
    }
    protected function _buildGroupHavingJoin($having_fragment, $op,&$having){
        $shaving = trim ( $having );
        if (empty ( $shaving ) || substr ( $shaving, - 1, 1 ) == '(') $having .= ' ' . $having_fragment . ' ';
        else $having .= ' ' . $op . ' ' . $having_fragment . ' ';
    }
    /**
     * 编译GROUP
     *
     * @return string
     */
    protected function _buildGroup() {
        $db = $this->db();;
        $group = array ();
        $having = '';
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'group_by' :
                    $column = $method ['args'];
                    $column = $db->quoteColumn ( $column );
                    $group [] = $column;
                    break;
                case 'having' :
                case 'andHaving' :
                    $this->_build_grouHhaving_join( call_user_func_array ( array($this,'_buildGroupHavingOp'), $method ['args'] ), 'and' ,$having);
                    break;
                case 'orHaving' :
                    $this->_buildGroupHavingJoin(  call_user_func_array ( array($this,'_buildGroupHavingOp'), $method ['args'] ), 'or' ,$having);
                    break;
                case 'andHavingOpen' :
                case 'havingOpen' :
                    $this->_buildGroupHavingJoin( '(', 'and' ,$having);
                    break;
                case 'orHavingOpen' :
                    $this->_buildGroupHavingJoin( '(', 'or' ,$having);
                    break;
                case 'andHavingClose' :
                case 'havingClose' :
                case 'orHavingClose' :
                    $having .= ' ) ';
                    break;
            }
        }
        if (empty ( $group )) return '';
        if (! empty ( $having )) $having = 'HAVING ' . $having;
        return ' GROUP BY ' . implode ( ', ', $group ) . " " . $having;
    }
    /**
     * 编译JOIN
     *
     * @return string
     */
    protected function _buildJoin() {
        $sql = ' ';
        $on = false;
        $db = $this->db();;
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'join' :
                    list ( $table, $type ) = $method ['args'];
                    if (! empty ( $type )) $sql .= strtoupper ( $type ) . ' JOIN';
                    else $sql .= 'JOIN';
                    $table = $db->quoteTable( $table );
                    $sql .= ' ' . $table;
                    $on = false;
                    break;
                case 'on' :
                    list ( $c1, $op, $c2 ) = $method ['args'];
                    if ($op) {
                        $op = ' ' . strtoupper ( $op );
                    }
                    $j = '' . $db->quoteColumn ( $c1 ) . ' ' . $op . ' ' . $db->quoteColumn ( $c2 );
                    if (! $on) {
                        $sql .= ' ON (' . $j . ')';
                    } else {
                        $sql = rtrim ( $sql, ") " ) . ' and ' . $j . ')';
                    }
                    $on = true;
                    break;
            }
        }
        return $sql;
    }
    protected function _buildOrderOp($field,$order){
        return ' '.$this->db()->quoteColumn($field).' '.addslashes($order);
    }
    /**
     * 编译查询
     * @return string
     */
    protected function _buildSelect($field,$limit=null) {
        $db = $this->db();
        $distinct = false;
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'distinct' :
                    $distinct = ( bool ) $method ['args'] [0];
                    break;
            }
        }
        $distinct=$distinct?" DISTINCT ":'';
        $table=$db->quoteTable($this->tableName());
        $sql="SELECT $distinct ".$field." FROM ".$table;
        $sql .= $this->_buildJoin ();
        
        $order = '';
        $offset = null;
        if ($limit>0) $limit = " limit " . intval($limit);
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'orderBy' :
                    $Od = call_user_func_array(array($this,'_buildOrderOp'),$method['args']);
                    $order ? ($order .= ' ,' . $Od) : ($order .= " order by " . $Od);
                    break;
                case 'limit' :
                    if($limit==null){
                        
                        $_limit=$method ['args'] [0];
                        if($_limit===false||$_limit===null) $limit = null;
                        else $limit = " limit " . intval($_limit);
                    }
                    break;
                case 'offset' :
                    $Offset=$method ['args'] [0];
                    if($Offset===false||$Offset===null) $offset= null;
                    else $offset= " offset " . intval($Offset);
                    break;
            }
        }
        if ($offset !== null && $limit === null) $limit = " limit " . PHP_INT_MAX;
        $where = $this->_buildWhere ();
        $where = empty ( trim ( $where ) ) ? ' ' : ' where ' . $where;
        $group = $this->_buildGroup ();
        return $sql . $where . $group . $order . $limit . $offset;
    }
    protected function _buildField(ColumnSet $columnset) {
        $columns=$columnset->asArray(ColumnSet::TYPE_FIELD);
        $db = $this->db();
        $_field=array();
        if(array_search($this->primaryKey(), $columns,true)===false){
            array_unshift($columns, $this->primaryKey());
        }
        foreach ($columns as $value)
        {
            array_push($_field, $db->quoteColumn(array($this->tableName().".".$value,$value)));
        }
        $field=implode(",", $_field);
        return $field;
    }
    /**
     * set primary_key = $pk where
     * @param string $pk
     * @return static
     */
    public function wherePk($pk){
        $this->where($this->primaryKey(), "=", $pk);
        return $this;
    }
    /**
     * Alias of andWhere()
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function where($column, $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'where',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $op,
                $value
            )
        );
        return $this;
    }
    
    /**
     * Creates a new "AND WHERE" condition for the query.
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function andWhere($column, $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andWhere',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $op,
                $value
            )
        );
        return $this;
    }
    
    /**
     * Creates a new "OR WHERE" condition for the query.
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function orWhere($column, $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orWhere',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $op,
                $value
            )
        );
        
        return $this;
    }
    
    /**
     * Alias of andWhereOpen()
     *
     * @return $this
     */
    public function whereOpen() {
        return $this->andWhereOpen ();
    }
    
    /**
     * Opens a new "AND WHERE (...)" grouping.
     *
     * @return $this
     */
    public function andWhereOpen() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andWhereOpen',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Opens a new "OR WHERE (...)" grouping.
     *
     * @return $this
     */
    public function orWhereOpen() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orWhereOpen',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return $this
     */
    public function whereClose() {
        return $this->andWhereClose ();
    }
    
    /**
     * Closes an open "AND WHERE (...)" grouping.
     *
     * @return $this
     */
    public function andWhereClose() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andWhereClose',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Closes an open "OR WHERE (...)" grouping.
     *
     * @return $this
     */
    public function orWhereClose() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orWhereClose',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Applies sorting with "ORDER BY ..."
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $direction
     *        	direction of sorting
     * @return $this
     */
    public function orderBy($column, $direction = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'order_by',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $direction
            )
        );
        
        return $this;
    }
    
    /**
     * Return up to "LIMIT ..." results
     * set null or false clear setting
     * @param integer $number
     *        	maximum results to return
     * @return $this
     */
    public function limit($number) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'limit',
            'args' => array (
                $number
            )
        );
        
        return $this;
    }
    
    /**
     * Enables or disables selecting only unique columns using "SELECT DISTINCT"
     *
     * @param boolean $value
     *        	enable or disable distinct columns
     * @return $this
     */
    public function distinct($value = true) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'distinct',
            'args' => array (
                $value
            )
        );
        
        return $this;
    }
    /**
     * Start returning results after "OFFSET ..."
     * set null or false clear setting
     * @param integer $number
     *        	starting result number
     * @return $this
     */
    public function offset($number) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'offset',
            'args' => array (
                $number
            )
        );
        
        return $this;
    }
    
    /**
     * Adds addition tables to "JOIN ...".
     *
     * @param mixed $table
     *        	column name or array($column, $alias)
     * @param string $type
     *        	join type (LEFT, RIGHT, INNER, etc)
     * @return $this
     */
    public function join($table, $type = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'join',
            'args' => array (
                $table,
                $type
            )
        );
        
        return $this;
    }
    /**
     * Adds "ON ..." conditions for the last created JOIN statement.
     *
     * @param mixed $c1
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $c2
     *        	column name or array($column, $alias) or object
     * @return $this
     */
    public function on($c1, $op, $c2) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'on',
            'args' => array (
                strpos($c1, ".")===false?$this->tableName().".".$c1:$c1,
                $op,
                strpos($c2, ".")===false?$this->tableName().".".$c2:$c2,
            )
        );
        
        return $this;
    }
    
    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param mixed $columns
     *        	column name
     * @param
     *        	...
     * @return $this
     */
    public function groupBy($columns) {
        // Add pending database call which is executed after query type is determined
        $columns=strpos($columns, ".")===false?$this->tableName().".".$columns:$columns;
        $this->_db_pending [] = array (
            'name' => 'group_by',
            'args' => $columns
        );
        
        return $this;
    }
    
    /**
     * Alias of andHaving()
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function having($column, $op, $value = NULL) {
        return $this->andHaving ( $column, $op, $value );
    }
    
    /**
     * Creates a new "AND HAVING" condition for the query.
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function andHaving($column, $op, $value = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andHaving',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $op,
                $value
            )
        );
        
        return $this;
    }
    
    /**
     * Creates a new "OR HAVING" condition for the query.
     *
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function orHaving($column, $op, $value = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orHaving',
            'args' => array (
                strpos($column, ".")===false?$this->tableName().".".$column:$column,
                $op,
                $value
            )
        );
        
        return $this;
    }
    
    /**
     * Alias of andHavingOpen()
     *
     * @return $this
     */
    public function havingOpen() {
        return $this->andHavingOpen ();
    }
    /**
     * Opens a new "AND HAVING (...)" grouping.
     *
     * @return $this
     */
    public function andHavingOpen() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andHavingOpen',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Opens a new "OR HAVING (...)" grouping.
     *
     * @return $this
     */
    public function orHavingOpen() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orHavingOpen',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return $this
     */
    public function havingClose() {
        return $this->andHavingClose ();
    }
    
    /**
     * Closes an open "AND HAVING (...)" grouping.
     *
     * @return $this
     */
    public function andHavingClose() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andHavingClose',
            'args' => array ()
        );
        
        return $this;
    }
    
    /**
     * Closes an open "OR HAVING (...)" grouping.
     *
     * @return $this
     */
    public function orHavingClose() {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orHavingClose',
            'args' => array ()
        );
        
        return $this;
    }
}
