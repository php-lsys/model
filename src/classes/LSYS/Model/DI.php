<?php
namespace LSYS\EntityBuilder;
/**
 * @method \LSYS\EntityBuilder\Database LSYSORMDB()
 * @method \LSYS\Entity\I18n LSYSORMI18n()
 */
class DI extends \LSYS\DI{
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->LSYSORMDB)&&$di->LSYSORMDB(new \LSYS\DI\VirtualCallback());
        !isset($di->LSYSORMI18n)&&$di->LSYSORMI18n(new \LSYS\DI\VirtualCallback());
        return $di;
    }
}