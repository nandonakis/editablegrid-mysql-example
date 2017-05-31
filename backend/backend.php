<?php	 

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('db_utils.php');

$action = (isset($_GET['action'])) ? stripslashes($_GET['action']) : die ("no action");
$table = (isset($_GET['table'])) ? stripslashes($_GET['table']) : die ("No table");
$profile = (isset($_GET['profile'])) ? stripslashes($_GET['profile']) : die("No profile");

require_once("profiles/$profile/config.php");
isset($config) or die("Invalid config");

require_once('EditableGrid.php');
require_once('BackendDB.php');

$db = new BackendDB($config);

isset($db) or die("No DB");

$error="";
// create a new EditableGrid object
if($action == 'add'){
	$return = $db->add($table, $_POST, $error);
	//var_dump($return);
	// why doesn't this return json
	show_result($return, $error);
	
	die;
}

if ($action == 'update'){
	$return = $db->update($table, $_POST, $error);
	debug("after update", $error, $error);
	
	show_result($return, $error);
	die;
}

if ($action == 'delete'){
	$return = $db->delete($table, $_POST);
	echo $return ? "ok" : json_encode(array('error' => $db->errorInfo()));
	die;
}

if ($action == 'duplicate'){
	$return = $db->duplicate($table, $_POST, $error);
	show_result($return, $error);
	
	die;
}elseif ($action == 'load') {
	$grid = new EditableGrid();
	
	$result = $db->list($table);
	$result or fail('load','select failed'); //TODO: Show this properly
	add_columns_from_meta($result,$grid,$table);
	//var_dump($meta);fail;
	// send data to the browser
	$grid->renderJSON($result);
}
else {
	fail("Invalid action");
}


function show_result($result, $error){
	global $db;
	echo ($error === "" && $result) ? "ok" : json_encode(array('error' => $error !== ""?$error:$db->errorInfo()));
}


