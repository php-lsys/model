<?php
namespace LSYS\Model;
use LSYS\Entity\Table;
abstract class Entity extends \LSYS\Entity{
    public function __construct(Table $table=NULL) {
        $this->_table = $table;
    }
    public function table(){
        if ($this->_table==null) $this->_table=(new \ReflectionClass($this->tableClass()))->newInstance();
        return $this->_table;
    }
    /**
     * 返回ORM名
     * @return string
     */
    abstract public function tableClass();
}

