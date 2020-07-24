<?php
namespace LSYS\Model\Database\Swoole\EventManager;
use LSYS\EventManager\Event;
class DBEvent extends Event{
    const SQL_START="db.sql.start";
    const SQL_OK="db.sql.ok";
    const SQL_BAD="db.sql.bad";
    const SQL_END="db.sql.end";
    const TRANSACTION_BEGIN="db.transaction.begin";
    const TRANSACTION_COMMIT="db.transaction.commit";
    const TRANSACTION_ROLLBACK="db.transaction.rollback";
    const TRANSACTION_FAIL="db.transaction.fail";
    public static function sqlStart($sql,bool $exec) {
        return new self(self::SQL_START,compact(func_get_argsname()));
    }
    public static function sqlOk($sql,bool $exec) {
        return new self(self::SQL_OK,compact(func_get_argsname()));
    }
    public static function sqlBad($sql,bool $exec) {
        return new self(self::SQL_BAD,compact(func_get_argsname()));
    }
    public static function sqlEnd($sql,bool $exec) {
        return new self(self::SQL_END,compact(func_get_argsname()));
    }
    public static function transactionBegin() {
        return new self(self::TRANSACTION_BEGIN,compact(func_get_argsname()));
    }
    public static function transactionCommit() {
        return new self(self::TRANSACTION_COMMIT,compact(func_get_argsname()));
    }
    public static function transactionRollback() {
        return new self(self::TRANSACTION_ROLLBACK,compact(func_get_argsname()));
    }
    public static function transactionFail() {
        return new self(self::TRANSACTION_FAIL,compact(func_get_argsname()));
    }
}