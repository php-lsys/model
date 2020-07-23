<?php
namespace LSYS\Model;
use LSYS\Model\Database\Builder;
class Related{
    protected $_has_many=[];
    protected $_has_one=[];
    protected $_belongs_to=[];
    protected $_filter=[];
    protected $_callback=[];
    /**
     * 一[本身]对多[对方]关系
     * 对方(或第三方)存本身主键,但对方(或第三方)有多条记录
     * @param string $column 对外字段名
     * @param string $model 对方模型
     * @param string $foreign_key 对方存本身主键的字段  或 联合主键:[本身主键名=>对方存本身主键的字段]
     * @return $this
     */
    public function addHasMany(
        string $column,
        string $model,
        $foreign_key
        ){
        $this->_has_many[$column]=array(
            'model'=>$model,
            'foreign_key'=>$foreign_key,
        );
        return $this;
    }
    /**
     * 一[本身]对多[对方]关系
     * 通过第三方关系表存放的一对多关系
     * @param string $column 对外字段名
     * @param string $model 对方模型名
     * @param string|array $through 关系表名 或 [关系表名,别名]
     * @param string|array $far_key 关系表存对方主键的字段名 或 联合主键:[关系表存对方主键的字段名=>关系表存对方主键的字段名]
     * @param string|array $foreign_key 关系表存本身主键的字段名 或 联合主键:[当前表主键字段名=>关系表存本身主键的字段名]
     * @return $this
     */
    public function addThroughHasMany(
        string $column,
        string $model,
        $through,
        $far_key,
        $foreign_key
        ){
        $this->_has_many[$column]=array(
            'model'=>$model,
            'through'=>$through,
            'far_key'=>$far_key,
            'foreign_key'=>$foreign_key,
        );
        return $this;
    }
    /**
     * 一[对方]对一[本身]关系
     * 对方存本身主键,对方一个记录
     * @param string $column 关联字段
     * @param string $model 对方模型名
     * @param string $foreign_key 对方存本身主键字段名 或 联合主键:['本身主键字段'=>'对方存本身主键字段名']
     * @return $this
     */
    public function addHasOne(
        string $column,
        string $model,
        $foreign_key
        ){
        $this->_has_one[$column]=array(
            'model'=>$model,
            'foreign_key'=>$foreign_key,
        );
        return $this;
    }
    /**
     * (多或一[对方])对一[本身]关系
     * 本身存对方主键
     * @param string $column 关联字段
     * @param string $model 对方模型名
     * @param string $foreign_key 本身存对方主键键名 或 联合主键:['对方主键名'=>'本身存对方主键键名']
     * @return $this
     */
    public function addBelongsTo(
        string $column,
        string $model,
        $foreign_key
    ){
        $this->_belongs_to[$column]=array(
            'model'=>$model,
            'foreign_key'=>$foreign_key,
        );
        return $this;
    }
    /**
     * 得到指定配置关系的model名
     * @param string $column
     * @return string
     */
    public function modelName(string $column){
        if (isset($this->_belongs_to[$column])) {
            $model_name=$this->_belongs_to[$column];
        }
        if (isset($this->_has_many[$column])) {
            $model_name=$this->_has_many[$column];
        }
        if (isset($this->_has_one[$column])) {
            $model_name=$this->_has_one[$column];
        }
        return isset($model_name)?$model_name['model']??NULL:null;
    }
    /**
     * 是否是BelongsTo关系
     * @param string $column
     * @return boolean
     */
    public function isBelongsTo(string $column) {
        return array_key_exists($column, $this->_belongs_to);
    }
    /**
     * 是否是HasOne关系
     * @param string $column
     * @return boolean
     */
    public function isHasOne(string $column) {
        return array_key_exists($column, $this->_has_one);
    }
    /**
     * 是否是HasMany关系
     * @param string $column
     * @return boolean
     */
    public function isHasMany(string $column) {
        return array_key_exists($column, $this->_has_many);
    }
    /**
     * 是否是HasMany Through关系
     * @param string $column
     * @return boolean
     */
    public function isHasManyThrough(string $column) {
        return isset($this->_has_many[$column])&&isset($this->_has_many[$column]['through']);
    }
    /**
     * 得到关联关系表名
     * @param string $column
     * @return string|array
     */
    public function getHasManyThrough(string $column) {
        return $this->_has_many[$column]['through']??'';
    }
    /**
     * 本身存对方主键字段名
     * @param string $column
     * @return string|array
     */
    public function getBelongsToForeignKey(string $column) {
        if (!isset($this->_belongs_to[$column]))return null;
        if (!isset($this->_belongs_to[$column]['foreign_key'])) {
            $this->_belongs_to[$column]['foreign_key']=strtolower($this->_belongs_to[$column]['model'])."_id";
        }
        return $this->_belongs_to[$column]['foreign_key'];
    }
    /**
     * 中间表存本身字段名
     * @param string $column
     * @return string|array
     */
    public function getHasManyFarKey(string $column) {
        if (!isset($this->_has_many[$column]))return null;
        if (!isset($this->_has_many[$column]['far_key'])) {
            $this->_has_many[$column]['far_key']=strtolower($this->_belongs_to[$column]['through'])."_id";
        }
        return $this->_has_many[$column]['far_key'];
    }
    /**
     * 对方存本身主键字段 或中间表存本身主键字段
     * @param string $column
     * @return string|array
     */
    public function getHasManyForeignKey(string $column) {
        if (!isset($this->_has_many[$column]))return null;
        if (!isset($this->_has_many[$column]['foreign_key'])) {
            $this->_has_many[$column]['foreign_key']=strtolower($this->_belongs_to[$column]['model'])."_id";
        }
        return $this->_has_many[$column]['foreign_key'];
    }
    /**
     * 对方键名
     * @param string $column
     * @return string|array
     */
    public function getHasOneForeignKey(string $column) {
        if (!isset($this->_has_one[$column]))return null;
        if (!isset($this->_has_one[$column]['foreign_key'])) {
            $this->_has_one[$column]['foreign_key']=strtolower($this->_has_one[$column]['model'])."_id";
        }
        return $this->_has_one[$column]['foreign_key'];
    }
    /**
     * 设置查询构造器回调
     * @param string $column
     * @param callable $callback(Builder $builder,callable $parent)
     * @return $this
     */
    public function setBuilderCallback(string $column,callable $callback=null){
        if (!is_callable($callback)){
            $this->_callback[$column]=[];
            return $this;
        }
        $this->_callback[$column][]=$callback;
        return $this;
    }
    /**
     * 运行查询构造器回调
     * @param string $column
     * @param Builder $builder
     * @return $this
     */
    public function runBuilderCallback(string $column,Builder $builder) {
        if (!isset($this->_callback[$column])||!is_array($this->_callback[$column])){
            return $this;
        }
        $pipe=array_reduce(array_reverse($this->_callback[$column]),function($carry,$callback){
            return function ($passable) use ($carry,$callback) {
                if (is_callable($callback)) {
                    return $callback($passable, $carry);
                }
            };
        },function(Builder $builder){});
        $pipe($builder);
        return $this;
    }
    /**
     * 是否是过滤值
     * 返回真表示此值需要过滤掉,不进行关系查询
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function isFilter(string $column,$value):bool{
        if(!isset($this->_filter[$column]))$this->_filter[$column]=array(NULL,FALSE,[]);
        if(!is_array($this->_filter[$column]))$this->_filter[$column]=[$this->_filter[$column]];
        return in_array($value,$this->_filter[$column]);
    }
    /**
     * 返回已注册的关系字段
     * @return string[]
     */
    public function getColumns():array {
        return array_merge(array_keys($this->_belongs_to),array_keys($this->_has_one),array_keys($this->_has_many));
    }
}