<?php
//根据你实际需求重写这个类
use LSYS\Model\Tools\TraitBuild;
class DomeModelBuild extends TraitBuild{
    protected $_db;
    public function __construct(){
        $this->setSaveDir("../")
            ->setNamespace("Model")
        ;
        $this->_db=LSYS\Database\DI::get()->db();
    }
    public function db(){
        return new \LSYS\Model\Database\Database\MYSQL($this->_db);
    }
    public function listTables():array
    {
//         $sql='SHOW TABLES';
//         $out=[];
//         foreach ($this->_db->query($sql) as $value) {
//             $out[]=array_shift($value);
//         }
        return [$this->_db->tablePrefix()."user",$this->_db->tablePrefix()."email"];//$out;
    }
    public function tablePrefix():string{
        return $this->_db->tablePrefix();
    }
    public function message(string $table,string $msg):void{
        echo $table.":".$msg."\n";
    }
    //yaf model 生成
//     protected function modelFileName(string $model_name):string{
//         return substr($model_name, 0,strlen($model_name)-5);
//     }
    protected function modelName(string $table):string{
            return "Model".$this->tableToName($table);
        }
}