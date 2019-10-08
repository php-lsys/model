<?php
namespace Model\Traits;
trait ModelUserTrait {
    use \LSYS\Model\Traits\ModelTableColumnsFromFactory;
    /**
     * 重写此方法进行自定义字段填充
     * @rewrite
     * @param \LSYS\Entity\ColumnSet $table_columns
     */
    private function customColumnsFactory(\LSYS\Entity\ColumnSet $table_columns){}
    public function tableColumns(){
        if (!isset(self::$_table_columns_code)){
            self::$_table_columns_code=$this->tableColumnsFactory();
            $_table_columns_code=$this->customColumnsFactory(self::$_table_columns_code);
            if($_table_columns_code instanceof \LSYS\Entity\ColumnSet)self::$_table_columns_code=$_table_columns_code;
        }
        return self::$_table_columns_code;
    }
    private function tableColumnsFactory(){
        return new \LSYS\Entity\ColumnSet([
            (new \LSYS\Entity\Column('id'))->setType('int(11)')->setDefault(NULL),
			(new \LSYS\Entity\Column('name'))->setType('varchar(100)')->setDefault(NULL),
			(new \LSYS\Entity\Column('add_time'))->setType('int(11)')->setDefault(0),
			(new \LSYS\Entity\Column('code'))->setType('varchar(100)')->setDefault(NULL)
        ]);
    }
    public function primaryKey() {
        return 'id';
    }
    public function entityClass()
    {
        return \Model\EntityUser::class;
    }
    public function tableName()
    {
        return "user";
    }
    
    private $_db_builder;
    /**
     * @return \Model\Traits\BuilderUser
     */
    public function dbBuilder() {
       $table_name=$this->tableName();
       if (!isset($this->_db_builder[$table_name])){
           $this->_db_builder[$table_name]=new \Model\Traits\BuilderUser($this);
       }
       return $this->_db_builder[$table_name];
    }
        
}
