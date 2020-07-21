<?php
namespace LSYS\Model;
class TableEntity extends Entity{
    private $_table_name;
    /**
     * 根据表名创建实体
     * @param string $table 不带前缀
     */
    public function __construct($table) {
        if ($table instanceof \LSYS\Entity\Table) {
            $this->_table_name=$table->tableName();
            return parent::__construct($table);
        }
        $this->_table_name=$table;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Model\Entity::tableClass()
     */
    public function tableClass(): string
    {
        return Table::class;
    }
    /**
     * {@inheritDoc}
     * @see \LSYS\Entity::table()
     */
    public function table(){
        if ($this->_table==null) $this->_table=new Table($this->_table_name);
        return $this->_table;
    }
}

