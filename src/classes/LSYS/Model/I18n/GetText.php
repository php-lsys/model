<?php
namespace LSYS\EntityBuilder\I18n;
use LSYS\Entity\I18n;
class GetText implements I18n{
    protected $_dir;
    protected $_domain;
    protected $_lang;
    /**
     * gettext多语言实现
     * @param string $dir
     * @param string $domain
     */
    public function __construct($dir,$domain="default",$lang="zh_CN"){
        $this->_dir=$dir;
        $this->_domain=$domain;
        $this->_lang=$lang;
    }
    public function __($string, array $values = NULL)
    {
        setlocale(LC_ALL, $this->_lang.".UTF-8");
        bind_textdomain_codeset($this->_domain, 'UTF-8' );
        bindtextdomain($this->_domain, $this->_dir);
        $string=dgettext($this->_domain,$string);
        if(is_array($values)){
            foreach ($values as $k=>$v){
                $values[$k]=(string)$v;
            }
            $string=strtr($string, $values);
        }
        return $string;
    }
}