<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
use LSYS\Validation\Valid;
use LSYS\Model\Exception;
class ValidStr implements ValidRule{
    const ALPHA=1;//字符串为字母
    const ALPHA_NUMERIC=2;//字符串为字母加数字
    const ALPHA_DASH=3;//字符串为字母下划线
    const DIGIT=4;//字符串为整数
    const NUMERIC=5;//字符串为纯数字
    const COLOR=6;//字符串为颜色值
    const NOT_EMPTY=7;//字符串不能为空
    const IP=8;//字符串是IP地址
    protected $_type;
    protected $_allow_empty;
    /**
     * 校验字符串指定类型
     * @param int $type 参见常量 
     */
    public function __construct(int $type,bool $allow_empty=true) {
        $this->_type=$type;
        $this->_allow_empty=$allow_empty;
        if(!class_exists(\LSYS\Validation\Valid::class)){
            throw new Exception("plase install it: composer install lsys/validation");
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