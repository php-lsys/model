<?php
namespace LSYS\Model\Database;
class ArrayResult implements \LSYS\Entity\Database\Result {
    protected $_data;
    protected $_index=0;
    protected $_total=0;
    public function __construct($data){
        $this->_data=is_array($data)?array_values($data):[];
        $this->_total=count($this->_data);
    }
    public function get($name, $default = NULL)
    {
        $row=$this->current();
        if (is_array($row)&&array_key_exists($name, $row))return $row[$name];
        return $default;
    }
    public function next()
    {
        if ($this->_index>=$this->_total) {
            return false;
        }
        $this->_index++;
        return true;
    }
    public function seek($position)
    {
        $position=intval($position);
        if(array_key_exists($position,$this->_data)){
            $this->_index=$position;
            return true;
        }
        return false;
    }
    public function valid()
    {
        return $this->_index<$this->_total&&$this->_index>=0;
    }
    public function current()
    {
        return isset($this->_data[$this->_index])?$this->_data[$this->_index]:null;
    }
    public function rewind()
    {
        $this->_index=0;
        return true;
    }
    public function key()
    {
        return $this->_index;
    }
    public function count()
    {
        return $this->_total;
    }
}