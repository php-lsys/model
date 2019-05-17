<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
class ValidEquals implements ValidRule{
    protected $_value;
    protected $_in_array;
    /**
     * 等于某值
     * $in_array 为true时,$value为数组,表示是其中一个即可
     * @param string $value
     * @param bool $in_array
     */
    public function __construct($value,$in_array=true) {
        $this->_value=$value;
        $this->_in_array=$in_array;
    }
    /**
     * @return bool
     */
    public function check(Validation $validation,$field,$value,$label,Entity $entity,array $check_data) {
        $values=is_array($this->_value)?$this->_value:[$this->_value];
        $param=array(
          ":label"=>$label,  
          ":field"=>$field,  
          ":value"=>is_array($values)?implode(",", $values):$values,  
        );
        if($this->_in_array){
            if(!in_array($value, $values)){
                $validation->error($field, __(":label [:field] not in [:value]",$param));
            }
        }else{
            if($value!=$this->_value){
                $validation->error($field, __(":label [:field] not equals [:value]",$param));
            }
        }
    }
    public function allowEmpty()
    {
        return false;
    }
}