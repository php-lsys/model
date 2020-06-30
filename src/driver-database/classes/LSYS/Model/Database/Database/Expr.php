<?php
namespace LSYS\Model\Database\Database;
use LSYS\Model\Database\Database;
class Expr implements \LSYS\Model\Database\Expr {
    protected $expr;
    public function __construct(string $value,array $parameters = array()) {
        $this->expr=\LSYS\Database::expr($value,$parameters);
    }
    public function compile(\LSYS\Model\Database $db):string{
        assert($db instanceof Database);
        return $this->expr->compile($db->getDatabase()->getConnect());
    }
}