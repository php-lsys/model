<?php
namespace LSYS;
use LSYS\Entity\EntityColumnSet;
use LSYS\Entity\Table;
use LSYS\Model\Database;
use LSYS\Model\DI;
use LSYS\Model\EntitySet;
use function LSYS\Model\__;
use LSYS\Model\Related;
use LSYS\Model\Database\Database\ArrayResult;
use LSYS\Model\Database\Builder;
abstract class Model implements Table{
    private $_db;
    private $_related;
    /**
     * @return Database
     */
    public function db(Database $db=null){
        if($db)$this->_db=$db;
        if($this->_db)return $this->_db;
        return DI::get()->modelDB();
    }
    private function builderPkWheres(Builder $dbbuilder,$cols,$vals) {
        $dbbuilder->whereOpen();
        foreach ($vals as $value) {
            $dbbuilder->orWhereOpen();
            foreach ($cols as $key=>$col) {
                $dbbuilder->where($col, "=", $value[$key]);
            }
            $dbbuilder->orWhereClose();
        }
        $dbbuilder->whereClose();
    }
    private function pksKey($keys) {
        return serialize($keys);
    }
	/**
	 * 批量得到关系数据
	 * @param EntitySet $entity_set
	 * @param string $column
	 * @return NULL|\LSYS\Entity|\LSYS\Model\EntitySet[]|\LSYS\Entity[]
	 */
    public function RelatedFinds(EntitySet $entity_set,$column) {
        $model=$this->_relatedModel($column);
        if (is_null($model))return null;
        $related=$this->related();
        $out=[];
        if ($related->isBelongsTo($column)) {
            $vals=[];
            $foreign_key=$related->getBelongsToForeignKey($column);
            $keys=$model->primaryKey();
            if (!is_array($keys)) {
                foreach ($entity_set as $entity) {
                    $val=$entity->__get(strval($foreign_key));
                    if ($related->isFilter($val)) {
                        $out[$val]=$this->_createEntity($model);
                        continue;
                    }
                    $vals[]=$val;
                }
                $dbbuilder=$model->dbBuilder();
                $dbbuilder->where($keys, "in", $vals);
                $related->runBuilderCallback($column,$dbbuilder);
                foreach ($dbbuilder->findAll() as $entity){
                    $out[$entity->__get($keys)]=$entity;
                }
            }else{
                $vals=[];
                foreach ($entity_set as $entity) {
                    $val=[];
                    foreach ((array)$foreign_key as $_foreign_key){
                        $val[$_foreign_key]=$entity->__get(strval($_foreign_key));
                    }
                    if ($related->isFilter($val)) {
                        $out[$this->pksKey($val)]=$this->_createEntity($model);
                        continue;
                    }
                    $vals[]=$val;
                }
                $dbbuilder=$model->dbBuilder();
                $this->builderPkWheres($dbbuilder, array_combine($keys, $keys), $vals);
                $related->runBuilderCallback($column,$dbbuilder);
                foreach ($dbbuilder->findAll() as $entity){
                    $val=[];
                    foreach ($keys as $value) {
                        $val[$value]=$entity->__get($value);
                    }
                    $out[$this->pksKey($val)]=$entity;
                }
                foreach ($vals as $val){
                    if (isset($out[$this->pksKey($val)]))continue;
                    $out[$this->pksKey($val)]=$this->_createEntity($model);;
                }
            }
        }
        if ($related->isHasOne($column)) {
            $vals=[];
            foreach ($entity_set as $entity) {
                if (!$entity->loaded()){
                    $out[]= $this->_createEntity($model);
                }
                $vals[]=$entity->pk();
            }
            $col = $related->getHasOneForeignKey($column);
            $dbbuilder=$model->dbBuilder();
            $this->builderPkWheres($dbbuilder, array_combine($col, $col), $vals);
            $related->runBuilderCallback($column,$dbbuilder);
            foreach ($dbbuilder->findAll() as $entity){
                $out[$entity->__get($col)]=$entity;
            }
        }
        if ($related->isHasMany($column)) {
            $vals=[];
            foreach ($entity_set as $entity) {
                $vals[]=$entity->pk();
            }
            $foreign_key=$related->getHasManyForeignKey($column);
            $dbbuilder=$model->dbBuilder();
            if ($related->isHasManyThrough($column)) {
                // 中间表
                $through=$related->getHasManyThrough($column);
                if(is_array($through)&&isset($through[1])){
                    $column_table=$this->db()->quoteTable($through[1]);
                }else $column_table=$this->db()->quoteTable($through);
                
                $far_key=$related->getHasManyFarKey($column);
                
                // 中间表分别存放两个表的两个主键
                $join_col1 = $this->db()->expr($column_table . '.' . $this->db()->quoteColumn($far_key));
                $join_col2 = $model->tableName() . '.' . $model->primaryKey();
                $dbbuilder->join ($through);
                $dbbuilder->on ( $join_col1, '=', $join_col2 );
                // 连接中间表,中间表存储当前主键的字段 = 当前主键
                if (is_array($foreign_key)) {
                    $col=[];
                    foreach ($foreign_key as $_foreign_key) {
                        $col[$_foreign_key] = $this->db()->expr($column_table . '.' . $_foreign_key);
                    }
                }else{
                    $col = $this->db()->expr($column_table . '.' . $foreign_key);
                }
            }else{
                if (is_array($foreign_key)) {
                    $col=array_combine($foreign_key, $foreign_key);
                }else{
                    $col = $foreign_key;
                }
            }
            $this->builderPkWheres($dbbuilder, $col, $vals);
            $related->runBuilderCallback($column,$dbbuilder);
            $tmp=[];
            foreach ($dbbuilder->findAll() as $entity) {
                $tmp[$entity->__get($foreign_key)][]=$entity->exportData();
            }
            foreach ($tmp as $key=>$value) {
                $out[$key]=new EntitySet(
                    new ArrayResult($value),
                    $dbbuilder->table()->entityClass(),
                    $dbbuilder->getColumnSet(),
                    $dbbuilder->table()
                );
            }
            foreach ($entity_set as $entity) {
                if (isset($out[$entity->pk()]))continue;
                $out[$key]=new EntitySet(
                    new ArrayResult([]),
                    $dbbuilder->table()->entityClass(),
                    $dbbuilder->getColumnSet(),
                    $dbbuilder->table()
                );
            }
        }
        return $out;
    }
	/**
	 *
	 * @param string $column
	 * @return Model|null
	 */
    protected function _relatedModel($column) {
	    $related=$this->related();
	    $model_name=$related->modelName($column);
	    if (!isset($model_name))return null;
	    return (new \ReflectionClass($model_name))->newInstance();
	}
	/**
	 * @param string $entity_name
	 * @return Entity
	 */
	protected function _createEntity(Model $model){
	    return (new \ReflectionClass($model->entityClass()))->newInstance($model);
	}
	/**
	 * 得到关系对象
	 * @return Related
	 */
	public function related(){
	    if (is_null($this->_related)) {
	        $this->_related=$this->relatedFactory();
	    }
	    return $this->_related;
	}
	/**
	 * 创建关系对象
	 * @rewrite
	 * @return Related
	 */
	protected function relatedFactory() {
	    return new Related();
	}
	private function builderPkWhere(Builder $dbbuilder,$keys,$vals) {
	    if (is_array($keys)) {
	        $dbbuilder->where($keys, "=", $vals);
	    }else{
	        $dbbuilder->whereOpen();
            foreach ($keys as $key=>$col) {
                $dbbuilder->where($col, "=", $vals[$key]);
            }
	        $dbbuilder->whereClose();
	    }
	}
	/**
	 * 得到关系
	 * @param Entity $entity
	 * @param string $column
	 * @param array|string|EntityColumnSet $columns
	 * @return Entity|static|NULL
	 */
	public function relatedFind(Entity $entity,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if ($related->isBelongsTo($column)) {
	        $val=$entity->__get($related->getBelongsToForeignKey($column));
	        if ($related->isFilter($val)) {
	            return $this->_createEntity($model);
	        }
	        $dbbuilder=$model->dbBuilder();
	        $this->builderPkWhere($dbbuilder, $model->primaryKey(), $val);
	        $related->runBuilderCallback($column,$dbbuilder);
	        $_entity= $dbbuilder->find();
	        return $_entity;
	    }
	    if ($related->isHasOne($column)) {
	        if (!$entity->loaded()){
	            return $this->_createEntity($model);
	        }
	        $col = $related->getHasOneForeignKey($column);
	        $val= $entity->pk();
	        $dbbuilder=$model->dbBuilder();
	        $this->builderPkWhere($dbbuilder, $col, $val);
	        $related->runBuilderCallback($column,$dbbuilder);
	        $_entity= $dbbuilder->find();
	        return $_entity;
	    }
	    if ($related->isHasMany($column)) {
	        $dbbuilder=$model->dbBuilder();
	        
	        if (!$entity->loaded()) {
	            return new EntitySet(
	                new ArrayResult([]),
	                $dbbuilder->table()->entityClass(),
	                $dbbuilder->getColumnSet(),
	                $dbbuilder->table()
                );
	        }
	        
	        $foreign_key=$related->getHasManyForeignKey($column);
	        if ($related->isHasManyThrough($column)) {
	            // 中间表
	            $through=$related->getHasManyThrough($column);
	            if(is_array($through)&&isset($through[1])){
	                $column_table=$this->db()->quoteTable($through[1]);
	            }else $column_table=$this->db()->quoteTable($through);
	            
	            $far_key=$related->getHasManyFarKey($column);
	            
	            // 中间表分别存放两个表的两个主键
	            $join_col1 = $this->db()->expr($column_table . '.' . $this->db()->quoteColumn($far_key));
	            $join_col2 = $model->tableName() . '.' . $model->primaryKey();
	            $dbbuilder->join ($through);
	            $dbbuilder->on ( $join_col1, '=', $join_col2 );
	            // 连接中间表,中间表存储当前主键的字段 = 当前主键
	            if (is_array($foreign_key)) {
	                $col=[];
	                foreach ($foreign_key as $_foreign_key) {
	                    $col[$_foreign_key] = $this->db()->expr($column_table . '.' . $_foreign_key);
	                }
	            }else{
	                $col = $this->db()->expr($column_table . '.' . $foreign_key);
	            }
	            
	        }else{
	            if (is_array($foreign_key)) {
	                $col=array_combine($foreign_key, $foreign_key);
	            }else{
	                $col = $foreign_key;
	            }
	        }
	        $this->builderPkWhere($dbbuilder, $col, $entity->pk());
	        $related->runBuilderCallback($column,$dbbuilder);
	        return $dbbuilder->findAll();
	    }
	    return null;
	}
	/**
	 * 是否存在某关系
	 * @param Entity $entity
	 * @param string $alias
	 * @param mixed $far_keys
	 * @return bool
	 */
	public function relatedHas(Entity $entity,string $alias, $far_keys = NULL):bool {
	    $count = $this->countRelations ($entity,$alias, $far_keys );
		if ($far_keys === NULL) {
			return ( bool ) $count;
		} else {
			return $count === count ( $far_keys );
		}
	}
	/**
	 * 是否存某关系集
	 * @param Entity $entity
	 * @param string $alias
	 * @param mixed $far_keys
	 * @return bool
	 */
	public function relatedHasAny(Entity $entity,string $alias, $far_keys = NULL):bool {
	    return ( bool ) $this->countRelations ($entity,$alias, $far_keys );
	}
	/**
	 * 查询指定关系数量
	 * @param Entity $entity
	 * @param string $alias
	 * @param mixed $far_keys
	 * @return int
	 */
	public function countRelations(Entity $entity,string $column, $far_keys = NULL):int
	{
	    $db=$this->db();
	    $related=$this->related();
	    $has_many=$this->hasMany();
	    if ($related->isHasManyThrough($column)) return 0;
	    
	    $columns=$this->tableColumns()->offsetGet($column);
	    
		if ($far_keys === NULL)
		{
			$table=$related->getHasManyThrough($column);
			$column=$db->quoteColumn($related->getHasManyForeignKey($column));
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
		$val=$db->quoteValue($far_keys,$columns->getType($model->primaryKey()));
		
		$sql=" SELECT COUNT(*) as total FROM {$table} WHERE {$column1} = {$pk} and {$column2} IN {$val} ";
		$result=$db->query($sql);
		return (int)$result->get('total',0);
	}
	/**
	 * 添加一个关系
	 * @param Entity $entity
	 * @param string $alias
	 * @param mixed $far_keys
	 * @return bool
	 */
	public function relatedAdd(Entity $entity,string $alias, $far_keys):bool {
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
		        $db->quoteValue($entity->pk (),$this->tableColumns()->columnSet()->getType($this->primaryKey())),
		        $db->quoteValue($key,$model->tableColumns()->columnSet()->getType($model->primaryKey()))
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
	/**
	 * 移除一个关系
	 * @param Entity $entity
	 * @param string $alias
	 * @param mixed $far_keys
	 * @return bool
	 */
	public function relatedRemove(Entity $entity,string $alias, $far_keys = NULL):bool {
	    $has_many=$this->hasMany();
	    if (!isset($has_many[$alias])
	        ||!isset($has_many[$alias]['through'])
	        ||!isset($has_many [$alias] ['model'])) return false;
	    
		$db=$this->db();
		$this->_relationKeyFill($has_many [$alias],'foreign_key',$this);
		$pk=$db->quoteValue($entity->pk (),$this->tableColumns()->columnSet()->getType($this->primaryKey()));
		$where = $db->quoteColumn( $has_many [$alias] ['foreign_key'] ) . '=' . $pk;
		
		$far_keys = ($far_keys instanceof Entity) ? $far_keys->pk () : $far_keys;
		if ($far_keys !== NULL)
		{
		    
		    if (!isset ( $has_many [$alias] )
		        ||!isset($has_many [$alias] ['model'])
		        ||! is_subclass_of ( $has_many [$alias] ['model'], __CLASS__ )
		        ){
		            $msg=__("column :alias model :model not extends ORM!",array(":alias"=>$alias,":model"=>isset($related [$column] ['model'])?$related [$column] ['model']:'Unkown')) ;
		            throw new Exception($msg);
		    }
		    $model= $this->_createModel($has_many [$alias] ['model']);
		    $this->_relationKeyFill($has_many [$alias],'far_key',$model);//中间表存对方主键字段名
		    $column=$db->quoteColumn($has_many[$alias]['far_key']);
		    $val=$db->quoteValue($far_keys,$model->tableColumns()->columnSet()->getType($model->primaryKey()));
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
	public function entityClass():string
	{
	    return Entity::class;
	}
	/**
	 * 得到当前模型的完整表名
	 * @return string
	 */
	public function tableFullName():string{
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