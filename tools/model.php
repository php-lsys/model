<?php
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
	require __DIR__ . '/../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../../../autoload.php')) {
	require __DIR__ . '/../../../autoload.php';
} else {
	echo 'Cannot find the vendor directory, have you executed composer install?' . PHP_EOL;
	echo 'See https://getcomposer.org to get Composer.' . PHP_EOL;
	exit(1);
}
if (isset($argv)&&array_search( "--help",$argv)||count($argv)==1){
	echo "ORM create tools param:\n";
	echo "\t--namespace= your class namespace\n";	
	echo "\t--save_dir= your save dir\n";
	echo "\t--tables= used table list [,]\n";	
	exit;
}


function replace_tpl($tpl,$name,$val,$warp=false){
	$name="__LSYS_TPL_{$name}__";
	if ($warp)$name="/*{$name}*/";
	$tpl=str_replace($name,$val,$tpl);
	if ($warp&&$val==null){
		$tpl=preg_replace("/\n\s*\n/i", '', $tpl);
	}
	return $tpl;
};


function cli_param($name,$defalut=NULL){
	static $param;
	if ($param===NULL){
		global $argv;
		$param=array();
		foreach ($argv as $v){
			$p=strpos($v, "=");
			if ($p!==false&&substr($v, 0,2)=='--'){
				$param[substr($v, 2,$p-2)]=substr($v,$p+1);
			}
		}
	}
	if (isset($param[$name])) return trim($param[$name]);
	return $defalut;
};


$namespace=cli_param("namespace",'Model');

$class_dir=cli_param("save_dir","./");
$class_dir=trim($class_dir,"/\\")."/";

$split=true;

$_tables=cli_param("tables","");
if (empty($_tables))$_tables=array();
else $_tables=explode(",",$_tables);
$_tables=array_map('trim',$_tables);
foreach ($_tables as $k=>$v){
	if (empty($v))unset($_tables[$k]);
}

$config=cli_param("config_dir",null);
if ($config!=null) LSYS\Config\File::dirs(array($config));


$db=\LSYS\Model\DI::get()->modelDB();

$tables=$db->list_tables();
if(!is_dir($class_dir))return die(strtr("class_dir[:dir] not find", array(":dir"=>$class_dir)));
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
$split&&(is_dir($auto_class_dir)||mkdir($auto_class_dir));
if ($namespace) $auto_namespace=$namespace.'\\'.$auto_namespace;
$p_auto_namespace='namespace '.$auto_namespace.';';
$trait_orm_tpl=file_get_contents(__DIR__.'/tpls/TraitORMTpl.php');
$orm_tpl=file_get_contents(__DIR__.'/tpls/ORMTpl.php');
$trait_entity_tpl=file_get_contents(__DIR__.'/tpls/TraitEntityTpl.php');
$entity_tpl=file_get_contents(__DIR__.'/tpls/EntityTpl.php');

if ($db_config)$db_config="protected \$_db_config= '{$db_config}';\n\t";

