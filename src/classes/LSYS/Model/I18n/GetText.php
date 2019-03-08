<?php
namespace LSYS\Model\I18n;
use LSYS\Entity\I18n;
use LSYS\I18n\DI;
class GetText implements I18n{
    protected $i18n;
    public function __construct($dir,\LSYS\I18n $i18n=null){
        $this->i18n=$i18n?$i18n:DI::get()->i18n($dir);
    }
    public function __($string, array $values = NULL)
    {
        return $this->i18n->__($string,$values);
    }
}