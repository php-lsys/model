<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
class VaildCallback implements ValidRule{
    protected $_fun;
    protected $_param;
    protected $_allow_null;
    public function __construct($fun,$allow_null,$param=array(":value")) {
        $this->_fun=$fun;
        $this->_param=$param;
        $this->_allow_null=$allow_null;
    }
    /**
     * 错误提示 $fun $string 同时存在时设置指定函数错误提示
     * @param string $fun 为空返回内置错误提示
     * @param string $string 为空返回指定函数错误提示
     * @return string[]|NULL|string
     */
    public static function i18n($fun=null,$string=null){
        static $i18n;
        if(!$i18n){
            $i18n=array(
                'ctype_alnum'         => 'The :label does not consist of all letters or digits.',
                'ctype_alpha'         => 'The :label does not consist of all letters.',
                'ctype_digit'         => 'The :label does not consist of all digit.',
                'ctype_lower'         => 'The :label does not consist of all letters lower.',
                'ctype_upper'         => 'The :label does not consist of all letters upper.',
                'preg_match'          => 'The :label does not match rules.',
            );
        }
        if ($fun===null) {
            return $i18n;
        }
        if($string===null){
            return isset($i18n[$fun])?$i18n[$fun]:null;
        }
        $i18n[$fun]=$string;
    }
    public function check(Validation $validation,$field,$value,$label,Entity $entity,array $check_data) {
        // Rules are defined as array($rule, $params)
        $rule=$this->_fun;
        $params=$this->_param;
        $bound=array(
          ":validation"=>$validation,  
          ":field"=>$field,  
          ":value"=>$value,  
          ":label"=>$label,  
          ":entity"=>$entity,  
          ":data"=>$check_data,  
        );
        foreach ($params as $key => $param)
        {
            if (is_string($param) AND array_key_exists($param, $bound))
            {
                // Replace with bound value
                $params[$key] = $bound[$param];
            }
        }
        // Default the error name to be the rule (except array and lambda rules)
        if (is_array($rule))
        {
            // Allows rule('field', array(':model', 'some_rule'));
            if (is_string($rule[0]) AND array_key_exists($rule[0], $bound))
            {
                // Replace with bound value
                $rule[0] = $bound[$rule[0]];
            }
            // This is an array callback, the method name is the error name
            $passed = call_user_func_array($rule, $params);
        }
        elseif ( ! is_string($rule))
        {
            // This is a lambda function, there is no error name (errors must be added manually)
            $passed = call_user_func_array($rule, $params);
        }
        elseif (strpos($rule, '::') === FALSE)
        {
            // Use a function call
            $function = new \ReflectionFunction($rule);
            // Call $function($this[$field], $param, ...) with Reflection
            $passed = $function->invokeArgs($params);
        }
        else
        {
            // Split the class and method of the rule
            list($class, $method) = explode('::', $rule, 2);
            // Use a static method call
            $method = new \ReflectionMethod($class, $method);
            // Call $Class::$method($this[$field], $param, ...) with Reflection
            $passed = $method->invokeArgs(NULL, $params);
        }
        if ($passed === FALSE)
        {
            $msg=static::i18n($this->_fun);
            $args=[];
            foreach ($bound as $key=>$value) {
                if(is_scalar($value))$args[$key]=$value;
            }
            $validation->error($field,strtr($msg?$msg:":label [:field] valid fail",$args));
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_null;
    }
}