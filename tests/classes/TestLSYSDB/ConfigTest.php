<?php
namespace TestLSYSDB;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Database;
class ConfigTest extends TestCase
{
    /**
     * @var Database
     */
    protected $_db;
    public function setUp(){
        $this->_db=DI::get()->db("database.mysqli");
    }
    public function testDbConfig()
    {
        $config = new \LSYS\Config\Database("aaa");
        $this->assertTrue($config instanceof \LSYS\Config);
        $this->assertTrue($config->set("bbb","value"));
        $this->assertEquals($config->get("bbb"), "value");
        $this->assertTrue(is_array($config->asArray()));
        $this->assertTrue($config->exist("bbb"));
        $this->assertTrue($config->name()=="aaa");
        $this->assertTrue($config->loaded());
        $this->assertFalse($config->readonly());
    }
}