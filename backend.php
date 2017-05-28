<?php	 

require_once('config.php'); 
require_once('EditableGrid.php');
require_once('pdoDB.php'); 

function debug($op,$query,$sth='no_result')  {
	$result = $sth ? 'ok' : 'error';
	file_put_contents('db.log', "$op\n$query\n$result\n\n",FILE_APPEND);
}
debug('op','query','result',0);

function get_col_type($type,$name){
	//echo $type;
	//echo "...". $type."\n";
	$type=strtolower($type);
	if ($name === 'email') 
		return 'email';
	elseif(strpos($type,'string') !== false)
		return 'string';
	elseif(strpos($type,'long') !== false)
		return 'integer';
	elseif(strpos($type, 'decimal')!== false)
		return 'float';
	elseif(strpos($type, 'float')!== false)
		return 'float';
	elseif(strpos($type, 'date')!== false)
		return 'date';
	elseif(strpos($type, 'tiny')!== false) // only true for mysql?
		return 'boolean';
	elseif(strpos($type, 'bool')!== false)
		return 'boolean';
	elseif(strpos($type, 'blob')!== false)
		return 'string';
	else {
		echo "Unrecognised type $type";
		die;
	}
	return 'integer';
}

function add_columns_from_meta($result, $grid, $table){
	global $db;
	$meta = $db->get_table_columns($table);
	// var_dump($meta);die;
	//$grid->addColumn('id', 'ID', 'integer', NULL, false); 
	foreach($meta as $name => $v){
		$editable = true; $name === 'id' and $editable = false;
		$type = get_col_type($v["native_type"],$name);
		$grid->addColumn($name,$name,$type,NULL,$editable);
		//echo $v["native_type"] . "...$type\n";
		//if($v['name'] == 'id')
		//	continue;
		//$name = $v['name'];
		//$pos = strpos($name, 'id_');
		//if($pos !== false){
		//	$instr = substr($name, 3);
		//	$grid->addColumn($name, $instr, 'string', $db->fetch_pairs('SELECT id, name FROM ' . $instr),true );  
		//}else{
		//public function addColumn($name, $label, $type, $values = NULL, $editable = true, $field = NULL, $bar = true, $hidden = false)
	}
	$grid->addColumn('action', 'Action', 'html', NULL, false, 'id');
	//die;
}  

	$table = (isset($_GET['table'])) ? stripslashes($_GET['table']) : 'demo';
	$action = (isset($_GET['action'])) ? stripslashes($_GET['action']) : 'list';

if($action == 'add'){
	$return = $db->add($table);
	//var_dump($return);
	// why doesn't this return json
	if($return){
		$json = json_encode($return);
		//file_put_contents('update.log', $json ."\n");
		echo $json;
	}
  else{
		echo "error";  
	}
	//echo $return ? $data . $return : "error";  
	die;
}

if ($action == 'update'){
	$return = $db->update($table);
	echo $return ? "ok" : "error";  
	die;
}

if ($action == 'delete'){
	$return = $db->delete($table);
	echo $return ? "ok" : "error";  
	die;
}

if ($action == 'duplicate'){
	$return = $db->duplicate($table);
	echo $return ? "ok" : "error";  
	die;
}
				
// create a new EditableGrid object
$grid = new EditableGrid();
/* 
*  Add columns. The first argument of addColumn is the name of the field in the databse. 
*  The second argument is the label that will be displayed in the header
*/
/*
$grid->addColumn('id', 'ID', 'integer', NULL, false); 
$grid->addColumn('name', 'Name', 'string');  
$grid->addColumn('firstname', 'Firstname', 'string');  
$grid->addColumn('age', 'Age', 'integer');  
$grid->addColumn('height', 'Height', 'float');  
*/

/* The column id_country and id_continent will show a list of all available countries and continents. So, we select all rows from the tables */
/*
$grid->addColumn('id_continent', 'Continent', 'string' , $db->fetch_pairs('SELECT id, name FROM continent'),true);  
$grid->addColumn('id_country', 'Country', 'string', $db->fetch_pairs('SELECT id, name FROM country'),true );  
$grid->addColumn('email', 'Email', 'email');											   
$grid->addColumn('freelance', 'Freelance', 'boolean');  
$grid->addColumn('lastvisit', 'Lastvisit', 'date');  
$grid->addColumn('website', 'Website', 'string');  
$grid->addColumn('action', 'Action', 'html', NULL, false, 'id');  
*/

$sql = 'SELECT * FROM '.$table;
$result = $db->query($sql);
add_columns_from_meta($result, $grid, $table);

//var_dump($meta);die;
//die;
// send data to the browser
$grid->renderJSON($result);

