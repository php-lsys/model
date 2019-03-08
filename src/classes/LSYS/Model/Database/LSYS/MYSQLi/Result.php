<?php
namespace LSYS\Model\Database\LSYS\MYSQLi;
class Result implements \LSYS\Entity\Database\Result {
    protected $_result;
    public function __construct(\LSYS\Database\MYSQLi\Result $result){
        $this->_result=$result;
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

    public function count()
    {
        return $this->_result->count();
    }

    public function seek(int $position)
    {
        return $this->_result->seek($position);
    }
    public function key()
    {
        return $this->_result->key();
    }
}