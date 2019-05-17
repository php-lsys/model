<?php
namespace LSYS\Model;
use LSYS\Entity\Database\Result;
use LSYS\Entity\Exception;
interface Database extends \LSYS\Entity\Database{
    /**
     * 执行请求时自动选择主从
     * @var integer
     */
    const QUERY_AUTO=0;
    /**
     * 执行请求时一次执行主库,之后变为QUERY_AUTO
     * @var integer
     */
    const QUERY_MASTER_ONCE=1;
    /**
     * 执行请求时都选择主库
     * @var integer
     */
    const QUERY_MASTER_ALL=2;
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
}