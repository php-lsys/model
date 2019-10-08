<?php
namespace LSYS\Model\Traits;
use LSYS\Entity\Exception;

trait EntityRelatedMethods{
   public function __call($method,$args) {
       $related=$this->table()->related($this, $method,isset($args[0])?$args[0]:NULL);
       if(is_null($related)){
           throw new Exception(strtr("Call to undefined method :class:::method()",array(":class"=>get_called_class(),":method"=>$method)));
       }
       return $related;
   }
}
