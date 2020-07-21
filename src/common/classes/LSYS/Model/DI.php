<?php
namespace LSYS\Model;
/**
 * @method \LSYS\Model\Database modelDB() 模型使用的默认数据库操作对象,虚方法,必须重写
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