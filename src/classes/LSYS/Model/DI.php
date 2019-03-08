<?php
namespace LSYS\Model;
/**
 * @method \LSYS\Model\Database modelDB()
 * @method \LSYS\Entity\I18n modelI18n()
 */
class DI extends \LSYS\DI{
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->modelDB)&&$di->modelDB(new \LSYS\DI\VirtualCallback());
        !isset($di->modelI18n)&&$di->modelI18n(new \LSYS\DI\VirtualCallback());
        return $di;
    }
}