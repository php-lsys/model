<?php
namespace LSYS\Model;
class Table extends \LSYS\Model{
    use \LSYS\Model\Traits\ModelTableColumnsFromDB;
    protected $_table_name;
    /**
     * 根据表名创建model
     * @param string $table_name 不带前缀
     */
    public function __construct(string $table_name){
        $this->_table_name=$table_name;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity\Table::tableName()
     */
    public function tableName():string
    {
        return $this->_table_name;
    }
    public function entityClass():string
    {
        return TableEntity::class;
    }
}