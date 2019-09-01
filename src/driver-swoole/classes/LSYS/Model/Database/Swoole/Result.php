<?php
namespace LSYS\Model\Database\Swoole;
class Result implements \LSYS\Entity\Database\Result {
    protected $_data;
    protected $_res;
    protected $_index=0;
    protected $_total=0;
    protected $_free=0;
    protected $_cur;
    public function __construct($res){
        $this->_res=$res;
        $this->fetch();
    }
    protected function fetch(){
        $this->_cur=$this->_res->fetch();
        if(!is_array($this->_cur))return false;
        if (!$this->_free){
            $this->_data[]=$this->_cur;
        }
        $this->_total++;
        return true;
    }
    public function setFetchFree(){
        $this->_free=1;
        return $this;
    }
    public function get($name, $default = NULL)
    {
        $row=$this->current();
        if (is_array($row)&&array_key_exists($name, $row))return $row[$name];
        return $default;
    }
    public function next()
    {
        $index=$this->_index+1;
        if (isset($this->_data[$index])){
            $this->_cur=$this->_data[$index];
            $this->_index=$index;
        }else if($this->fetch()){
            $this->_index=$index;
            return true;
        }
        return false;
    }
    public function valid()
    {
        return is_array($this->_cur);
    }
    public function current()
    {
        return $this->_cur;
    }
    public function rewind()
    {
        $this->_index=0;
        if(!$this->_free&&isset($this->_data[0])){
            $this->_cur=$this->_data[0];
        }
    }
    public function key()
    {
        return $this->_index;
    }
    public function fetchCount($iterator=false){
        if($iterator){
            $arr=$this->_res->fetchall();
            $this->_total+=is_array($arr)?count($arr):0;
        }
        return $this->_total;
    }
}