$tp=$db->table_prefix();
foreach ($tables as $table){
	if (!empty($tp)){
		if(strpos($table, $tp)!==0){
			echo "[{$table}] not match {$tp}\n";
			continue;
		}
		$table_name = substr($table, strlen($tp));
	}else $table_name = $table;
	
	if (count($_tables)>0&&!in_array($table_name, $_tables)){
		echo "[{$table}] not in table list\n";
		continue;
	}
	
	echo "[{$table}] is created \n";
	
	
	$class_name=str_replace(" ",'',ucwords(str_replace("_",' ', $table_name)));;
	$columns=$db->list_columns($db->quote_table($table_name));
	
	$table_name="protected \$_table_name = '".addslashes($table_name)."';\n";
	
	$primary_key = 'id';
	$orm_columns=[];
	$tab="\t\t";
	$labels=$entity_columns_doc=[];
	foreach ($columns as $column){
		if ($column['key']=='PRI') $primary_key=$column['column_name'];
		$orm_columns[$column['column_name']]=$column['column_default'];
		$type='string';
		$comm=trim($column['comment']);
		$t=NULL;
		if (isset($column['type'])){
			$type=$column['type'];
			if (!empty($comm)){
				$comm="\t * {$column['comment']}\n";
				$column['comment']=str_replace(",",' ', $column['comment']);
				foreach(explode(' ', $column['comment']) as $v){
					$v=trim($v);
					if (!empty($v)){$t=$v;break;}
				}
				if ($t) $labels[$column['column_name']]=$t;
			}
		}
		$entity_columns_doc[]=" * @property {$type} \${$column['column_name']}\t{$t}\n";
	}
	ob_start();
	var_export($orm_columns);
	$orm_columns=ob_get_clean();
	$orm_columns=ltrim(str_replace("\n", "\n\t", $orm_columns),"\t");
	$orm_columns="\tprotected \$_table_defaults = {$orm_columns};";
	
	
	if ($primary_key=='id')$primary_key="";
	else $primary_key="\tprotected \$_primary_key = '{$primary_key}';\n\t";
	
	if (count($labels)==0)$labels="";
	else{
		ob_start();
		var_export($labels);
		$labels=ob_get_contents();
		ob_end_clean();
		$labels=str_replace("\n", "\n\t\t", $labels);
		$labels="\n\tpublic function labels() {
		return {$labels};\n\t}";
	}
	
	$orm_name="ORM".$class_name;
	$entity_name="Entity".$class_name;
	
	
	$doc_namespace=$namespace?('\\'.$namespace):'';
	$entity_columns_doc=trim("\n".implode("", $entity_columns_doc));
	$entity_doc="/**
 {$entity_columns_doc}
 * @method {$doc_namespace}\\{$orm_name} orm()
 */";
	$orm_doc="/**
 * @method {$doc_namespace}\\{$entity_name} find(\$columns=null)
 * @method \LSYS\ORM\Result|{$doc_namespace}\\{$entity_name}[] find_all(\$columns=null)
 */";
	
	
	
	$orm_fill=$db_config.$primary_key.$table_name.$orm_columns.$labels;
	
	$use_namespace=$auto_namespace?('\\'.$auto_namespace):'';
	$use_orm="use {$use_namespace}\\{$orm_name};";
	$use_entity="use {$use_namespace}\\{$entity_name};";
	
	//auto orm
	$orm_file=$auto_class_dir.$orm_name.".php";
	$tpl=replace_tpl($trait_orm_tpl,'P_AUTO_NAMESPACE',$p_auto_namespace,true);
	$tpl=replace_tpl($tpl,'AUTO_ORM',$orm_name);
	$tpl=replace_tpl($tpl,'DOC',$orm_doc,true);
	$tpl=replace_tpl($tpl,'AUTO_CODE',$orm_fill,true);
	file_put_contents($orm_file, $tpl);
	//orm
	$orm_file=$class_dir.$orm_name.".php";
	if (!is_file($orm_file)){
		$tpl=replace_tpl($orm_tpl,'P_NAMESPACE',$p_namespace,true);
		$tpl=replace_tpl($tpl,'ORM',$orm_name);
		$tpl=replace_tpl($tpl,'AUTO_ORM','\\'.($auto_namespace?$auto_namespace.'\\':'').$orm_name);
		$tpl=replace_tpl($tpl,'DOC',null,true);
		$tpl=replace_tpl($tpl,'AUTO_CODE','',true);
		file_put_contents($orm_file, $tpl);
	}
	//auto entity
	$entity_file=$auto_class_dir.$entity_name.".php";
	$tpl=replace_tpl($trait_entity_tpl,'P_AUTO_NAMESPACE',$p_auto_namespace,true);
	$tpl=replace_tpl($tpl,'AUTO_ENTITY',$entity_name);
	$tpl=replace_tpl($tpl,'DOC',$entity_doc,true);
	file_put_contents($entity_file, $tpl);
	//entity
	$entity_file=$class_dir.$entity_name.".php";
	if (!is_file($entity_file)){
		$tpl=replace_tpl($entity_tpl,'P_NAMESPACE',$p_namespace,true);
		$tpl=replace_tpl($tpl,'ENTITY',$entity_name);
		$tpl=replace_tpl($tpl,'AUTO_ENTITY','\\'.($auto_namespace?$auto_namespace.'\\':'').$entity_name);
		$tpl=replace_tpl($tpl,'DOC',NULL,true);
		file_put_contents($entity_file, $tpl);
	}
	
	unset($tpl);
}

