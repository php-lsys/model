<?php
namespace LSYS\Model\Column;
use LSYS\Entity\Column;
use LSYS\Entity\ColumnSave;
use LSYS\Entity;
/**
 * 时间字段 
 */
class TimeColumn extends Column implements ColumnSave{
    protected $_format=true;
    protected $_is_create;
    protected $_is_update;
    /**
     * 设置为创建时自动设置
     * @return static
     */
    public function setCreate(){
        $this->_is_create=true;
        return $this;
    }
    /**
     * 设置为更新时更新
     * @return static
     */
    public function setUpdate(){
        $this->_is_update=true;
        return $this;
    }
    /**
     * 设置字段格式 同date函数参数
     * @param string||true $format
     * @return static
     */
    public function setFormat($format){
        $this->_format=$format;
        return $this;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Column::compare()
     */
    public function compare($old_val,$new_val){
        if (is_null($old_val)&&is_null($new_val))return true;
        if (is_null($old_val)||is_null($new_val))return false;
        if ($this->_format===true)return intval($old_val)==intval($new_val);
        return strtotime($old_val)==strtotime($new_val);
    }
    public function update(Entity $entity,string $column)
    {
        if ($this->_is_update) {
            if($this->_format===true){
                $entity->__set($column, time());
            }else{
                $entity->__set($column, date($this->_format));
            }
        }
    }
    public function create(Entity $entity,string $column)
    {
        if ($this->_is_create) {
            if($this->_format===true) $entity->__set($column, time());
            else $entity->__set($column, date($this->_format));
        }
    }
    /**
     * 拷贝除名字外的所有属性
     * @param Column $column
     * @return \LSYS\Entity\Column
     */
    public function copy(Column $column) {
        parent::copy($column);
        if($column instanceof TimeColumn){
            $this->_format=$column->_format;
            $this->_is_create=$column->_is_create;
            $this->_is_update=$column->_is_update;
        }
        return $this;
    }
}