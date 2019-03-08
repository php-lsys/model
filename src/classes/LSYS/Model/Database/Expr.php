<?php
namespace LSYS\EntityBuilder\Database;
class Expr{
	protected $_value;
	public function __construct($value)
	{
		$this->_value = $value;
	}
	public function value()
	{
		return (string) $this->_value;
	}
}