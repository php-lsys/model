<?php
namespace LSYS;
use LSYS\Entity\Table;
use LSYS\Model\Database;
use LSYS\Model\DI;
use LSYS\Model\EntitySet;
use function LSYS\Model\__;
use LSYS\Model\Related;
use LSYS\Model\Database\Builder;
use LSYS\Model\Column;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\EntityColumnSet;
use LSYS\Model\Exception;
abstract class Model implements Table{
    private $_db;
    private $_related;
    /**
     * 返回实体名
     * @rewrite
     * @return string
     */
    abstract public function entityClass():string;
    /**
     * 创建关系对象
     * @rewrite
     * @return Related
     */
    protected function relatedFactory() {
        return new Related();
    }
    /**
     * {@inheritDoc}
     * @return \LSYS\Model\Database\Builder
     */
    public function dbBuilder() {
        return $this->db()->SQLBuilder($this);
    }
    /**
     * @return Database
     */
    public function db(Database $db=null){
        if($db)$this->_db=$db;
        if($this->_db)return $this->_db;
        return DI::get()->modelDB();
    }
    /**
     * 一对多关系结果数量
     * @param string $column
     * @return ?int
     */
    public function hasManyCount(Entity $entity,string $column) {
        $model=$this->_relatedModel($column);
        if (is_null($model))return null;
        $related=$this->related();
        if (!$related->isHasMany($column))return null;
        if (!$entity->loaded()) {
            return 0;
        }
        $out=$this->_hasManyCount($model,[$entity], $column);
        $out=current($out);
        return $out===false?null:$out;
    }
    /**
     * 批量查一对多关系结果数量
     * @param string $column
     * @return ?int[]
     */
    public function hasManysCount(EntitySet $entity_set,string $column) {
        $model=$this->_relatedModel($column);
        if (is_null($model))return null;
        $related=$this->related();
        if (!$related->isHasMany($column))return null;
        return $this->_hasManyCount($model,$entity_set, $column);
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
	 * 获取一[本身]对多[对方]关系对象
	 * @param Entity $entity
	 * @param string $column
	 * @return NULL|\LSYS\Model\Entity
	 */
	public function hasMany(Entity $entity,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isHasMany($column))return null;
	    if (!$entity->loaded()) {
	        return $this->_builderEntitySet($model->dbBuilder(), []);
	    }
        $out=$this->_hasMany($model,[$entity], $column);
        $out=current($out);
        return $out===false?null:$out;
	}
	/**
	 * 获取一[对方]对一[本身]关系
	 * @param Entity $entity
	 * @param string $column
	 * @return NULL|\LSYS\Model\Entity
	 */
	public function hasOne(Entity $entity,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isHasOne($column))return null;
	    if (!$entity->loaded()) {
	        return $this->_createEntity($model);
	    }
	    $out=$this->_hasOne($model,[$entity], $column);
        $out=current($out);
        return $out===false?null:$out;
	}
	/**
	 * 获取(多或一[对方])对一[本身]关系对象
	 * @param Entity $entity
	 * @param string $column
	 * @return NULL|\LSYS\Model\EntitySet
	 */
	public function belongsTo(Entity $entity,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isBelongsTo($column))return null;
	    if (!$entity->loaded()) {
	        return $this->_createEntity($model);
	    }
	    $out=$this->_belongsTo($model,[$entity], $column);
        $out=current($out);
        return $out===false?null:$out;
	}
	/**
	 * 批量获取一[本身]对多[对方]关系对象
	 * @param EntitySet $entity_set
	 * @param string $column
	 * @return NULL|\LSYS\Model\EntitySet[]
	 */
	public function hasManys(EntitySet $entity_set,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isHasMany($column))return null;
	    return $this->_hasMany($model,$entity_set, $column);
	}
	/**
	 * 批量获取一[对方]对一[本身]关系
	 * @param EntitySet $entity_set
	 * @param string $column
	 * @return NULL|\LSYS\Entity[]
	 */
	public function hasOnes(EntitySet $entity_set,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isHasOne($column))return null;
	    return $this->_hasOne($model,$entity_set, $column);
	}
	/**
	 * 批量获取(多或一[对方])对一[本身]关系对象
	 * @param EntitySet $entity_set
	 * @param string $column
	 * @return NULL|\LSYS\Entity[]
	 */
	public function belongsTos(EntitySet $entity_set,string $column) {
	    $model=$this->_relatedModel($column);
	    if (is_null($model))return null;
	    $related=$this->related();
	    if (!$related->isBelongsTo($column))return null;
	    return $this->_belongsTo($model,$entity_set, $column);
	}
	/**
	 * 得到当前模型的完整表名
	 * @return string
	 */
	public function tableFullName():string{
	    return $this->db()->quoteTable($this->tableName());
	}
	/**
	 * 一对多关系结果数量
	 * @param string $column
	 * @return int[]
	 */
	private function _hasManyCount(Model $model,$entity_set,string $column) {
	    $related=$this->related();
	    //一[本身]对多[对方]关系
	    $out=[];
	    $foreign_key=$related->getHasManyForeignKey($column);
	    if (!is_array($foreign_key)) {
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $vals[]=strval($entity->pk());
	        }
	        if (empty($vals))return $out; 
	        $dbbuilder=$model->dbBuilder();
	        if ($related->isHasManyThrough($column)) {
	            // 中间表
	            $through=$related->getHasManyThrough($column);
	            if(is_array($through)&&isset($through[1])){
	                $column_table_=$through[1];
	                $column_table=$this->db()->quoteTable($column_table_);
	            }else{
	                $column_table_=$through;
	                $column_table=$this->db()->quoteTable($column_table_);
	            }
	            //$foreign_key 中间表本身主键字段
	            //$far_key 中间表存对方主键字段
	            $far_key=strval($related->getHasManyFarKey($column));//中间表存本身字段名
	            // 中间表分别存放两个表的两个主键
	            $join_col1 = $this->db()->expr($column_table . '.' . $this->db()->quoteColumn($far_key));
	            $join_col2 = $model->tableName() . '.' . $model->primaryKey();
	            $dbbuilder->join ($through);
	            $dbbuilder->on ( $join_col1, '=', $join_col2 );//中间表连对方模型
	            // 连接中间表,中间表存储当前主键的字段 = 当前主键
	            $col = $this->db()->expr($column_table . '.' . $foreign_key);
	            $dbbuilder->where($col, "in", $vals);
	            $rc=$column_table_ . '_' . $foreign_key;
	            $ac=$column_table . '.' . $foreign_key." as ".$rc;
	            $dbbuilder->columnSet(new EntityColumnSet([],[
	                new Column($rc,$ac),
	                new Column('total','count(*) as total'),
	            ]));
	        }else{
	            //$foreign_key 对方存本身主键字段
	            $rc = $col = strval($foreign_key);
	            $dbbuilder->where($col, "in", $vals);
	            $dbbuilder->columnSet(new EntityColumnSet([],[
	                new Column($rc),
	                new Column('total','count(*) as total'),
	            ]));
	        }
	        $dbbuilder->groupBy($col);
	        $related->runBuilderCallback($column,$dbbuilder);
	        foreach ($dbbuilder->findAll() as $entity) {
	            $out[$entity->__get($rc)]=intval($entity->__get('total'));
	        }
	        foreach ($entity_set as $entity) {
	            $pk=$entity->pk();
	            if (isset($out[$pk]))continue;
	            $out[$pk]=0;
	        }
	    }else{
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $vals[]=(array)$entity->pk();
	        }
	        if (empty($vals))return $out;
	        $dbbuilder=$model->dbBuilder();
	        if ($related->isHasManyThrough($column)) {
	            // 中间表
	            $through=$related->getHasManyThrough($column);
	            if(is_array($through)&&isset($through[1])){
	                $column_table_=$through[1];
	                $column_table=$this->db()->quoteTable($column_table_);
	            }else{
	                $column_table_=$through;
	                $column_table=$this->db()->quoteTable($column_table_);
	            }
	            //$foreign_key 中间表本身主键字段
	            //$far_key 中间表存对方主键字段
	            $far_key=$related->getHasManyFarKey($column);//[对方表字段=>中间表字段]
	            $model_key=$model->primaryKey();
	            if (!is_array($model_key)){
	                throw new Exception(__("model :model primary key must be array,may be related setting is wrong.",[":model"=>get_class($model)]));
	            }
	            $dbbuilder->join ($through);
	            foreach ((array)$model_key as $_model_key){
	                $join_col1 = $this->db()->expr($column_table . '.' . $far_key[$_model_key]);
	                $join_col2 = $model->tableName() . '.' . $_model_key;
	                $dbbuilder->on ( $join_col1, '=', $join_col2 );
	            }
	            // $foreign_key [当前表字段=>中间表字段]
	            $rc=$col=[];
	            foreach ($foreign_key as $entity_key=>$_foreign_key) {
	                $col[$entity_key] = $this->db()->expr($column_table . '.' . $_foreign_key);
	            }
	            $this->_builderPkWheres($dbbuilder, $col, $vals);
	            
	            $columns=[];
	            foreach ($foreign_key as $entity_key=>$_foreign_key) {
	                $_rc=$column_table_ . '_' . $_foreign_key;
	                $ac=$column_table . '.' . $_foreign_key." as ".$_rc;
	                foreach ($col as $v){
	                    $columns[]=new Column($_rc,$ac);
	                }
	                $rc[$entity_key]=$_rc;
	            }
	            $columns[]=new Column('total','count(*) as total');
	            $dbbuilder->columnSet(new EntityColumnSet([],$columns));
	        }else{
	            //$foreign_key [本身主键名=>对方存本身主键名]
	            $rc=$col=$foreign_key;
	            $columns=[];
	            foreach ($col as $v){
	                $columns[]=new Column($v);
	            }
	            $columns[]=new Column('total','count(*) as total');
	            $dbbuilder->columnSet(new EntityColumnSet([],$columns));
	            $this->_builderPkWheres($dbbuilder, $col, $vals);
	        }
	        $related->runBuilderCallback($column,$dbbuilder);
	        foreach ($col as $_col) {
	            $dbbuilder->groupBy($_col);
	        }
	        foreach ($dbbuilder->findAll() as $entity) {
	            $val=[];
	            foreach ($rc as $entity_key=>$_foreign_key){
	                $val[$entity_key]=strval($entity->__get($_foreign_key));
	            }
	            ksort($val);
	            $out[serialize($val)]=intval($entity->__get('total'));
	        }
	        foreach ($entity_set as $entity) {
	            $key=(array)$entity->pk();
	            $key=array_map('strval', $key);
	            ksort($key);
	            $key=serialize($key);
	            if (isset($out[$key]))continue;
	            $out[$key]=0;
	        }
	    }
	    return $out;
	}
	/**
	 * 获取一对多实现
	 * @param Model $model
	 * @param array|\LSYS\Model\EntitySet $entity_set
	 * @param string $column
	 * @return \LSYS\Model\EntitySet[]
	 */
	private function _hasMany(Model $model,$entity_set,string $column) {
	    $related=$this->related();
	    //一[本身]对多[对方]关系
	    $out=[];
	    $foreign_key=$related->getHasManyForeignKey($column);
	    if (!is_array($foreign_key)) {
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $vals[]=strval($entity->pk());
	        }
	        if (!empty($vals)) {
    	        $dbbuilder=$model->dbBuilder();
    	        if ($related->isHasManyThrough($column)) {
    	            // 中间表
    	            $through=$related->getHasManyThrough($column);
    	            if(is_array($through)&&isset($through[1])){
    	                $column_table_=$through[1];
    	                $column_table=$this->db()->quoteTable($column_table_);
    	            }else{
    	                $column_table_=$through;
    	                $column_table=$this->db()->quoteTable($column_table_);
    	            }
    	            //$foreign_key 中间表本身主键字段
    	            //$far_key 中间表存对方主键字段
    	            $far_key=strval($related->getHasManyFarKey($column));//中间表存本身字段名
    	            // 中间表分别存放两个表的两个主键
    	            $join_col1 = $this->db()->expr($column_table . '.' . $this->db()->quoteColumn($far_key));
    	            $join_col2 = $model->tableName() . '.' . $model->primaryKey();
    	            $dbbuilder->join ($through);
    	            $dbbuilder->on ( $join_col1, '=', $join_col2 );//中间表连对方模型
    	            // 连接中间表,中间表存储当前主键的字段 = 当前主键
    	            $col = $this->db()->expr($column_table . '.' . $foreign_key);
    	            $dbbuilder->where($col, "in", $vals);
    	            $related->runBuilderCallback($column,$dbbuilder);
    	            $rc=$column_table_ . '_' . $foreign_key;
    	            $ac=$column_table . '.' . $foreign_key." as ".$rc;
    	            $cset=new EntityColumnSet($model->tableColumns()->asArray(ColumnSet::TYPE_FIELD),[new \LSYS\Model\Column($rc,$ac)]);
    	            $dbbuilder->columnSet($cset);
    	        }else{
    	            //$foreign_key 对方存本身主键字段
    	            $col = strval($foreign_key);
    	            $rc = $col;
    	            $dbbuilder->where($col, "in", $vals);
    	            $related->runBuilderCallback($column,$dbbuilder);
    	        }
    	        $tmp=[];
    	        foreach ($dbbuilder->findAll() as $entity) {
    	            $tmp[$entity->__get($rc)][]=$entity->exportData();
    	        }
    	        foreach ($tmp as $key=>$value) {
    	            $out[$key]=$this->_builderEntitySet($dbbuilder, $value);
    	        }
    	        foreach ($entity_set as $entity) {
    	            $pk=$entity->pk();
    	            if (isset($out[$pk]))continue;
    	            $out[$pk]=$this->_builderEntitySet($dbbuilder, []);
    	        }
	        }
	    }else{
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $vals[]=(array)$entity->pk();
	        }
	        if (!empty($vals)) {
    	        $dbbuilder=$model->dbBuilder();
    	        if ($related->isHasManyThrough($column)) {
    	            // 中间表
    	            $through=$related->getHasManyThrough($column);
    	            if(is_array($through)&&isset($through[1])){
    	                $column_table_=$through[1];
    	                $column_table=$this->db()->quoteTable($column_table_);
    	            }else{
    	                $column_table_=$through;
    	                $column_table=$this->db()->quoteTable($column_table_);
    	            }
    	            //$foreign_key 中间表本身主键字段
    	            //$far_key 中间表存对方主键字段
    	            $far_key=$related->getHasManyFarKey($column);//[对方表字段=>中间表字段]
    	            $model_key=$model->primaryKey();
    	            if (!is_array($model_key)) $far_key=[$model_key=>strval($far_key)];
    	            $dbbuilder->join ($through);
    	            foreach ($model_key as $_model_key){
    	                $join_col1 = $this->db()->expr($column_table . '.' . $far_key[$_model_key]);
    	                $join_col2 = $model->tableName() . '.' . $_model_key;
    	                $dbbuilder->on ( $join_col1, '=', $join_col2 );
    	            }
    	            // $foreign_key [当前表字段=>中间表字段]
    	            $rc=$col=[];
    	            foreach ($foreign_key as $entity_key=>$_foreign_key) {
    	                $col[$entity_key] = $this->db()->expr($column_table . '.' . $_foreign_key);
    	            }
    	            $this->_builderPkWheres($dbbuilder, $col, $vals);
    	            $related->runBuilderCallback($column,$dbbuilder);
    	            $patch=[];
    	            foreach ($foreign_key as $entity_key=>$_foreign_key) {
    	                $_rc=$column_table_ . '_' . $_foreign_key;
    	                $ac=$column_table . '.' . $_foreign_key." as ".$_rc;
    	                $patch[]=new \LSYS\Model\Column($_rc,$ac);
    	                $rc[$entity_key]=$_rc;
    	            }
    	            $dbbuilder->columnSet(new EntityColumnSet($dbbuilder->table()->tableColumns()->asArray(ColumnSet::TYPE_FIELD),$patch));
    	        }else{
    	            //$foreign_key [本身主键名=>对方存本身主键名]
    	            $rc=$col=$foreign_key;
    	            $this->_builderPkWheres($dbbuilder, $col, $vals);
    	            $related->runBuilderCallback($column,$dbbuilder);
    	        }
    	        
    	        $tmp=[];
    	        foreach ($dbbuilder->findAll() as $entity) {
    	            $val=[];
    	            foreach ($rc as $entity_key=>$_foreign_key){
    	                $val[$entity_key]=strval($entity->__get($_foreign_key));
    	            }
    	            ksort($val);
    	            $tmp[serialize($val)][]=$entity->exportData();
    	        }
    	        foreach ($tmp as $key=>$value) {
    	            $out[$key]=$this->_builderEntitySet($dbbuilder, $value);
    	        }
    	        foreach ($entity_set as $entity) {
    	            $key=$entity->pk();
    	            $key=array_map('strval', $key);
    	            ksort($key);
    	            $key=serialize($key);
    	            if (isset($out[$key]))continue;
    	            $out[$key]=$this->_builderEntitySet($dbbuilder, []);
    	        }
	        }
	    }
	    return $out;
	}
	/**
	 * 获取一对一实现
	 * @param Model $model
	 * @param array|\LSYS\Model\EntitySet $entity_set
	 * @param string $column
	 * @return \LSYS\Model\Entity[]
	 */
	private function _hasOne(Model $model,$entity_set,string $column) {
	    $related=$this->related();
	    //对方存本身主键
	    $out=[];
	    $col = $related->getHasOneForeignKey($column);//对方键名
	    if (!is_array($col)) {
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $vals[]=$entity->pk();
	        }
	        if (!empty($vals)){
    	        $vals=array_unique($vals);
    	        $dbbuilder=$model->dbBuilder();
    	        $dbbuilder->where($col, "in", $vals);
    	        $related->runBuilderCallback($column,$dbbuilder);
    	        foreach ($dbbuilder->findAll() as $entity){
    	            $out[$entity->__get($col)]=$entity;
    	        }
    	        foreach ($vals as $val){
    	            if (isset($out[$val]))continue;
    	            $out[$val]=$this->_createEntity($model);;
    	        }
	        }
	    }else{
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $tmp=(array)$entity->pk();
	            $tmp=array_map('strval', $tmp);
	            $vals[]=$tmp;
	        }
	        if (!empty($vals)){
    	        $dbbuilder=$model->dbBuilder();
    	        //$col ['本身字段'=>'对方字段']
    	        $this->_builderPkWheres($dbbuilder,$col, $vals);
    	        $related->runBuilderCallback($column,$dbbuilder);
    	        foreach ($dbbuilder->findAll() as $entity){
    	            $val=[];
    	            foreach ($col as $tk=>$value) {
    	                $val[$tk]=strval($entity->__get($value));
    	            }
    	            ksort($val);
    	            $out[serialize($val)]=$entity;
    	        }
    	        foreach ($vals as $val){
    	            ksort($val);
    	            $key=serialize($val);
    	            if (isset($out[$key]))continue;
    	            $out[$key]=$this->_createEntity($model);;
    	        }
	        }
	    }
	    return $out;
	}
	/**
	 * 获取多对一实现
	 * @param Model $model
	 * @param array|\LSYS\Model\EntitySet $entity_set
	 * @param string $column
	 * @return \LSYS\Model\Entity[]
	 */
	private function _belongsTo(Model $model,$entity_set,string $column) {
	    $related=$this->related();
	    //本身存对方主键
	    $out=[];
	    $foreign_key=$related->getBelongsToForeignKey($column);
	    $keys=$model->primaryKey();
	    if (!is_array($keys)) {
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $val=$entity->__get(strval($foreign_key));//得到对方主键
	            if ($related->isFilter($column,$val)) {
	                $out[$val]=$this->_createEntity($model);
	                continue;
	            }
	            $vals[]=$val;
	        }
	        if (!empty($vals)){
    	        $vals=array_unique($vals);
    	        $dbbuilder=$model->dbBuilder();
    	        $dbbuilder->where($keys, "in", $vals);
    	        $related->runBuilderCallback($column,$dbbuilder);
    	        $tmp=[];
    	        foreach ($dbbuilder->findAll() as $entity){
    	            $tmp[$entity->pk()]=$entity;
    	        }
    	        foreach ($entity_set as $entity) {
    	            $rpk=$entity->pk();
    	            if (is_array($rpk)) {
    	                $rpk=array_map('strval', $rpk);
    	                ksort($rpk);
    	                $rpk=serialize($rpk);
    	            }
    	            $out[$rpk]=$tmp[$entity->__get(strval($foreign_key))]??$this->_createEntity($model);
    	        }
	        }
	    }else{
	        $vals=[];
	        foreach ($entity_set as $entity) {
	            $val=[];
	            //$foreign_key=['对方键名'=>'本身键名'];
	            foreach ((array)$foreign_key as $_foreign_key=>$_foreign_val){
	                $val[$_foreign_key]=strval($entity->__get(strval($_foreign_val)));//得到对方主键
	            }
	            if ($related->isFilter($column,$val)) {
	                ksort($val);
	                $out[serialize($val)]=$this->_createEntity($model);
	                continue;
	            }
	            $vals[]=$val;
	        }
	        if (!empty($vals)){
    	        $dbbuilder=$model->dbBuilder();
    	        $this->_builderPkWheres($dbbuilder, array_combine($keys, $keys), $vals);
    	        $related->runBuilderCallback($column,$dbbuilder);
    	        
    	        $tmp=[];
    	        foreach ($dbbuilder->findAll() as $entity){
    	            $val=[];
    	            foreach ($foreign_key as $tk=>$sval) {
    	                $val[$sval]=strval($entity->__get($tk));
    	            }
    	            ksort($val);
    	            $tmp[serialize($val)]=$entity;
    	        }
    	        foreach ($entity_set as $entity) {
    	            $val=[];
    	            foreach ($foreign_key as $sval) {
    	                $val[$sval]=strval($entity->__get($sval));
    	            }
    	            $rpk=$entity->pk();
    	            if (is_array($rpk)) {
    	                $rpk=array_map('strval', $rpk);
    	                ksort($rpk);
    	                $rpk=serialize($rpk);
    	            }
    	            ksort($val);
    	            $out[$rpk]=$tmp[serialize($val)]??$this->_createEntity($model);
    	        }
	        }
	    }
	    return $out;
	}
	/**
	 * 构造结果集对象
	 * @param Builder $dbbuilder
	 * @param array $cols
	 * @param array $vals
	 */
	private function _builderEntitySet(Builder $dbbuilder,array $val) {
	    return new EntitySet(
	        new \LSYS\Model\Database\ArrayResult($val),
	        $dbbuilder->table()->entityClass(),
	        $dbbuilder->columnGet(),
	        $dbbuilder->table()
        );
	}
	private function _builderPkWheres(Builder $dbbuilder,array $cols,array $vals) {
	    $dbbuilder->whereOpen();
	    foreach ($vals as $value) {
	        $dbbuilder->orWhereOpen();
	        foreach ($cols as $key=>$col) {
	            $dbbuilder->where($col, "=", $value[$key]??NULL);
	        }
	        $dbbuilder->orWhereClose();
	    }
	    $dbbuilder->whereClose();
	}
	/**
	 * 根据字段名得到模型对象
	 * @param string $column
	 * @return Model|null
	 */
	private function _relatedModel($column) {
	    $related=$this->related();
	    $model_name=$related->modelName($column);
	    if (empty($model_name))return null;
	    return (new \ReflectionClass($model_name))->newInstance();
	}
	/**
	 * @param string $entity_name
	 * @return Entity
	 */
	private function _createEntity(Model $model){
	    return (new \ReflectionClass($model->entityClass()))->newInstance($model);
	}
}