<?php
namespace LSYS\Model\Database;
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