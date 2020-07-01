<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
use LSYS\Validation\Valid;
/**
 * 依赖 LSYS\Validation\Valid  
 */
class ValidEmail implements ValidRule{
    protected $_dns;
    protected $_strict;
    protected $_allow_empty;
    /**
     * @param bool $dns 是否检测DNS
     */
    public function __construct(bool $dns=false,bool $allow_empty=true,bool $strict=FALSE) {
        $this->_dns=$dns;
        $this->_strict=$strict;
        $this->_allow_empty=$allow_empty;
        if(!class_exists(\LSYS\Validation\Valid::class)){
            throw new \Exception("plase install it: composer install lsys/validation");
        }
    }
    /**
     * @return bool
     */
    public function check(Validation $validation,$field,$email,$label,Entity $entity,array $check_data) {
        $param=array(
            ":label"=>$label,
            ":email"=>$email,
            ":field"=>$field,
        );
        if (mb_strlen($email,\LSYS\Core::charset()) > 254)
        {
            $validation->error($field, __(":label [:field] [:email] to long",$param));
            return ;
        }
        if (!Valid::email($email,$this->_strict)) {
            $validation->error($field, __(":label [:field] [:email] not email address",$param));
            return ;
        }
        if ($this->_dns&&!Valid::emailDomain($email)) {
            $validation->error($field, __(":label [:field] [:email] not find DNS",$param));
            return ;
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_empty;
    }
}