<?php	 

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('db_utils.php');



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

$action = (isset($_GET['action'])) ? stripslashes($_GET['action']) : die ("no action");
$table = (isset($_GET['table'])) ? stripslashes($_GET['table']) : die ("No table");
$profile = (isset($_GET['profile'])) ? stripslashes($_GET['profile']) : die("No profile");

require_once("profiles/$profile/config.php");
isset($config) or die("Invalid config");
require_once('EditableGrid.php');
require_once('pdoDB.php');

$db = new DBClass($config);
isset($db) or die("No DB");

$error="";
// create a new EditableGrid object
if($action == 'add'){
	$return = $db->add($table,$error);
	//var_dump($return);
	// why doesn't this return json
	echo ($error === "" && $return) ? "ok" : json_encode(array('error' => isset($error)?$error:$db->errorInfo()));
	
	die;
}

if ($action == 'update'){
	$return = $db->update($table, $error);
	debug("after update", $error, $error);
	
	echo ($error === "" && $return) ? "ok" : json_encode(array('error' => isset($error)?$error:$db->errorInfo()));
	die;
}

if ($action == 'delete'){
	$return = $db->delete($table);
	echo $return ? "ok" : json_encode(array('error' => $db->errorInfo()));
	die;
}

if ($action == 'duplicate'){
	$return = $db->duplicate($table, $error);
	echo ($error === "" && $return) ? "ok" : json_encode(array('error' => isset($error)?$error:$db->errorInfo()));
	die;
}

$grid = new EditableGrid();
$result = $db->list($table);
add_columns_from_meta($result, $grid, $table);

//var_dump($meta);die;
//die;
// send data to the browser
$grid->renderJSON($result);

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
