<?php
namespace LSYS\Model;
use function LSYS\Model\__;
/**
 * @property \LSYS\Model $_table 
 */
class EntitySet extends \LSYS\Entity\EntitySet{
    private $_free=false;
    private $pre_column=[];
    private $pre_data=[];
    private $pre_count_data=[];
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\EntitySet::setFetchFree()
     */
    public function setFetchFree() {
        $this->_free=true;
        return parent::setFetchFree();
    }
    /**
     * 设置预加载字段
     * @param string $column
     * @param string|array|\LSYS\Entity\ColumnSet $columns
     * @return $this
     */
    public function setPreload(... $column) {
        if ($this->_free) throw new Exception(__("set free fetchd can't preload related"));
        $this->pre_column=array_unique($column);
        return $this;
    }
    /**
     * 重置需要预加载
     * @return \LSYS\Model\EntitySet
     */
    public function reloadPreload(){
        $this->pre_data=[];
        $this->pre_count_data=[];
        return $this;
    }
    /**
     * @param Entity $entity
     * @param string $column
     * @return int|NULL
     */
    public function getHasManyPreloadCount(Entity $entity,string $column) {
        if (!in_array($column, $this->pre_column)) {
            return null;
        }
        if(is_null($this->_table)){
            $this->_table=(new \ReflectionClass($this->_entity))->newInstance()->table();
        }
        $related=$this->_table->related();
        if(!isset($this->pre_count_data[$column])){
            if (!$related->isHasMany($column))return null;
            $keep_key=$this->key();
            $out=$this->_table->hasManysCount($this, $column);
            $this->rewind();
            while (true) {
                if(!$this->valid()||$this->key()==$keep_key)break;
                $this->next();
            }
            $this->pre_count_data[$column]=is_array($out)?$out:[];
        }
        return $this->dataPkFind($entity,$this->pre_count_data[$column]??[]);
    }
    /**
     * 得到预加载数据
     * @param Entity $entity
     * @param string $column
     * @return NULL|Entity|EntitySet
     */
    public function getPreload(Entity $entity,string $column) {
        if (!in_array($column, $this->pre_column)) {
            return null;
        }
        if(is_null($this->_table)){
            $this->_table=(new \ReflectionClass($this->_entity))->newInstance()->table();
        }
        $related=$this->_table->related();
        if(!isset($this->pre_data[$column])){
            $keep_key=$this->key();
            if ($related->isBelongsTo($column)){
                $out=$this->_table->belongsTos($this, $column);
            }
            if ($related->isHasOne($column)){
                $out=$this->_table->hasOnes($this, $column);
            }
            if ($related->isHasMany($column)){
                $out=$this->_table->hasManys($this, $column);
            }
            if (!isset($out))return null;
            $this->rewind();
            while (true) {
                if(!$this->valid()||$this->key()==$keep_key)break;
                $this->next();
            }
            $this->pre_data[$column]=is_array($out)?$out:[];
        }
        return $this->dataPkFind($entity, $this->pre_data[$column]??[]);
    }
    /**
     * 通过主键查询缓存数组的值
     * @param Entity $entity
     * @param array $data
     * @return mixed
     */
    private function dataPkFind(Entity $entity,array $data) {
        $keys=$this->_table->primaryKey();
        $pk=$entity->pk();
        if(is_array($keys)){
            $pk=array_map('strval', $pk);
            $pk=serialize($pk);
        }
        return $data[$pk]??null;
    }
    public function current()
    {
        $row=$this->_result->current();
        if (is_null($row))return null;
        /**
         * @var Entity $entity
         */
        $entity=(new \ReflectionClass($this->_entity))->newInstance($this->_table);
        if (is_null($this->_table)) {
            $this->_table=$entity->table();
        }
        if ($entity instanceof Entity) {
            return $entity->loadData($row,$this->_columns,true,$this);
        }
        return $entity->loadData($row,$this->_columns,true);
    }
}
