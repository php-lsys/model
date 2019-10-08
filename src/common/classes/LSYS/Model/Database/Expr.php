<?php
namespace LSYS\Model\Database;
interface Expr{
    public function __construct($value, $parameters = array());
    public function compile(\LSYS\Model\Database $db);
}