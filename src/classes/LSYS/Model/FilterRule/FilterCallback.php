<?php
namespace LSYS\Model\FilterRule;
use LSYS\Entity;
use LSYS\Entity\FilterRule;
class FilterCallback implements FilterRule{
    protected $_fun;
    protected $_param;
    /**
     * 回调方式实现过滤器
     * @param string $fun 函数名
     * @param array $param 参数格式
     */
    public function __construct($fun,$param=array(":value")){
        $this->_fun=$fun;
        $this->_param=$param;
    }
    public function filter(Entity $entity, $value, $field)
    {
        $_bound = array (
            ':field' => $field,
            ':entity' => $entity,
            ':value' => $value
        );
        $filter = $this->_fun;
        $params = $this->_param;
        foreach ( $params as $key => $param ) {
            if (is_string ( $param ) and array_key_exists ( $param, $_bound )) {
                $params [$key] = $_bound [$param];
            }
        }
        if (is_array ( $filter ) or ! is_string ( $filter )) {
            $value = call_user_func_array ( $filter, $params );
        } elseif (strpos ( $filter, '::' ) === FALSE) {
            $function = new \ReflectionFunction ( $filter );
            $value = $function->invokeArgs ( $params );
        } else {
            list ( $class, $method ) = explode ( '::', $filter, 2 );
            $method = new \ReflectionMethod ( $class, $method );
            $value = $method->invokeArgs ( NULL, $params );
        }
        return $value;
    }
}