<?php
namespace Model\Traits;
trait ModelEmailTrait {
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
			(new \LSYS\Entity\Column('user_id'))->setType('int(11)')->setDefault(NULL),
			(new \LSYS\Entity\Column('mail'))->setType('char(12)')->setDefault(NULL),
			(new \LSYS\Entity\Column('my_user_id'))->setType('int(11)')->setDefault(NULL)
        ]);
    }
    public function primaryKey() {
        return 'id';
    }
    public function entityClass():string
    {
        return \Model\EntityEmail::class;
    }
    public function tableName():string
    {
        return "email";
    }
    
    /**
     * 数据库执行构造器
     * @rewrite
     * @return \Model\Traits\BuilderEmail
     */
    public function dbBuilder() {
       return new \Model\Traits\BuilderEmail($this);
    }
        
}
