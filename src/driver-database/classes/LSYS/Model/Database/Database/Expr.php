<?php
namespace LSYS\Model\Database\Database;
class Expr implements \LSYS\Model\Database\Expr {
    protected $expr;
    public function __construct(string $value,array $parameters = array()) {
        $this->expr=new \LSYS\Database\Expr(...func_get_args());
    }
    public function compile(\LSYS\Model\Database $db):string{
        return $this->expr->compile();
    }
}