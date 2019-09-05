<?php
namespace LSYS\Model\Tools;
use LSYS\Model\Database;
use LSYS\Entity\ColumnSet;
use LSYS\Entity\Column;
abstract class TraitBuild{
    private $_dir;
    private $_namespace;
    private $_create_model=1;
    private $_create_entity=1;
    private $_create_model_trait=1;
    private $_create_entity_trait=1;
    public function __construct($dir){
        $this->setSaveDir($dir);
    }
    /**
     * 设置创建状态,如要将model或entity生成到不同目录时候
     * @param number $is_create_model
     * @param number $is_create_entity
     * @param number $is_create_model_trait
     * @param number $is_create_entity_trait
     * @return static
     */
    public function setCreateStatus(
        $is_create_model=1,
        $is_create_entity=1,
        $is_create_model_trait=1,
        $is_create_entity_trait=1
    ){
        $this->_create_entity=$is_create_entity;
        $this->_create_model=$is_create_model;
        $this->_create_entity_trait=$is_create_model_trait;
        $this->_create_model_trait=$is_create_entity_trait;
        return $this;
    }
    /**
     * 消息输出
     * @param string $table
     * @param string $msg
     */
    public function message($table,$msg){
        //your output
    }
    /**
     * 设置保存目录
     * @param string $dir
     * @return \LSYS\Model\Tools\TraitBuild
     */
    public function setSaveDir($dir){
        $this->_dir=$dir;
        return $this;
    }
    /**
     * 设置命名空间
     * @param string $namespace
     * @return \LSYS\Model\Tools\TraitBuild
     */
    public function setNamespace($namespace){
        $this->_namespace=$namespace;
        return $this;
    }
    /**
     * 模型继承的父类名
     * 不希望直接继承\LSYS\Model 时重写此方法
     * @return string
     */
    public function parentModelClassName(){
        return \LSYS\Model::class;
    }
    /**
     * 实体继承的父类名
     * 不希望直接继承\LSYS\Model\Entity 时重写此方法
     * @return string
     */
    public function parentEntityClassName(){
        return \LSYS\Model\Entity::class;
    }
    /**
     * 根据表名生成model名
     * @param string $table
     * @return string
     */
    protected function tableToName($table){
        return str_replace(" ",'',ucwords(str_replace("_",' ', $table)));
    }
    private function replaceTpl($tpl,$name,$val,$warp=false){
        $name="__LSYS_TPL_{$name}__";
        if ($warp)$name="/*{$name}*/";
        $tpl=str_replace($name,$val,$tpl);
        if ($warp&&$val==null){
            $tpl=preg_replace("/\n\s*\n/i", '', $tpl);
        }
        return $tpl;
    }
    /**
     * MODEL名生成
     * @param string $table
     * @return string
     */
    protected function modelName($table){
        return 'Model'.$this->tableToName($table);
    }
    /**
     * 创建model文件名
     * @param string $model_name
     * @return string
     */
    protected function modelFileName($model_name){
        return $model_name;
    }
    /**
     * model片段名生成
     * @param string $table
     * @return string
     */
    protected function modelTraitName($table){
        return 'Model'.$this->tableToName($table)."Trait";
    }
    /**
     * 创建entity文件名
     * @param string $entity_name
     * @return string
     */
    protected function entityFileName($entity_name){
        return $entity_name;
    }
    /**
     * entity名生成
     * @param string $table
     * @return string
     */
    protected function entityName($table){
        return 'Entity'.$this->tableToName($table);
    }
    /**
     * entity片段名
     * @param string $table
     * @return string
     */
    protected function entityTraitName($table){
        return 'Entity'.$this->tableToName($table)."Trait";
    }
    /**
     * model片段模板
     * @return string
     */
    protected function traitModelTpl(){
        return file_get_contents(__DIR__.'/../../../../tpls/TraitModelTpl.php');
    }
    /**
     * entity片段模板
     * @return string
     */
    protected function traitEntityTpl(){
        return file_get_contents(__DIR__.'/../../../../tpls/TraitEntityTpl.php');
    }
    /**
     * model模板
     * @return string
     */
    protected function modelTpl(){
        return file_get_contents(__DIR__.'/../../../../tpls/ModelTpl.php');
    }
    /**
     * entity末班
     * @return string
     */
    protected function entityTpl(){
        return file_get_contents(__DIR__.'/../../../../tpls/EntityTpl.php');
    }
    /**
     * 生成entity注释
     * @param ColumnSet $set
     * @param string $model_name
     * @return string
     */
    protected function createEntityTraitDoc(ColumnSet $set,$model_name){
        $doc=[];
        foreach ($set as $column){
            assert($column instanceof Column);
            $type='string';
            if(!is_array($column->getType()))$type=strval($column->getType());
            $name=$column->name();
            $commit=strval($column->comment());
            $commit=str_replace(["\n","\r"],' ', $commit);
            $commit=str_replace(",",' ', $commit);
            if ($column->useDefault()) {
                $commit.=" [".$column->getDefault().']';
            }
            $doc[]=" * @property {$type} \${$name}\t{$commit}";
        }
        $doc[]=" * @method {$model_name} table()";
        $doc=implode("\n", $doc);
        return "/**\n{$doc}\n*/";
        //格式如下
        /**
         * @property int $id ID
         * @method \Model\ModelUser table()
         */
    }
    /**
     * 生成model注释
     * @param string $entity_name
     * @return string
     */
    protected function createModelTraitDoc($entity_name){
        $doc=[];
        $doc[]=" * @method {$entity_name} find()";
        $doc[]=" * @method {$entity_name} queryOne(\$sql,\$column_set=null,array \$patch_columns=[])";
        $doc[]=" * @method \LSYS\Entity\EntitySet|{$entity_name}[] findAll()";
        $doc[]=" * @method \LSYS\Entity\EntitySet|{$entity_name}[] queryAll(\$sql,\$column_set=null,array \$patch_columns=[])";
        $doc=implode("\n", $doc);
        return "/**\n{$doc}\n*/";
        //示例格式如下
        /**
         * @method \Model\EntityUser find()
         * @method \Model\EntityUser queryOne($sql,$column_set=null,array $patch_columns=[])
         * @method \LSYS\Entity\EntitySet|\Model\EntityUser[] findAll()
         * @method \LSYS\Entity\EntitySet|\Model\EntityUser[] queryAll($sql,$column_set=null,array $patch_columns=[])
         */
    }
    /**
     * 生成字段列表
     * @param ColumnSet $set
     * @return string
     */
    protected function createEntityTraitColumnCode(ColumnSet $set){
        $column_name=\LSYS\Entity\Column::class;
        $code=[];
        foreach ($set as $column){
            assert($column instanceof Column);
            $name=$column->name();
            $name=var_export($name,1);
            $tmp="(new \\{$column_name}({$name}))";
            $type=var_export($column->getType(),1);
            $tmp.="->setType({$type})";
            if ($column->useDefault()) {
                $default=$column->getDefault();
                if(is_numeric($default))$default+=0;
                $default=var_export($default,1);
                $tmp.="->setDefault({$default})";
            }
            $commit=$column->comment();
            if(!empty($commit)){
                $commit=var_export($column->comment(),1);
                $commit=trim(str_replace(["\n","\r"],' ', $commit),'\t\r\n');
                $tmp.="->setComment({$commit})";
            }
            $code[]=$tmp;
        }
        return implode(",\n\t\t\t",$code);
        //格式如下
        // (new \LSYS\Entity\Column('id'))
    }
    /**
     * 创建代码
     * @throws \Exception
     */
    public function build(){
        $class_dir=rtrim($this->_dir,"\/")."/";
        if(!is_dir($class_dir)){
            throw new \Exception(strtr("dir [:dir] does not exist.", array(":dir"=>$class_dir)));
        }
        $namespace=$this->_namespace;
        if (empty($namespace))$namespace=null;
        else{
            $namespaces=explode("\\", $namespace);
            while ($dir=array_shift($namespaces)){
                $class_dir.=DIRECTORY_SEPARATOR.$dir;
                is_dir($class_dir)||mkdir($class_dir);
            }
            $class_dir.=DIRECTORY_SEPARATOR;
        }
        if (!$namespace)$p_namespace='';
        else $p_namespace='namespace '.$namespace.';';
        $auto_namespace='Traits';
        $auto_class_dir=$class_dir.$auto_namespace.DIRECTORY_SEPARATOR;
        is_dir($auto_class_dir)||mkdir($auto_class_dir);
        if ($namespace) $auto_namespace=$namespace.'\\'.$auto_namespace;
        $p_auto_namespace='namespace '.$auto_namespace.';';
        $trait_orm_tpl=$this->traitModelTpl();
        $orm_tpl=$this->modelTpl();
        $trait_entity_tpl=$this->TraitEntityTpl();
        $entity_tpl=$this->EntityTpl();
        
        $tp=$this->tablePrefix();
        $tables=$this->listTables();
        $db=$this->db();
        foreach ($tables as $table){
            if (!empty($tp)){
                if(strpos($table, $tp)!==0){
                    $this->message($table,"not match");
                    continue;
                }
                $table_name = substr($table, strlen($tp));
            }else $table_name = $table;
            
            $this->message($table,"create start");
            
            
            $columnset=$db->listColumns($db->quoteTable($table_name));
            $model_name=$this->modelName($table_name);
            $model_trait_name=$this->modelTraitName($table_name);
            $entity_name=$this->entityName($table_name);
            $entity_trait_name=$this->entityTraitName($table_name);
          
            
            $fentity_name=($namespace?"\\":"").$namespace."\\".$entity_name;
            $orm_doc=$this->createModelTraitDoc($fentity_name);
            $orm_file=$auto_class_dir.$model_trait_name.".php";
            $columncode=$this->createEntityTraitColumnCode($columnset->columnSet());
            $pk=$columnset->primaryKey();
            if(is_array($pk))$pk=var_export($pk,1);
            if($this->_create_model_trait){
                $tpl=$this->replaceTpl($trait_orm_tpl,'NAMESPACE',$p_auto_namespace,true);
                $tpl=$this->replaceTpl($tpl,'DOC',$orm_doc,true);
                $tpl=$this->replaceTpl($tpl,'TRAIT_MODEL',$model_trait_name);
                $tpl=$this->replaceTpl($tpl,'COLUMNS',$columncode,true);
                $tpl=$this->replaceTpl($tpl,'PK',$pk);
                $tpl=$this->replaceTpl($tpl,'ENTITY_CLASS',$fentity_name."::class",true);
                $tpl=$this->replaceTpl($tpl,'TABLE_NAME',$table_name);
                file_put_contents($orm_file, $tpl);
            }
            
            $orm_file=$class_dir.$this->modelFileName($model_name).".php";
            if ($this->_create_model&&!is_file($orm_file)){
                $tpl=$this->replaceTpl($orm_tpl,'NAMESPACE',$p_namespace,true);
                $tpl=$this->replaceTpl($tpl,'PARENT_MODEL'," extends \\".ltrim($this->parentModelClassName(),"\\"),true);
                $tpl=$this->replaceTpl($tpl,'MODEL',$model_name);
                $tpl=$this->replaceTpl($tpl,'TRAIT_MODEL',"\\".$auto_namespace."\\".$model_trait_name);
                file_put_contents($orm_file, $tpl);
            }
            
            $fmodel_name=($namespace?"\\":"").$namespace."\\".$model_name;
            $entity_doc=$this->createEntityTraitDoc($columnset->columnSet(), $fmodel_name);
            $entity_file=$auto_class_dir.$entity_trait_name.".php";
            if($this->_create_entity_trait){
                $tpl=$this->replaceTpl($trait_entity_tpl,'NAMESPACE',$p_auto_namespace,true);
                $tpl=$this->replaceTpl($tpl,'DOC',$entity_doc,true);
                $tpl=$this->replaceTpl($tpl,'TRAIT_ENTITY',$entity_trait_name);
                $tpl=$this->replaceTpl($tpl,'MODEL_CLASS',$fmodel_name."::class",true);
                file_put_contents($entity_file, $tpl);
            }
            
            $entity_file=$class_dir.$this->entityFileName($entity_name).".php";
            if ($this->_create_entity&&!is_file($entity_file)){
                 $tpl=$this->replaceTpl($entity_tpl,'NAMESPACE',$p_namespace,true);
                 $tpl=$this->replaceTpl($tpl,'PARENT_ENTITY'," extends \\".ltrim($this->parentEntityClassName(),"\\"),true);
                 $tpl=$this->replaceTpl($tpl,'ENTITY',$entity_name);
                 $tpl=$this->replaceTpl($tpl,'TRAIT_ENTITY',"\\".$auto_namespace."\\".$entity_trait_name);
                 file_put_contents($entity_file, $tpl);
            }
            unset($tpl);
            
            $this->message($table,"create success");
        }
    }
    /**
     * 表前缀
     * @return string
     */
    protected function tablePrefix(){
        return '';
    }
    /**
     * 数据库操作对象
     * @return Database
     */
    abstract protected function db();
    /**
     * 得到指定数据的表
     * @return array
     */
    abstract protected function listTables();
}