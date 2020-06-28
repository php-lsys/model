<?php
namespace TestLSYSDB;
use PHPUnit\Framework\TestCase;
use LSYS\Database\DI;
use LSYS\Database;
use LSYS\Database\AsyncQuery;
use LSYS\Database\Result;
use LSYS\EventManager\EventCallback;
use LSYS\Database\EventManager\DBEvent;
use LSYS\Database\EventManager\ProfilerObserver;
class MYSQLITest extends TestCase
{
    public function testinit(){
        $this->runInit("database.pdo_mysql");
        $this->runInit("database.mysqli");
    }
    protected function runInit($config){
        $db =DI::get()->db($config);
        $this->assertTrue($db instanceof Database);
        $db = Database::factory(\LSYS\Config\DI::get()->config($config));
        $this->assertTrue($db instanceof Database);
        $this->assertFalse($db->getConnectManager()->isConnected());
        $db->getConnectManager()->getConnect();
        $this->assertTrue($db->getConnectManager()->isConnected());
    }
    public function testQuote() {
        $this->runQuote(DI::get()->db("database.mysqli"));
        $this->runQuote(DI::get()->db("database.pdo_mysql"));
    }
    protected function runQuote(Database $db){
        $this->assertEquals($db ->quote(NULL),"NULL");
        $this->assertEquals($db ->quote("aaa"),"'aaa'");
        $this->assertEquals($db ->quote(true),"'1'");
        $this->assertEquals($db ->quote(false),"'0'");
        $this->assertEquals($db ->quote(Database::expr("aaa")),"aaa");
        $this->assertEquals(str_replace(" ", '', $db ->quote([1,2,3])),"(1,2,3)");
        $this->assertEquals($db ->quote(1),"1");
        $this->assertEquals($db ->quote(1.2),"1.200000");
        $this->assertEquals($db ->quoteTable(["a","b"]),"`l_a` AS `l_b`");
        $this->assertEquals($db ->quoteTable(["a.a","b"]),"`a`.`l_a` AS `l_b`");
        $this->assertEquals($db ->quoteTable(Database::expr("aaa as b")),"aaa as b");
        $this->assertEquals($db ->quoteColumn(["a","b"]),"`a` AS `b`");
        $this->assertEquals($db ->quoteColumn(["a.a","b"]),"`l_a`.`a` AS `b`");
        $expr=Database::expr("if(id>:a,1,2) as t",[":a"=>1]);
        $this->assertEquals($expr->value(), strval($expr));
        $expr->bindParam([":a"=> 2]);
        $eq="if(id>2,1,2) as t";
        $this->assertEquals($expr->compile(),$eq);
        $this->assertEquals($db ->quoteColumn($expr),$eq);
        $this->assertEquals($db ->quoteColumn("*"),"*");
        
        $this->assertEquals($db->escape("aaa\\a"), "aaa\\\\a");
        
    }
    public function testCURD() {
        $this->runCURD(DI::get()->db("database.mysqli"));
        $this->runCURD(DI::get()->db("database.pdo_sqlite"));
        $this->runCURD(DI::get()->db("database.pdo_mysql"));
    }
    protected function runCURD(Database $db) {
        
        \LSYS\EventManager\DI::get()->eventManager()->attach(new EventCallback([
            DBEvent::SQL_START,
            DBEvent::SQL_END,
            DBEvent::SQL_OK,
            DBEvent::SQL_END,
        ], function(\LSYS\EventManager\Event $e){
            $this->assertTrue(!empty($e->data("sql")));
        }));
        \LSYS\EventManager\DI::get()->eventManager()->attach(new EventCallback([
            DBEvent::TRANSACTION_BEGIN,
            DBEvent::TRANSACTION_COMMIT,
            DBEvent::TRANSACTION_ROLLBACK,
        ], function(\LSYS\EventManager\Event $e){
            $this->assertTrue(boolval($e->data("connent")));
        }));
        $db->setEventManager(\LSYS\EventManager\DI::get()->eventManager());
        
        $table_name=$db->quoteTable("order");
        $column=$db ->quoteColumn('sn');
        $titlec=$db ->quoteColumn('title');
        $add_time=$db ->quoteColumn('add_time');
        $val=$db->quote('SN001');
        $title=$db->quote(uniqid("title"));
        $time=$db->quote(time());
        $sql="insert into {$table_name} ({$column},{$titlec},{$add_time}) values ({$val},{$title},{$time});";
        $result=$db->exec($sql);
        $this->assertTrue($result);
        $this->assertEquals($db->lastQuery(), $sql);
        $id=$db->insertId();
        $this->assertTrue(is_numeric($id));
        $row=$db->affectedRows();
        $this->assertTrue(is_numeric($row));
        $_id=$db->quote($id);
        $res=$db->query("select * from {$table_name} where id={$_id}");
        $this->assertEquals($res->count(), "1");
        //预编译
        $usql="UPDATE {$table_name} SET sn=:sn WHERE id=:id ";
        $db->exec($usql,array(":sn"=>"SN002",":id"=>$id));
        $pre=$db->prepare($usql);
        $this->assertTrue($pre->db() ===$db);
        $pre->bindParam(array(
            ":sn"=>"SN003",":id"=>$id
        ))->exec();
        $pre->bindParam(array(
            ":sn"=>"SN004",":id"=>$id
        ))->exec();
        
        $res=$db->query("select * from {$table_name} where id in :id",array(":id"=>[$id,$id]));
        $this->assertEquals($res->get("sn"), "SN004");
        $this->assertEquals($res->asArray()[0]['sn'],'SN004');
        
        
        //事务确认
        $db->beginTransaction();
        $db->exec($sql);
        $idd=$db->insertId();
        $db->commit();
        $bid=$db->quote($idd);
        $res=$db->query("select * from {$table_name} where id={$bid}");
        $res->setFetchMode(Result::FETCH_OBJ);
        $this->assertTrue($res->current() instanceof \stdClass);
        $this->assertEquals($res->count(), "1");
        
       
        
        //事务回滚
        $db->beginTransaction();
        $db->exec($sql);
        $this->assertTrue($db->inTransaction());
        $rid=$db->insertId();
        $db->rollback();
        $rid=$db->quote($rid);
        $tsql="select * from {$table_name} where id={$rid}";
        $res=$db->query($tsql);
        $this->assertEquals($res->count(), "0");
        
        
        //异步
        if ($db instanceof AsyncQuery) {
            $i1=$db->asyncAddQuery("select * from {$table_name} where id={$bid}");
            $i2=$db->asyncAddQuery("select * from {$table_name} where id={$_id}");
            $data=$db->asyncExecute()->result([$i1,$i2]);
            $this->assertTrue(is_array($data));
            $this->assertEquals($data[0]->get("id"),$bid);
            $this->assertEquals($data[1]->get("id"),$_id);
            
            $i1=$db->asyncAddExec($sql);
            $i2=$db->asyncAddQuery("select * from {$table_name} where id=:id",[":id"=>$id]);
            $res=$db->asyncExecute();
            $data=$res->result([$i1,$i2]);
            $this->assertTrue($res->insertId($i1)>0);
            $this->assertTrue($res->affectedRows($i1)>0);
            $this->assertTrue($data[0]);
            $this->assertEquals($data[1]->get("id"),$_id);
            
        }
        
        
        $dsql="delete from {$table_name} where id=:id";
        $result=$db->exec($dsql,array(":id"=>$idd));
        $this->assertTrue($db->affectedRows()>0);
        //exception
        
        try{
            $sql="wrong sql";
            $db->query($sql);
        }catch (\LSYS\Database\Exception $e){
            $this->assertEquals($e->getErrorSql(),$sql);
        }
        
    }
    
