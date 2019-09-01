<?php
namespace LSYS\Model\Traits;
trait EntityRelatedMethods{
   public function __call($method,$args) {
       $related=$this->table()->related($this, $method,isset($args[0])?$args[0]:NULL);
       if(is_null($related))trigger_error(strtr("Call to undefined method :class:::method()",array(":class"=>get_called_class(),":method"=>$method)),E_ERROR);
       return $related;
   }
}
