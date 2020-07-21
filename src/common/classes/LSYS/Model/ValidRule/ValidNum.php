<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
class ValidNum implements ValidRule{
    protected $_min;
    protected $_max;
    protected $_allow_empty;
    /**
     * 检验是否在某数字范围内
     * @param int $min 存在时表示不小于此值
     * @param int $max 存在时表示不大于此值
     * @param bool $allow_empty 是否不能为空
     */
    public function __construct(?int $min=null,?int $max=null,bool $allow_empty=true) {
        $this->_min=$min;
        $this->_max=$max;
        $this->_allow_empty=boolval($allow_empty);
    }
    /**
     * @return bool
     */
    public function check(Validation $validation,$field,$value,$label,Entity $entity,array $check_data) {
        $param=array(
          ":label"=>$label,  
          ":min"=>$this->_min,  
          ":max"=>$this->_max,  
          ":field"=>$field,  
        );
        if(!is_numeric($value)){
            $validation->error($field, __(":label [:field] must be number",$param));
        }
        if (!is_null($this->_min)&&$value<$this->_min) {
            $validation->error($field, __(":label [:field] can't be < :min",$param));
        }
        if (!is_null($this->_max)>0&&$value>$this->_max) {
            $validation->error($field, __(":label [:field] can't be > :max",$param));
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_empty;
    }
}