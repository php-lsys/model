<?php
namespace LSYS\Model\Database;
interface Expr{
    /**
     * SQL表达式构造函数
     * @param string $value
     * @param array $parameters
     */
    public function __construct(string $value,array $parameters = array());
    /**
     * 编译表达式
     * @param \LSYS\Model\Database $db
     */
    public function compile(\LSYS\Model\Database $db):string;
}