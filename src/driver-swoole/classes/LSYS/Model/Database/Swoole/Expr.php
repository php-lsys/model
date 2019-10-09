<?php
namespace LSYS\Model\Database\Swoole;
class Expr implements \LSYS\Model\Database\Expr {
    protected $parameters;
    protected $value;
    public function __construct($value, $parameters = array())
    {
        $this->value = $value;
        $this->parameters = $parameters;
    }
    public function compile(\LSYS\Model\Database $db)
    {
        $value = $this->value;
        if ( ! empty($this->parameters))
        {
            $params = array_map(array($db, 'quoteValue'), $this->parameters);
            $value = strtr($value, $params);
        }
        return $value;
    }
}