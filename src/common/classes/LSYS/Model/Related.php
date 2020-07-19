<?php
namespace LSYS\Model;
use LSYS\Model;
use LSYS\Model\Database\Builder;

class Related{
    protected $_has_many=[];
    protected $_has_one=[];
    protected $_belongsto=[];
    protected $_filter=[];
    /**
     * 一[本身]对多[对方]关系
     * 对方(或第三方)存本身主键,但对方(或第三方)有多条记录
     * @param string $column 对外字段名
     * @param string $model 对方模型
     * @param string $foreign_key 对方存本身主键的字段
     * @return \LSYS\Model\Related
     */
    public function addHasMany(
        string $column,
        string $model,
        string $foreign_key
        ){
        $this->_belongsto[$column]=array(
            
        );
        return $this;
    }
    /**
     * 一[本身]对多[对方]关系
     * 通过第三方关系表存放的一对多关系
     * @param string $column 对外字段名
     * @param string $model 对方模型名
     * @param string $through 关系表名
     * @param string $far_key 关系表存对方主键的字段名
     * @param string $foreign_key 关系表存本身主键的字段名
     */
    public function addHasManyFromThrough(
        string $column,
        string $model,
        string $through,
        string $far_key,
        string $foreign_key
        ){
        
        return $this;
    }
    /**
     * 一[对方]对一[本身]关系
     * 对方存本身主键,对方一个记录
     * @param string $column
     * @param string $model
     * @param string $primary_key
     * @param string $foreign_key
     */
    public function addHasOne(
        string $column,
        string $model,
        string $foreign_key
        ){
        return $this;
    }
    /**
     * (多或一[对方])对一[本身]关系
     * 本身存对方主键
     */
    public function addBelongsTo(
        string $column,
        string $model,
        string $far_primary_key,
        string $foreign_key
    ){
        return $this;
    }
    public function modelName($column){
        if (isset($this->_belongsto[$column])) {
            $model_name=$this->_belongsto[$column];
        }
        if (isset($this->_has_many[$column])) {
            $model_name=$this->_belongsto[$column];
        }
        if (isset($this->_has_one[$column])) {
            $model_name=$this->_belongsto[$column];
        }
        
        $this->_relationKeyFill($belongs_to [$column],'foreign_key',$model);
        
        
        return isset($model_name)?$model_name:null;
    }
    public function isBelongsTo($column) {
        
    }
    public function isHasOne($column) {
        
    }
    public function getBelongsToForeignKey($column) {
        
    }
    public function isHasMany($column) {
        
    }
    public function isHasManyThrough($column) {
        
    }
    public function getHasManyThrough($column) {
        if (!isset($relation [$key_name])) {
            $relation [$key_name]=strtolower($orm->tableName()."_".$orm->primaryKey());
        }
    }
    public function getHasManyFarKey($column) {
        
    }
    public function getHasManyForeignKey($column) {
        
    }
    public function getBelongsToWhere($column) {
        
    }
    public function getHasOneWhere($column) {
        
    }
    public function getHasManyWhere($column) {
        
    }
    public function getHasOneForeignKey($column) {
        
    }
    public function setBuilderCallback($column,callable $callback){
        
    }
    public function runBuilderCallback($column,Builder $builder) {
        
    }
    
    
    
    
    
    
    public function isFilter($column,$value):bool{
        if(!isset($this->_filter[$column]))$filter=array(NULL,FALSE,[]);
        if(!is_array($this->_filter[$column]))$this->_filter[$column]=[$this->_filter[$column]];
        return in_array($value,$this->_filter[$column]);
    }
}