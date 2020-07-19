<?php
namespace LSYS\Model;
use function LSYS\Model\__;
use LSYS\Entity;
/**
 * @property \LSYS\Model $_table 
 */
class EntitySet extends \LSYS\Entity\EntitySet{
    private $_free=false;
    private $pre_column=[];
    private $pre_data=[];
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\EntitySet::setFetchFree()
     */
    public function setFetchFree() {
        $this->_free=true;
        return parent::setFetchFree();
    }
    /**
     * 预加载指定关系
     * @param string $column
     * @param string|array|\LSYS\Entity\ColumnSet $columns
     * @return $this
     */
    public function setPreload(... $column) {
        if ($this->_free) throw new Exception(__("set free fetchd can't preload related"));
        $column=array_merge($column,$this->pre_column);
        $this->pre_column=array_unique($column);
        return $this;
    }
    /**
     * 重置需要预加载
     * @return \LSYS\Model\EntitySet
     */
    public function reloadPreload(){
        $this->pre_data=[];
        return $this;
    }
    public function getPreload(Entity $entity,$column) {
        if (!isset($this->pre_column[$column])) {
            return null;
        }
        if(!is_array($this->pre_data[$column])){
            if(is_null($this->_table))$this->_table=(new \ReflectionClass($this->_entity))->newInstance()->table();
            $data=$this->_table->RelatedFinds($this,$this->pre_column[$column]);
            if(is_array($data)){
                $this->pre_data[$column]=$data;
            }
        }
        if (!isset($this->pre_data[$column][$entity->__get($column)])) {
            return null;
        }
        return $this->pre_data[$column][$entity->__get($column)];
    }
}
