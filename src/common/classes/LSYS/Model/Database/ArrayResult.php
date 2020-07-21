<?php
namespace LSYS\Model\Database;
class ArrayResult extends \ArrayIterator implements \LSYS\Entity\Database\Result {
    public function setFetchFree(){
        return $this;
    }
    public function get($name, $default = NULL)
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }
        return $default;
    }
    public function fetchCount($iterator=false){
        return $this->count();
    }
}