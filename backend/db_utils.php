<?php



function debug()  {
	//$result = $sth ? 'ok' : 'error';
	$stamp = date("Y-m-d H:i:s");
	//TODO: get this to work
	$args = func_get_args(); 
	$line = join(":",[$args[0],$args[1]]);
	$line = "$stamp:$line\n";
	file_put_contents('backend.log',$line,FILE_APPEND);
}

function fail($message='fail') {
	debug('fail',$message);
	die($message);
}

function add_columns_from_meta($result, $grid, $table){
	global $db;
	//$meta = $db->get_table_columns($table);
	$meta = $db->get_meta($table);
	// var_dump($meta);die;
	//$grid->addColumn('id', 'ID', 'integer', NULL, false); 
	foreach($meta as $name => $v){
		$editable = $v['editable']; $name === 'id' and $editable = false;
		//$type = get_col_type($v["native_type"],$name);
		$type = $v['type'];
		
		if($type === false)
		    continue;
		
		
		$grid->addColumn($name,$v['label'],$type,NULL,$editable);
		//public function addColumn($name, $label, $type, $values = NULL, $editable = true, $field = NULL, $bar = true, $hidden = false)
		//echo $v["native_type"] . "...$type\n";
		//if($v['name'] == 'id') continue;
		//$name = $v['name'];
		//$pos = strpos($name, 'id_');
		//if($pos !== false){
		//	$instr = substr($name, 3);
		//	$grid->addColumn($name, $instr, 'string', $db->fetch_pairs('SELECT id, name FROM ' . $instr),true );  
		//}else{
	}
	$grid->addColumn('action', 'Action', 'html', NULL, false, 'id');
	//die;
}  


function get_col_type($type,$name=''){
	$type=strtolower($type);
	if ($name === 'email') {
		return 'email';
	}
	elseif(preg_match('/string|blob|char|text/',$type)) {
		return 'string';
	}
	elseif(preg_match('/int|long/',$type)) {
		return 'integer';
	}
	elseif(preg_match('/float|decimal|numeric/',$type)) {
		return 'float';
	}
	elseif($type == 'date') {
		return 'date';
	}
	elseif(preg_match('/date|time/',$type)) {
		return 'string';
	}
	elseif(preg_match('/tiny|bool/',$type)) {
		return 'boolean';
	}
	else {

		//fail ("Unrecognised type $type");
		debug('get_col_type', $type,'Unrecognised type');
		return false;
	}
	return 'string';

}
