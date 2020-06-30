<?php
namespace LSYS\Model\Database\Database;
class Result implements \LSYS\Entity\Database\Result {
    protected $_result;
    public function __construct(\LSYS\Database\Result $result){
        $this->_result=$result;
    }
    public function setFetchFree(){
        $this->_result->setFetchFree();
        return $this;
    }
    public function next()
    {
        return $this->_result->next();
    }

    public function valid()
    {
        return $this->_result->valid();
    }

    public function current()
    {
        return $this->_result->current();
    }

    public function rewind()
    {
        return $this->_result->rewind();
    }

    public function get($name, $default = NULL)
    {
        return $this->_result->get($name,$default);
    }
    public function key()
    {
        return $this->_result->key();
    }
    public function fetchCount($iterator=false):int{
        return $this->_result->count();
    }
}