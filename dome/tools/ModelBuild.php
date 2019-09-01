<?php
//根据你实际需求重写这个类
use LSYS\Model\Tools\TraitBuild;
class DomeModelBuild extends TraitBuild{
    protected $_db;
    public function __construct(){
        $this->setSaveDir("./")
            ->setNamespace("Model")
        ;
        $this->_db=LSYS\Database\DI::get()->db();
    }
    public function db(){
        return new \LSYS\Model\Database\Database($this->_db);
    }
    public function listTables()
    {
        return $this->_db->listTables();
    }
    public function tablePrefix(){
        return $this->_db->tablePrefix();
    }
    public function message($table,$msg){
        echo $table.$msg."\n";
    }
    //yaf model 生成
    //     protected function modelFileName($model_name){
    //         return substr($model_name, 0,strlen($model_name)-5);
    //     }
        protected function modelName($table){
            return "ORM".$this->tableToName($table);
        }
}