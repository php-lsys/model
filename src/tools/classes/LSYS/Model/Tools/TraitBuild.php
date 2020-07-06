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
    private $_create_builder=1;
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
        bool $is_create_model=true,
        bool $is_create_entity=true,
        bool $is_create_model_trait=true,
        bool $is_create_entity_trait=true,
        bool $is_create_builder=true
    ){
        $this->_create_entity=$is_create_entity;
        $this->_create_model=$is_create_model;
        $this->_create_entity_trait=$is_create_model_trait;
        $this->_create_model_trait=$is_create_entity_trait;
        $this->_create_builder=$is_create_builder;
        return $this;
    }
    /**
     * 消息输出
     * @param string $table
     * @param string $msg
     */
    public function message(string $table,string $msg):void{
        //your output
    }
    /**
     * 设置保存目录
     * @param string $dir
     * @return $this
     */
    public function setSaveDir(string $dir){
        $this->_dir=$dir;
        return $this;
    }
    /**
     * 设置命名空间
     * @param string $namespace
     * @return $this
     */
    public function setNamespace(string $namespace){
        $this->_namespace=$namespace;
        return $this;
    }
    /**
     * 模型继承的父类名
     * 不希望直接继承\LSYS\Model 时重写此方法
     * @return string
     */
    public function parentModelClassName():string{
        return \LSYS\Model::class;
    }
    /**
     * 数据库请求构造器的父类名
     * @return string
     */
    public function parentBuilderClassName():string{
        return \LSYS\Model\Database\Builder::class;
    }
    /**
     * 实体继承的父类名
     * 不希望直接继承\LSYS\Model\Entity 时重写此方法
     * @return string
     */
    public function parentEntityClassName():string{
        return \LSYS\Model\Entity::class;
    }
    /**
     * 根据表名生成model名
     * @param string $table
     * @return string
     */
    protected function tableToName(string $table):string{
        return str_replace(" ",'',ucwords(str_replace("_",' ', $table)));
    }
    private function replaceTpl(string $tpl,string $name,?string $val,bool $warp=false):string {
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
    protected function modelName(string $table):string {
        return 'Model'.$this->tableToName($table);
    }
    /**
     * 创建model文件名
     * @param string $model_name
     * @return string
     */
    protected function modelFileName(string $model_name):string {
        return $model_name;
    }
    /**
     * 数据库执行构造器名
     * @param string $builder_name
     * @return string
     */
    protected function builderName(string $table):string {
        return 'Builder'.$this->tableToName($table);
    }
    /**
     * 数据库执行构造器文件名
     * @param string $builder_name
     * @return string
     */
    protected function builderFileName(string $builder_name):string {
        return $builder_name;
    }
    /**
     * model片段名生成
     * @param string $table
     * @return string
     */
    protected function modelTraitName(string $table):string {
        return 'Model'.$this->tableToName($table)."Trait";
    }
    /**
     * 创建entity文件名
     * @param string $entity_name
     * @return string
     */
    protected function entityFileName(string $entity_name):string {
        return $entity_name;
    }
    /**
     * entity名生成
     * @param string $table
     * @return string
     */
    protected function entityName(string $table):string {
        return 'Entity'.$this->tableToName($table);
    }
    /**
     * entity片段名
     * @param string $table
     * @return string
     */
    protected function entityTraitName(string $table):string {
        return 'Entity'.$this->tableToName($table)."Trait";
    }
    /**
     * model片段模板
     * @return string
     */
    protected function traitModelTpl():string {
        return file_get_contents(__DIR__.'/../../../../tpls/TraitModelTpl.php');
    }
    /**
     * entity片段模板
     * @return string
     */
    protected function traitEntityTpl():string {
        return file_get_contents(__DIR__.'/../../../../tpls/TraitEntityTpl.php');
    }
    /**
     * model模板
     * @return string
     */
    protected function modelTpl():string {
        return file_get_contents(__DIR__.'/../../../../tpls/ModelTpl.php');
    }
    /**
     * entity模板
     * @return string
     */
    protected function entityTpl():string {
        return file_get_contents(__DIR__.'/../../../../tpls/EntityTpl.php');
    }
    /**
     * builder模板
     * @return string
     */
    protected function builderTpl():string {
        return file_get_contents(__DIR__.'/../../../../tpls/BuilderTpl.php');
    }
    /**
     * 生成entity注释
     * @param ColumnSet $set
     * @param string $model_name
     * @return string
     */
    protected function createEntityTraitDoc(ColumnSet $set,string $model_name):string {
        $doc=[];
        foreach ($set as $column){
            assert($column instanceof Column);
            $type='string';
            if(!is_array($column->getType()))$type=strval($column->getType());
            $name=$column->name();
            $commit=strval($column->comment());
            $commit=str_replace(["\n","\r"],' ', $commit);
            $commit=str_replace(",",' ', $commit);
            if ($column->useDefault()&&!empty($column->getDefault())) {
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
    protected function createBuilderDoc(string $entity_name,string $model_name):string {
        $doc=[];
        $doc[]=" * @method {$model_name} table()";
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
    protected function createEntityTraitColumnCode(ColumnSet $set):string {
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
     * 替换model中的数据库执行构造器
     * @param string $builder_name
     * @return string
     */
    protected function createBuilderMethod(string $builder_name):string {
        return "
    /**
     * 数据库执行构造器
     * @rewrite
     * @return $builder_name
     */
    public function dbBuilder() {
       return new {$builder_name}(\$this);
    }
        ";
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
        $builder_tpl=$this->builderTpl();
        
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
            
            
            $columnset=$db->listColumns($db->quoteTable($tp.$table_name));
            $model_name=$this->modelName($table_name);
            $model_trait_name=$this->modelTraitName($table_name);
            $entity_name=$this->entityName($table_name);
            $entity_trait_name=$this->entityTraitName($table_name);
            $builder_name=$this->builderName($table_name);
            $builder_file_name=$this->builderFileName($builder_name);
          
            
            if ($this->_create_builder) {
                $builder_file=$auto_class_dir.$builder_file_name.".php";
                $tpl=$this->replaceTpl($builder_tpl,'NAMESPACE',$p_auto_namespace,true);
                $tpl=$this->replaceTpl($tpl,'BUILDER',$builder_name,false);
                $fentity_name=($namespace?"\\":"").$namespace."\\".$entity_name;
                $fmodel_name=($namespace?"\\":"").$namespace."\\".$model_name;
                $builder_doc=$this->createBuilderDoc($fentity_name,$fmodel_name);
                $tpl=$this->replaceTpl($tpl,'DOC',$builder_doc,true);
                $tpl=$this->replaceTpl($tpl,'PARENT_BUILDER'," extends \\".ltrim($this->parentBuilderClassName(),"\\"),true);
                file_put_contents($builder_file, $tpl);
            }
            
            
            $fentity_name=($namespace?"\\":"").$namespace."\\".$entity_name;
            $orm_file=$auto_class_dir.$model_trait_name.".php";
            $columncode=$this->createEntityTraitColumnCode($columnset->columnSet());
            $pk=$columnset->primaryKey();
            if(is_array($pk))$pk=var_export($pk,1);
            else $pk="'{$pk}'";
            if($this->_create_model_trait){
                $tpl=$this->replaceTpl($trait_orm_tpl,'NAMESPACE',$p_auto_namespace,true);
                $tpl=$this->replaceTpl($tpl,'TRAIT_MODEL',$model_trait_name);
                $tpl=$this->replaceTpl($tpl,'COLUMNS',$columncode,true);
                $tpl=$this->replaceTpl($tpl,'PK',$pk,true);
                $tpl=$this->replaceTpl($tpl,'ENTITY_CLASS',$fentity_name."::class",true);
                $tpl=$this->replaceTpl($tpl,'TABLE_NAME',$table_name);
                $builder_method='';
                if ($this->_create_builder){
                    $fbuilder_name=($auto_namespace?"\\":"").$auto_namespace.'\\'.$builder_name;
                    $builder_method=$this->createBuilderMethod($fbuilder_name);
                }
                $tpl=$this->replaceTpl($tpl,'BUILDER_METHOD',$builder_method,true);
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
    protected function tablePrefix():string {
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
    abstract protected function listTables():array;
}