    public function testRWCACHE() {
        $this->RUNRWCACHE(DI::get()->db("database.mysqli"));
        $this->RUNRWCACHE(DI::get()->db("database.pdo_mysql"));
        $this->RUNRWCACHE(DI::get()->db("database.mysqli"));
        $this->RUNRWCACHE(DI::get()->db("database.pdo_mysql"));
        $this->RUNRWCACHE(DI::get()->db("database.mysqli"));
        $this->RUNRWCACHE(DI::get()->db("database.pdo_mysql"));
    }
    public function testReconn() {
        $this->runCURD(DI::get()->db("database.mysqli"));
        $this->runCURD(DI::get()->db("database.pdo_mysql"));
    }
    public function runReconn(Database $db) {
        $table_name=$db->quoteTable("order");
        $sql="select * from {$table_name} where id>=:id";
        $db->query($sql,[":id"=>"764"]);
        `sudo service mysql restart`;
        $result= $db->query($sql,[":id"=>"764"]);
        $this->assertTrue($result instanceof Result);
    }
    public function testExpr() {
        $this->runExpr(DI::get()->db("database.mysqli"));
        $this->runExpr(DI::get()->db("database.pdo_mysql"));
    }
    public function runExpr(Database $db) {
        $table_name=$db->quoteTable("order");
        $sql="select * from {$table_name} where id in :id";
        $result= $db->query($sql,[":id"=>Database::expr("(1,2)")]);
        $this->assertTrue($result instanceof Result);
        $sql="UPDATE {$table_name} SET sn=:sn WHERE id>0";
        $result= $db->exec($sql,[":sn"=>Database::expr("CONCAT(sn,'hi')")]);
        $this->assertTrue($result);
    }
    public function testProfiler() {
        $this->runProfiler(DI::get()->db("database.mysqli"));
        $this->runProfiler(DI::get()->db("database.pdo_mysql"));
    }
    public function runProfiler(Database $db) {
        $eventm=\LSYS\EventManager\DI::get()->eventManager();
        $eventm->attach(new ProfilerObserver());
        $db->setEventManager($eventm);
        $table_name=$db->quoteTable("order");
        $sql="select * from {$table_name} where id = :id";
        $db->query($sql,[":id"=>1]);
        $sql="select sleep(1) as t";
        $db->query($sql);
        $this->assertTrue(\LSYS\Profiler\DI::get()->profiler()->appTotal()[0]>1000);//总耗时肯定大于1秒
    }
}