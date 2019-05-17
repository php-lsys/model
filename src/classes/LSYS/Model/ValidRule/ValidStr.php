<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
use LSYS\Validation\Valid;
class ValidEmail implements ValidRule{
    const ALPHA=1;
    const ALPHA_NUMERIC=2;
    const ALPHA_DASH=3;
    const DIGIT=4;
    const NUMERIC=5;
    const COLOR=6;
    const NOT_EMPTY=7;
    const IP=8;
    protected $_type;
    protected $_allow_empty;
    /**
     * @param bool $dns 是否检测DNS
     */
    public function __construct($type=false,$allow_empty=true) {
        $this->_type=$type;
        $this->_allow_empty=$allow_empty;
        if(!class_exists(\LSYS\Validation\Valid::class)){
            throw new \Exception("plase install it: composer install lsys/validation");
        }
    }
    /**
     * @return bool
     */
    public function check(Validation $validation,$field,$value,$label,Entity $entity,array $check_data) {
        $param=array(
            ":label"=>$label,
            ":value"=>$value,
            ":field"=>$field,
        );
        switch ($this->_type) {
            case self::ALPHA:
                if(!Valid::alpha($value)){
                    $validation->error($field, __(":label [:field] [:value] not alpha",$param));
                }
            break;
            case self::ALPHA_NUMERIC:
                if(!Valid::alphaNumeric($value)){
                    $validation->error($field, __(":label [:field] [:value] not ALPHA NUMERIC",$param));
                }
            break;
            case self::ALPHA_DASH:
                if(!Valid::alphaDash($value)){
                    $validation->error($field, __(":label [:field] [:value] not ALPHA DASH",$param));
                }
            break;
            case self::COLOR:
                if(!Valid::color($value)){
                    $validation->error($field, __(":label [:field] [:value] not COLOR",$param));
                }
            break;
            case self::DIGIT:
                if(!Valid::digit($value)){
                    $validation->error($field, __(":label [:field] [:value] not DIGIT",$param));
                }
            break;
            case self::NUMERIC:
                if(!Valid::numeric($value)){
                    $validation->error($field, __(":label [:field] [:value] not NUMERIC",$param));
                }
            break;
            case self::NOT_EMPTY:
                if(!Valid::notEmpty($value)){
                    $validation->error($field, __(":label [:field] [:value] not EMPTY",$param));
                }
            break;
            case self::IP:
                if(!Valid::ip($value)){
                    $validation->error($field, __(":label [:field] [:value] not IP",$param));
                }
            break;
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_empty;
    }
}