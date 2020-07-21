<?php
namespace LSYS\Model;
class Column extends \LSYS\Entity\Column{
    /**
     * @var string
     */
    protected $_sql;
    /**
     * 字段
     * @param string $name
     * @param string $sql
     */
    public function __construct($name,$sql=null){
        if (is_null($sql)) {
            $this->_sql=$name;
        }else $this->_sql=$sql;
        parent::__construct($name);
    }
    /**
     * 获取字段名对应SQL
     * @return string
     */
    public function sql(){
        return $this->_sql;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Column::asArray()
     */
    public function asArray(){
        $arr=parent::asArray();
        $arr['sql']=$this->_sql;
        return $arr;
    }
}
