<?php
namespace LSYS\Model\ValidRule;
use LSYS\Entity\ValidRule;
use LSYS\Entity;
use LSYS\Entity\Validation;
use function LSYS\Model\__ as __;
class ValidEmail implements ValidRule{
    protected $_dns;
    protected $_strict;
    protected $_allow_empty;
    /**
     * @param bool $dns 是否检测DNS
     */
    public function __construct($dns=false,$allow_empty=true,$strict=FALSE) {
        $this->_dns=$dns;
        $this->_strict=$strict;
        $this->_allow_empty=$allow_empty;
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
        if (mb_strlen($email,\LSYS\Core::$charset) > 254)
        {
            $validation->error($field, __(":label [:field] [:email] to long",$param));
            return ;
        }
        if ($this->_strict === TRUE)
        {
            $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
            $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
            $atom  = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
            $pair  = '\\x5c[\\x00-\\x7f]';
            
            $domain_literal = "\\x5b($dtext|$pair)*\\x5d";
            $quoted_string  = "\\x22($qtext|$pair)*\\x22";
            $sub_domain     = "($atom|$domain_literal)";
            $word           = "($atom|$quoted_string)";
            $domain         = "$sub_domain(\\x2e$sub_domain)*";
            $local_part     = "$word(\\x2e$word)*";
            
            $expression     = "/^$local_part\\x40$domain$/D";
        }
        else
        {
            $expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})$/iD';
        }
        if (!preg_match($expression, (string) $email)) {
            $validation->error($field, __(":label [:field] [:email] not email address",$param));
            return ;
        }
        if ($this->_dns&&!checkdnsrr(preg_replace('/^[^@]++@/', '', $email), 'MX')) {
            $validation->error($field, __(":label [:field] [:email] not find DNS",$param));
            return ;
        }
    }
    public function allowEmpty()
    {
        return $this->_allow_empty;
    }
}