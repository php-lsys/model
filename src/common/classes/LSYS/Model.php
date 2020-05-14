<?php
namespace LSYS;
use LSYS\Entity\Exception;
use LSYS\Entity\EntityColumnSet;
use LSYS\Entity\Table;
use LSYS\Model\Database;
use LSYS\Model\Traits\ModelTableColumnsFromDB;
use LSYS\Model\DI;
abstract class Model implements Table{
    use ModelTableColumnsFromDB;
    /**
     * 工厂方法
     * @return \LSYS\Model
     */
    public static function factory(){
        return new static();
    }
    private $_db;
    /**
     * @return Database
     */
    public function db(Database $db=null){
        if($db)$this->_db=$db;
        if($this->_db)return $this->_db;
        return DI::get()->modelDB();
    }
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
	 * 得到关系
	 * @param Entity $entity
	 * @param string $column
	 * @param array|string|EntityColumnSet $columns
	 * @return Entity|static|NULL
	 */
	public function related(Entity $entity,$column,$columns=null) {
	    if (isset($this->belongsTo()[$column])){
	        return $this->_findBelongsTo($entity, $column,$columns);
	    }
	    if (isset($this->hasOne()[$column])){
	        return $this->_findHasOne($entity, $column,$columns);
	    }
	    if (isset($this->HasMany()[$column])) {
	        return $this->_findHasMany($entity, $column,$columns);
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
	    $filter=array(0,NULL,FALSE);
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
	    $_entity= $model->find();
	    $model->reset();
	    return $_entity;
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
	    $dbbuilder=$model->db()->SQLBuilder($this);
	    $dbbuilder->columnSet($columns);
	    $dbbuilder->where($col, "=", $val);
	    $_entity= $dbbuilder->find();
	    $dbbuilder->reset();
	    return $_entity;
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
	    $dbbuilder=$model->db()->SQLBuilder($this);
	    $dbbuilder->columnSet($columns);
	    $this->_relationKeyFill($has_many [$column],'foreign_key',$this);//默认对方存本身主键字段名 或 中间表存本身主键字段名
	    
	    if (isset ( $has_many [$column] ['through'] )) {
	        
	        $this->_relationKeyFill($has_many [$column],'far_key',$model);//中间表存对方主键字段名
	        // 中间表
	        $through = $has_many [$column] ['through'];
	        // 中间表分别存放两个表的两个主键
	        $join_col1 = $through . '.' . $has_many [$column] ['far_key'];
	        $join_col2 = $model->tableName() . '.' . $model->primaryKey();
	        $dbbuilder->join ( array (
	            $through,
	            $through
	        ) );
	        $dbbuilder->on ( $join_col1, '=', $join_col2 );
	        // 连接中间表,中间表存储当前主键的字段 = 当前主键
	        $col = $through . '.' . $has_many [$column] ['foreign_key'];
	    } else {
	        $col = $has_many [$column] ['foreign_key'];
	    }
	    $dbbuilder->where ( $col, '=', $entity->pk() );
	    return $dbbuilder;
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

	/**
	 * 返回实体名
	 * @return string
	 */
	public function entityClass()
	{
	    return Entity::class;
	}
	/**
	 * 得到当前模型的完整表名
	 * @return string
	 */
	public function tableFullName(){
	    return $this->db()->quoteTable($this->tableName());
	}
	/**
	 * {@inheritDoc}
	 * @return \LSYS\Model\Database\Builder
	 */
	public function dbBuilder() {
	    return $this->db()->SQLBuilder($this);
	}
}