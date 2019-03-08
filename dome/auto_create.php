<?php
use LSYS\Entity\ColumnSet;
/**
 * @method entity1 find($columns=null)
 * @method \LSYS\Entity\Result|entity1[] findAll($columns=null)
 */
trait orm11 {
    public function tableColumns(){
        if (!$this->_table_columns_cache){
            $column=array();
            $this->_table_columns_cache=new ColumnSet($column);
        }
        return $this->_table_columns_cache;
    }
    public function entityClass()
    {
        return entity1::class;
    }
    public function tableName()
    {
        return "test";
    }
}
/**
 * @property int $id ID
 * @property int $is_post	是否发货
 * @property int $post_time	发货时间
 * @property int $is_post_confirm	是否确认收货
 * @method orm1 table()
 */
trait entity11 {
    public function tableClass()
    {
        return orm1::class;
    }
}