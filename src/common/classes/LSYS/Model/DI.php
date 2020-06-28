<?php
namespace LSYS\Model;
/**
 * @method \LSYS\Model\Database modelDB()
 */
class DI extends \LSYS\DI{
    /**
     * @return static
     */
    public static function get(){
        $di=parent::get();
        !isset($di->modelDB)&&$di->modelDB(new \LSYS\DI\VirtualCallback());
        return $di;
    }
}