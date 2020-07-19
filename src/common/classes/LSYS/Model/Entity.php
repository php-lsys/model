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
    public function loadData(array $data,EntityColumnSet $query_column_set=null,$loaded=true,EntitySet $set=null){
        $this->sets=$set;
        return parent::loadData($data,$query_column_set,$loaded);
    }
    public function table(){
        if ($this->_table==null) $this->_table=(new \ReflectionClass($this->tableClass()))->newInstance();
        return $this->_table;
    }
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
            $val=$table->relatedFind($this,$column);
        }
        if(!is_null($val))return $val;
        return parent::getNotExist($column);
    }
    /**
     * 返回ORM名
     * @return string
     */
    abstract public function tableClass():string;
}

