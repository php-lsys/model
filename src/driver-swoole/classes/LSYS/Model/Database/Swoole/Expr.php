<?php
namespace LSYS\Model\Database\Swoole;
class Expr implements \LSYS\Model\Database\Expr {
    protected $parameters;
    protected $value;
    public function __construct(string $value,array $parameters = array())
    {
        $this->value = $value;
        $this->parameters = $parameters;
    }
    public function compile(\LSYS\Model\Database $db):string
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