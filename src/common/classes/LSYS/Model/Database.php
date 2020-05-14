<?php
namespace LSYS\Model;
use LSYS\Entity\Database\Result;
use LSYS\Entity\Exception;
use LSYS\Model\Database\Expr;
interface Database extends \LSYS\Entity\Database{
    /**
     * 默认查询方式,一般都是在主库进行查询
     * 你可以使用下面的2中查询方式强制在从库上进行查询 
     * @var integer
     */
    const QUERY_AUTO=0;
    /**
     * 执行强制选择一次请求从库之后自动转换为查询主库,之后变为QUERY_AUTO,注意:主从延时可能导致查询无结果
     * @var integer
     */
    const QUERY_SLAVE_ONCE=1;
    /**
     * 执行请求时都强制选择从库进行查询,注意:主从延时可能导致查询无结果,一般用在明确的从库查询情况下使用
     * @var integer
     */
    const QUERY_SLAVE_ALL=2;
    /**
     * 包裹表名 表名 或 array("表名","别名")
     * @param string|array $table 
     * @return string
     */
    public function quoteTable($table);
    /**
     * 包裹字段 字段名 或 array("字段名","别名")
     * @param string|array $column
     * @return string
     */
    public function quoteColumn($column);
    /**
     * 包裹值
     * $column_type为NULL时候根据$value自动推断
     * @param mixed $value
     * @param mixed $column_type
     * @return string
     */
    public function quoteValue($value,$column_type=null);
    /**
     * 返回指定表字段集合
     * @param string $table
     * @return \LSYS\Model\Database\ColumnSet
     */
    public function listColumns($table);
	/**
	 * 执行请求
	 * @param string
	 * @throws Exception
	 * @return Result
	 */
	public function query($sql,array $data=array());
	/**
	 * 执行一个COUNT语句并返回结果数量
	 * @param string $sql
	 * @param string $total_column
	 * @throws Exception
	 * @return int
	 */
	public function queryCount($sql,array $data=array(),$total_column='total');
	/**
	 * 最后执行SQL
	 * @return string
	 */
	public function lastQuery();
	/**
	 * 返回影响行数
	 * @return int
	 */
	public function affectedRows();
	/**
	 * 事务开始
	 */
	public function beginTransaction();
	/**
	 * 是否进行事务中
	 */
	public function inTransaction();
	/**
	 * 事务回滚
	 */
	public function rollback();
	/**
	 * 事务确认
	 */
	public function commit();
	/**
	 * 释放当前对象占用的资源
	 * 除非你明确当前对象不在使用,否则请不要调用此方法
	 * 调用此方法后当前对象一般将不在可用
	 */
	public function release();
	/**
	 * 设置请求时,发送到那个类型数据库
	 * @param int $mode 可用为QUERY_*常量值
	 * @return $this
	 */
	public function queryMode($mode);
	/**
	 * 得到expr对象
	 * @param mixed ...$args
	 * @return Expr
	 */
	public function expr($value,array $param);
	/**
	 * {@inheritDoc}
	 * @see \LSYS\Entity\Database::SQLBuilder()
	 * @return \LSYS\Model\Database\Builder
	 */
	public function SQLBuilder(Table $table);
}