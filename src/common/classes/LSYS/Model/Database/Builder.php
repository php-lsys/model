<?php
namespace LSYS\Model\Database;
use LSYS\Model\EntitySet;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\EntityColumnSet;
use LSYS\Entity;
/**
 * @method \LSYS\Model table() 
 */
class Builder extends \LSYS\Entity\Database\SQLBuilder{
    /**
     * @var array
     */
    protected $_db_pending=array();
    /**
     * @var EntityColumnSet
     */
    protected $_column_set;
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Database\SQLBuilder::update()
     */
    public function update(array $records,$where){
        if($where instanceof Expr)$where=$where->compile($this->table()->db());
        return parent::update($records, $where);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Database\SQLBuilder::delete()
     */
    public function delete($where){
        if($where instanceof Expr)$where=$where->compile($this->table()->db());
        return parent::delete($where);
    }
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
    public function columnSet($column_set,array $patch_columns=[]) {
        $this->_column_set=$this->_asEntityColumnSet($column_set,$patch_columns);
        return $this;
    }
    /**
     * 获取已设置的字段列表
     * @return EntityColumnSet|NULL
     */
    public function columnGet() {
        return $this->_column_set;
    }
    /**
     * 请求当前表一批记录
     * @param string $sql
     * @param array|string $column_set
     * @param array $patch_columns
     * @return EntitySet
     */
    public function queryAll(string $sql,$column_set=null,array $patch_columns=[]) {
        $result = $this->table()->db()->query ($sql);
        $column_set=$this->_asEntityColumnSet($column_set,$patch_columns);
        return new EntitySet($result ,$this->table()->entityClass(),$column_set,$this->table());
    }
    /**
     * 请求一个记录
     * @param string $sql
     * @param array|string $column_set
     * @param array $patch_columns
     * @return \LSYS\Entity
     */
    public function queryOne(string $sql,$column_set=null,array $patch_columns=[]) {
        $entity=(new \ReflectionClass($this->table()->entityClass()))->newInstance($this->table());
        $res = $this->table()->db()->query ($sql)->current();
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
    public function countAll():int{
        $sql = $this->_buildSelect ("count(*) as total");
        return $this->table()->db()->queryCount($sql,[],"total");
    }
    /**
     * 获取构造查询字段对象
     * @return \LSYS\Entity\ColumnSet
     */
    private function _columnSet() {
        if ($this->_column_set){
            $columnset=$this->_column_set->asColumnSet($this->table()->tableColumns(),true);
        }else $columnset=$this->table()->tableColumns();
        return $columnset;
    }
    /**
     * 重置查询条件
     * @return $this
     */
    public function reset() {
        $this->_db_pending = array ();
        return $this;
    }
    /**
     * 编译WHERE操作
     * @param mixed $field
     * @param string $op
     * @param mixed $value
     * @return string
     */
    protected function _buildWhereOp($field, string $op, $value):string {
        $db=$this->table()->db();
        $columntype=$this->columnType($field);
        $op = preg_replace ( "/\s+/", ' ', $op );
        switch ($op) {
            case 'in' :
            case 'not in' :
            case 'exists' :
            case 'not exists' :
                $val=$db->quoteValue ( $value,$columntype );
                if (!is_array($value)) {
                    $val="(".$val.")";
                }
                return $db->quoteColumn ( $field ) . ' ' . $op . " " .$val. " ";
                break;
            default :
                if(empty($op)&&empty($value)) return $db->quoteColumn ( $field );
                return $db->quoteColumn ( $field ) . ' ' . $op . " " . $db->quoteValue ( $value,$columntype ) . " ";
        }
        return '';
    }
    /**
     * 编译JOIN操作
     * @param string $where_fragment
     * @param string $op
     * @param string $where
     */
    protected function _buildWhereJoin($where_fragment,string  $op,&$where) {
        $swhere = trim ( $where );
        if (empty ( $swhere ) || substr ( $swhere, - 1, 1 ) == '(') $where .= ' ' . $where_fragment . ' ';
        else $where .= ' ' . $op . ' ' . $where_fragment . ' ';
    }
    /**
     * 编译条件
     * @return string
     */
    protected function _buildWhere():string {
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
    /**
     * 编译GroupHaving操作
     * @param mixed $field
     * @param string $op
     * @param mixed $value
     * @return string
     */
    protected function _buildGroupHavingOp($field, string $op, $value):string{
        $db=$this->table()->db();
        $columntype=$this->columnType($field);
        return $db->quoteColumn ( $field ) . ' ' . $op . " " . $db->quoteValue ( $value,$columntype ) . " ";
    }
    /**
     * 编译Having操作
     * @param string $having_fragment
     * @param string $op
     * @param string $having
     */
    protected function _buildGroupHavingJoin($having_fragment, string $op,&$having){
        $shaving = trim ( $having );
        if (empty ( $shaving ) || substr ( $shaving, - 1, 1 ) == '(') $having .= ' ' . $having_fragment . ' ';
        else $having .= ' ' . $op . ' ' . $having_fragment . ' ';
    }
    /**
     * 编译GROUP
     * @return string
     */
    protected function _buildGroup():string {
        $db = $this->table()->db();;
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
                    $this->_buildGroupHavingJoin( call_user_func_array ( array($this,'_buildGroupHavingOp'), $method ['args'] ), 'and' ,$having);
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
    protected function _buildJoin():string{
        $sql = ' ';
        $on = false;
        $db = $this->table()->db();;
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
    protected function _buildOrderOp($field,$order):string{
        return ' '.$this->table()->db()->quoteColumn($field).' '.addslashes($order);
    }
    /**
     * 编译查询
     * @return string
     */
    protected function _buildSelect($field,$limit=null):string {
        $db = $this->table()->db();
        $distinct = false;
        foreach ( $this->_db_pending as $method ) {
            switch ($method ['name']) {
                case 'distinct' :
                    $distinct = ( bool ) $method ['args'] [0];
                    break;
            }
        }
        $distinct=$distinct?" DISTINCT ":'';
        $table=$db->quoteTable($this->table()->tableName());
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
                        if($_limit===null) $limit = null;
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
    /**
     * 编译查询字段
     * @param ColumnSet $columnset
     * @return string
     */
    protected function _buildField(ColumnSet $columnset):string {
        $columns=$columnset->asArray(ColumnSet::TYPE_FIELD);
        $db = $this->table()->db();
        $add=$_field=array();
        $pkname=$this->table()->primaryKey();
        if (!is_array($pkname)) {
            if(array_search($pkname, $columns,true)===false){
                $add[]=$pkname;
            }
        }else{
            foreach ($pkname as $tname){
                if(array_search($tname, $columns,true)===false){
                    $add[]=$tname;
                }
            }
        }
        foreach ($add as $value)
        {
            if (!is_array($value))$value=[$value];
            foreach ($value as $vv){
                array_push($_field, $db->quoteColumn(array($this->table()->tableName().".".$vv,$vv)));
            }
        }
        foreach ($columnset as $value)
        {
            if ($value instanceof \LSYS\Model\Column) {
                $value=$value->sql();
            }else $value=$db->quoteColumn(array($this->table()->tableName().".".$value->name(),$value->name()));
            array_push($_field, $value);
        }
        $field=implode(",", $_field);
        return $field;
    }
    /**
     * 通过主键查询
     * 联合主键请使用数组
     * @param string|array $pk
     * @return static
     */
    public function wherePk($pk){
        $pkname=$this->table()->primaryKey();
        if (is_array($pkname)) {
            foreach ($pkname as $name){
                $this->where($name, "=", $pk[$name]??null);
            }
        }else{
            $this->where($pkname, "=", $pk);
        }
        return $this;
    }
    /**
     * Alias of andWhere()
     * @param mixed $column
     *        	column name or array($column, $alias) or object
     * @param string $op
     *        	logic operator
     * @param mixed $value
     *        	column value
     * @return $this
     */
    public function where($column,string  $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'where',
            'args' => array (
                $this->columnQuote($column),
                $op,
                $value
            )
        );
        return $this;
    }
    /**
     * quote column
     * @param string|Expr $column
     * @return Expr|string
     */
    protected function columnQuote($column){
        if($column instanceof Expr||is_array($column))return $column;
        $column=strval($column);
        return strpos($column, ".")===false?$this->table()->tableName().".".$column:$column;
    }
    /**
     * 得到字段类型
     * @param string $column
     * @return mixed|NULL
     */
    protected function columnType($column){
        $columnset=$this->_columnSet();
        if ($column instanceof Expr) {
            $column=$column->compile( $this->table()->db());
            $p=strpos($column, '.');
            if($p!==false){
                $column=substr($column, $p+1);
            }
            $p=strpos($column, ' as ');
            if($p!==false){
                $column=substr($column,0, $p);
            }
            $column=trim($column,'"`\'');
        }else if(is_array($column))$column=$column[0]??'';
        else $column=strval($column);
        return $columnset->getType($column);
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
    public function andWhere($column, string $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andWhere',
            'args' => array (
                $this->columnQuote($column),
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
    public function orWhere($column, string $op, $value) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orWhere',
            'args' => array (
                $this->columnQuote($column),
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
    public function orderBy($column,?string $direction = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'order_by',
            'args' => array (
                $this->columnQuote($column),
                $direction
            )
        );
        
        return $this;
    }
    
    /**
     * Return up to "LIMIT ..." results
     * set null clear setting
     * @param integer $number
     *        	maximum results to return
     * @return $this
     */
    public function limit(?int $number) {
        // Add pending database call which is executed after query type is determined
        if (is_null($number)) {
            unset($this->_db_pending ['limit']);
            return $this;
        }
        $this->_db_pending ['limit'] = array (
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
    public function distinct(bool $value = true) {
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
    public function offset(?int $number) {
        if (is_null($number)) {
            unset($this->_db_pending ['offset']);
            return $this;
        }
        // Add pending database call which is executed after query type is determined
        $this->_db_pending ['offset'] = array (
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
    public function join($table, string $type = NULL) {
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
    public function on($c1, string $op, $c2) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'on',
            'args' => array (
                $this->columnQuote($c1),
                $op,
                $this->columnQuote($c2)
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
    public function groupBy($column) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'group_by',
            'args' => $this->columnQuote($column)
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
    public function having($column,string $op, $value = NULL) {
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
    public function andHaving($column,string $op, $value = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'andHaving',
            'args' => array (
                $this->columnQuote($column),
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
    public function orHaving($column, string $op, $value = NULL) {
        // Add pending database call which is executed after query type is determined
        $this->_db_pending [] = array (
            'name' => 'orHaving',
            'args' => array (
                $this->columnQuote($column),
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
