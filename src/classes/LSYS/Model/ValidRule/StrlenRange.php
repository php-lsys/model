<?php
namespace LSYS\Entity\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use LSYS\I18n\DI;
class StrlenRange implements ValidRule{
    protected $_min;
    protected $_max;
    protected $_allow_empty;
    /**
     * @param int $min 存在时表示不小于此值
     * @param int $max 存在时表示不大于此值
     * @param bool $allow_empty 是否不能为空
     */
    public function __construct($min,$max,$allow_empty) {
        $this->_min=intval($min);
        $this->_max=intval($max);
        $this->_allow_empty=boolval($allow_empty);
    }
    /**
     * @return bool
     */
    public function check(Validation $validation,$field,$value,$label,Entity $entity,array $check_data) {
        $len=strlen($value);
        $i18n=DI::get();
        $param=array(
          ":label"=>$label,  
          ":min"=>$this->_min,  
          ":max"=>$this->_max,  
          ":field"=>$field,  
        );
        if ($this->_min>0&&$len<$this->_min) {
            $validation->error($field, $i18n->__(":label [:field] strlen can't be < :min",$param));
        }
        if ($this->_max>0&&$len>$this->_max) {
            $validation->error($field, $i18n->__(":label [:field] strlen can't be > :max",$param));
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_empty;
    }
}