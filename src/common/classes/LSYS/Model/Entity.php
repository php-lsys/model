<?php
namespace LSYS\Model;
use LSYS\Entity\Table;
use LSYS\Entity\EntityColumnSet;
abstract class Entity extends \LSYS\Entity{
    /**
     * @var EntitySet
     */
    private $sets;
    public function __construct(Table $table=NULL) {
        $this->_table = $table;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity::loadData()
     */
    public function loadData(array $data,EntityColumnSet $query_column_set=null,$loaded=true,EntitySet $set=null){
        $this->sets=$set;
        return parent::loadData($data,$query_column_set,$loaded);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity::table()
     */
    public function table(){
        if ($this->_table==null) $this->_table=(new \ReflectionClass($this->tableClass()))->newInstance();
        return $this->_table;
    }
    /**
     * 一对多关系的关系数量
     * 已配置关系返回数量,否则返回NULL
     * @param string $column
     * @return ?int
     */
    public function hasManyCount($column) {
        $val=null;
        if ($this->sets instanceof EntitySet) {
            $val=$this->sets->getHasManyPreloadCount($this,$column);
        }
        if(is_null($val)){
            /**
             * @var \LSYS\Model $table
             */
            $table=$this->table();
            $related=$table->related();
            if ($related->isHasMany($column)){
                $val=$table->hasManyCount($this, $column);
            }
        }
        if(!is_null($val))return $val;
        return parent::getNotExist($column);
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity::getNotExist()
     */
    protected function getNotExist($column) {
        $val=null;
        if ($this->sets instanceof EntitySet) {
            $val=$this->sets->getPreload($this,$column);
        }
        if(is_null($val)){
            /**
             * @var \LSYS\Model $table
             */
            $table=$this->table();
            $related=$table->related();
            if ($related->isBelongsTo($column)){
                $val=$table->belongsTo($this, $column);
            }
            if ($related->isHasOne($column)){
                $val=$table->hasOne($this, $column);
            }
            if ($related->isHasMany($column)){
                $val=$table->hasMany($this, $column);
            }
        }
        if(!is_null($val))return $val;
        return parent::getNotExist($column);
    }
    /**
     * 返回model名
     * @return string
     */
    abstract public function tableClass():string;
}

