<?php
namespace LSYS\Model\Traits;
trait ModelEntityClassFormParse{
    public function entityClass(){
        static $entity_name;
        if(!$entity_name){
            $cls=get_called_class ();
            $pos=strrpos($cls,"\\");
            $entity_name = substr($cls,0,$pos===false?0:$pos+1)."Entity".$entity_name;
        }
        return $entity_name;
    }
}