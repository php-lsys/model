<?php
namespace LSYS\Model;
use LSYS\Entity\Database\Result;
use LSYS\Entity\Exception;
interface Database extends \LSYS\Entity\Database{
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
     * @param mixed $value
     * @return string
     */
    public function quoteValue($value,$column_type);
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
	public function query($sql);
	/**
	 * 执行一个COUNT语句并返回结果数量
	 * @param string $sql
	 * @param string $total_column
	 * @throws Exception
	 * @return int
	 */
	public function queryCount($sql,$total_column='total');
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
}