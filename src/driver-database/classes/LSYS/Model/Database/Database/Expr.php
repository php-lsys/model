<?php
namespace LSYS\Model\Database\Database;
class Expr implements \LSYS\Model\Database\Expr {
    protected $expr;
    public function __construct(...$args) {
        $this->expr=new \LSYS\Database\Expr(...$args);
    }
    public function compile(\LSYS\Model\Database $db){
        return $this->expr->compile();
    }
}