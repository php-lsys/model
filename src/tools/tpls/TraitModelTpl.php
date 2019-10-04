<?php
/*__LSYS_TPL_NAMESPACE__*/
trait __LSYS_TPL_TRAIT_MODEL__ {
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
            /*__LSYS_TPL_COLUMNS__*/
        ]);
    }
    public function primaryKey() {
        return /*__LSYS_TPL_PK__*/;
    }
    public function entityClass()
    {
        return /*__LSYS_TPL_ENTITY_CLASS__*/;
    }
    public function tableName()
    {
        return "__LSYS_TPL_TABLE_NAME__";
    }
    /*__LSYS_TPL_BUILDER_METHOD__*/
}
