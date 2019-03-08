<?php
namespace LSYS;
use LSYS\Entity\Exception;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\EntityColumnSet;
use LSYS\Entity\EntitySet;
use LSYS\Entity\Table;
use LSYS\Model\Database;
abstract class Model implements Table{
    /**
     * @var array
     */
    protected $_db_pending=array();
    /**
     * @var EntityColumnSet
     */
    protected $_column_set;
    /**
     * @var ColumnSet
     */
    protected $_table_columns_cache;
	/**
	 * 返回hasOne关系
	 * @return array
	 */
	public function hasOne() {
	    return [];
	}
	/**
	 * 返回belongsTo关系
	 * @return array
	 */
	public function belongsTo() {
	    return [];
	}
	/**
	 * 返回hasMany关系
	 * @return array
	 */
	public function hasMany() {
	    return [];
	}
	public function tableColumns(){
	    if (!$this->_table_columns_cache){
	        $this->_table_columns_cache=$this->db()->listColumns($this->tableName());
	    }
	    return $this->_table_columns_cache;
	}
	public function primaryKey() {
	    return 'id';
	}
	/**
	 * @param array|EntityColumnSet|string $column_set $column_set
	 * @return EntityColumnSet
	 */
	protected function _asEntityColumnSet($column_set) {
	    if (is_null($column_set))return $column_set;
	    if (is_array($column_set)) {
	        $column_set=new EntityColumnSet($column_set);
	    }else if (is_string($column_set)) {
	        $column_set=explode(",", $column_set);
	        $column_set=array_map('trim',$column_set);
	        $column_set=new EntityColumnSet($column_set);
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
	protected function _columnSet() {
	    if ($this->_column_set){
	        $columnset=$this->_column_set->asColumnSet($this->tableColumns(),true);
	    }else $columnset=$this->tableColumns();
	    return $columnset;
	}
	/**
	 * @param string $entity_name
	 * @return Entity
	 */
	protected function _createEntity(Model $model){
	    return (new \ReflectionClass($model->entityClass()))->newInstance($model);
	}
	/**
	 * @param string $model_name
	 * @return static
	 */
	protected function _createModel($model_name){
	    return (new \ReflectionClass($model_name))->newInstance();
	}
	/**
	 * 查找一个实体
	 * @return Entity
	 */
	public function find() {
	    $field=$this->_buildField($this->_columnSet());
	    $sql = $this->_buildSelect ($field,1);
	    $entity=$this->_createEntity($this);
	    $res = $this->db()->query ($sql)->current();
	    if(!is_null($res))$entity->loadData($res,$this->_column_set,true);
	    return $entity;
	}
	public function findAll() {
	    $field=$this->_buildField ($this->_columnSet());
	    $sql = $this->_buildSelect ($field);
	    $result = $this->db()->query ($sql);
	    return new EntitySet($result ,$this->entityClass(),$this->_column_set,$this);
	}
	public function countAll() {
	    $sql = $this->_buildSelect ("count(*) as total");
	    return $this->db()->queryCount($sql,"total");
	}
	/**
	 * 得到关系
	 * @param Entity $entity
	 * @param string $column
	 * @param array|string|EntityColumnSet $columns
	 * @return Entity|static|NULL
	 */
	public function related(Entity $entity,$column,$columns=null) {
	    if (isset($this->belongsTo()[$column])){
	        return $this->_findBelongsTo($this, $column,$columns);
	    }
	    if (isset($this->has_one()[$column])){
	        return $this->_findHasOne($this, $column,$columns);
	    }
	    if (isset($this->HasMany()[$column])) {
	        return $this->_findHasMany($this, $column,$columns);
	    }
	    return null;
	}
	/**
	 * 过滤空值
	 * @param array $related
	 * @param mixed $val
	 * @return boolean
	 */
	protected function _filterEmpty($related,$val){
	    $filter=array(0,NULL,FALSYSE);
	    if (isset($related['filter']))$filter=$related['filter'];
	    if (!is_array($filter))$filter=[$filter];
	    return in_array($val,$filter);
	}
	/**
	 * 填充默认关系
	 * @param array $relation
	 * @param string $key_name
	 * @param static
	 */
	private function _relationKeyFill(&$relation,$key_name,Model $orm){
	    if (!isset($relation [$key_name])) {
	        $relation [$key_name]=strtolower($orm->tableName()."_".$orm->primaryKey());
	    }
	}
	
	/**
	 * 通过关系和字段名得到对应ORM
	 * @param array $related
	 * @param string $column
	 * @throws Exception
	 * @return static
	 */
	private function _createRelated($related,$column) {
	    if (!isset ( $related [$column] )
	        ||!isset($related [$column] ['model'])
	        ||! is_subclass_of ( $related [$column] ['model'], __CLASS__ )
	        ){
	            $msg=$this->i18n()->__("column :alias model :model not extends ORM!",array(":alias"=>$column,":model"=>isset($related [$column] ['model'])?$related [$column] ['model']:'Unkown')) ;
	            throw new Exception($msg);
	    }
	    return $this->_createModel($related [$column] ['model']);
	}
	
	/**
	 * 本身存对方主键
	 * @param Entity $entity
	 * @param string $column
	 * @param string $columns
	 * @throws Exception
	 * @return Entity
	 */
	protected function _findBelongsTo(Entity $entity,$column,$columns=null){
	    $belongs_to=$this->belongsTo();
	    $model=$this->_createRelated($belongs_to, $column);
	    $this->_relationKeyFill($belongs_to [$column],'foreign_key',$model);
	    $val = $entity->__get($belongs_to [$column] ['foreign_key']);
	    if ($this->_filterEmpty($belongs_to [$column], $val)){
	        return $this->_createEntity($model);
	    }
	    $model->columnSet($columns);
	    $model->where($model->primaryKey(), "=", $val);
	    return $model->find();
	}
	/**
	 * 对方有一条记录存本身主键
	 * @param Entity $entity
	 * @param string $column
	 * @param string $columns
	 * @throws Exception
	 * @return Entity
	 */
	protected function _findHasOne(Entity $entity,$column,$columns=null){
	    $has_one=$this->hasOne();
	    $model=$this->_createRelated($has_one, $column);
	    if (!$entity->loaded()){
	        return $this->_createEntity($model);
	    }
	    $this->_relationKeyFill($has_one [$column],'foreign_key',$this);//填充默认对方存本身主键字段名
	    $col = $has_one [$column] ['foreign_key'];
	    $val=$entity->pk();
	    $model->columnSet($columns);
	    $model->where($col, "=", $val);
	    return $model->find();
	}
	/**
	 * 对方有多条记存本身主键
	 * @param Entity $entity
	 * @param string $column
	 * @param string $columns
	 * @throws Exception
	 * @return static
	 */
	protected function _findHasMany(Entity $entity,$column,$columns=null){
	    // 方式1
	    //			"model"=>"对方模型名",
	    //			"foreign_key"=>"对方存本身主键的字段名",
	    // 方式2
	    //		"model"=>"对方模型名",
	    //			"through"=>"关系表名",
	    //			"far_key"=>"关系表存对方主键的字段名",
	    //			"foreign_key"=>"关系表存本身主键的字段名",
	    $has_many=$this->hasMany();
	    $model=$this->_createRelated($has_many, $column);
	    $model->columnSet($columns);
	    $this->_relationKeyFill($has_many [$column],'foreign_key',$this);//默认对方存本身主键字段名 或 中间表存本身主键字段名
	    
	    if (isset ( $has_many [$column] ['through'] )) {
	        
	        $this->_relationKeyFill($has_many [$column],'far_key',$model);//中间表存对方主键字段名
	        // 中间表
	        $through = $has_many [$column] ['through'];
	        // 中间表分别存放两个表的两个主键
	        $join_col1 = $through . '.' . $has_many [$column] ['far_key'];
	        $join_col2 = $model->tableName() . '.' . $model->primaryKey();
	        $model->join ( array (
	            $through,
	            $through
	        ) );
	        $model->on ( $join_col1, '=', $join_col2 );
	        // 连接中间表,中间表存储当前主键的字段 = 当前主键
	        $col = $through . '.' . $has_many [$column] ['foreign_key'];
	    } else {
	        $col = $has_many [$column] ['foreign_key'];
	    }
	    $model->where ( $col, '=', $entity->pk() );
	    return $model;
	}
	public function has(Entity $entity,$alias, $far_keys = NULL) {
	    $count = $this->countRelations ($entity,$alias, $far_keys );
		if ($far_keys === NULL) {
			return ( bool ) $count;
		} else {
			return $count === count ( $far_keys );
		}
	}
	public function hasAny(Entity $entity,$alias, $far_keys = NULL) {
	    return ( bool ) $this->countRelations ($entity,$alias, $far_keys );
	}
	public function countRelations(Entity $entity,$alias, $far_keys = NULL)
	{
	    $db=$this->db();
	    $has_many=$this->hasMany();
	    if (!isset($has_many[$alias])||!isset($has_many[$alias]['through'])) return 0;
	    
	    $this->_relationKeyFill($has_many [$alias],'foreign_key',$this);
	    
	    $columns=$this->tableColumns();
	    
		if ($far_keys === NULL)
		{
			$table=$db->quoteTable($has_many[$alias]['through']);
			$column=$db->quoteColumn($has_many[$alias]['foreign_key']);
			$pk=$db->quoteValue($entity->pk(),$columns->getType($this->primaryKey()));
			$sql=" SELECT COUNT(*) as total FROM {$table} WHERE {$column} = {$pk}";
			$result=$db->query($sql);
			return (int)$result->get('total',0);
		}
		$far_keys = ($far_keys instanceof Entity) ? $far_keys->pk() : $far_keys;
		// We need an array to simplify the logic
		$far_keys = (array) $far_keys;
		// Nothing to check if the model isn't loaded or we don't have any far_keys
		if ( ! $far_keys OR ! $entity->loaded()) return 0;
		
		$model = $this->_createModel($has_many [$alias]  ['model']);
		$this->_relationKeyFill($has_many [$alias],'far_key',$model);//中间表存对方主键字段名
		
		$table=$db->quoteTable($has_many[$alias]['through']);
		$column1=$db->quoteColumn($has_many[$alias]['foreign_key']);
		$column2=$db->quoteColumn($has_many[$alias]['far_key']);
		$pk=$db->quoteValue($entity->pk(),$columns->getType($this->primaryKey()));
		$val=$db->quoteValue($far_keys,$model->tableColumns()->getType($model->primaryKey()));
		
		$sql=" SELECT COUNT(*) as total FROM {$table} WHERE {$column1} = {$pk} and {$column2} IN {$val} ";
		$result=$db->query($sql);
		return (int)$result->get('total',0);
	}
	public function add(Entity $entity,$alias, $far_keys) {
	    $has_many=$this->hasMany();
	    if (!isset($has_many[$alias])
	        ||!isset($has_many[$alias]['through'])
	        ||!isset($has_many [$alias] ['model'])) return false;
	    
		$far_keys = ($far_keys instanceof Entity) ? $far_keys->pk () : $far_keys;
		$db=$this->db();
		$this->_relationKeyFill($has_many [$alias],'foreign_key',$this);
		
		$model = $this->_createModel($has_many [$alias] ['model']);
		
		$this->_relationKeyFill($has_many [$alias],'far_key',$model);//中间表存对方主键字段名
		

		$field=array(
		    $db->quoteColumn($has_many [$alias] ['foreign_key']),
		    $db->quoteColumn($has_many [$alias] ['far_key'])
		);
		$datas=array();
		foreach ( ( array ) $far_keys as $key ) {
		    $data=array(
		        $db->quoteValue($entity->pk (),$this->tableColumns()->getType($this->primaryKey())),
		        $db->quoteValue($key,$model->tableColumns()->getType($model->primaryKey()))
		    );
		    $data='('.implode(",", $data).')';
		    array_push($datas,$data);
		}
		$str_field=implode(",",$field);
		$str_data=implode(",", $data);
		$table=$db->quoteTable($has_many[$alias]['through']);
		$sql=" INSERT INTO ".$table." (".$str_field.")VALUES {$str_data}";
		return $db->exec($sql);
	}
	public function remove(Entity $entity,$alias, $far_keys = NULL) {
	    $has_many=$this->hasMany();
	    if (!isset($has_many[$alias])
	        ||!isset($has_many[$alias]['through'])
	        ||!isset($has_many [$alias] ['model'])) return false;
	    
		$db=$this->db();
		$this->_relationKeyFill($has_many [$alias],'foreign_key',$this);
		$pk=$db->quoteValue($entity->pk (),$this->tableColumns()->getType($this->primaryKey()));
		$where = $db->quoteColumn( $has_many [$alias] ['foreign_key'] ) . '=' . $pk;
		
		$far_keys = ($far_keys instanceof Entity) ? $far_keys->pk () : $far_keys;
		if ($far_keys !== NULL)
		{
		    $model=$this->_createRelated($has_many, $alias);
		    $this->_relationKeyFill($has_many [$alias],'far_key',$model);//中间表存对方主键字段名
		    $column=$db->quoteColumn($has_many[$alias]['far_key']);
		    $val=$db->quoteValue($far_keys,$model->tableColumns()->getType($model->primaryKey()));
		    $where.=" AND {$column} IN {$val}";
		}
		$table=$db->quoteTable($has_many[$alias]['through']);
		$sql=" DELETE FROM ".$table." WHERE ".$where;
		return $db->exec($sql);
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
	 * @return $this
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
	abstract public function entityClass();
	/**
	 * @return Database
	 */
	public function db(){
	    return DI::get()->LSYSORMDB();
	}
	public function i18n(){
	    return DI::get()->LSYSORMI18n();
	}
}