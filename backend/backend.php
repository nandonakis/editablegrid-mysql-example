<?php	 
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('db_utils.php');

$action = (isset($_GET['action'])) ? stripslashes($_GET['action']) : fail ("no action");
$table = (isset($_GET['table'])) ? stripslashes($_GET['table']) : fail ("No table");
$profile = (isset($_GET['profile'])) ? stripslashes($_GET['profile']) : fail("No profile");

isset($_POST['id']) and $id = stripslashes($_POST['id']);
//$id = $db->dbh->quote(strip_tags($_POST['id']));

require_once("profiles/$profile/config.php");
isset($config) or fail("Invalid config");
require_once('EditableGrid.php');
require_once('pdoDB.php');

$db = new DBClass($config);
if(!isset($db)) {
	debug("backend.php",'fail',"No DB");
	echo json_encode(array('error' => "NO DB"));
}

if ($action == 'delete'){
	isset($_POST['tablename']) and $table = stripslashes($_POST['tablename']);
	$rows = $db->delete($table,$id);
	debug('delete',$rows,$query);
	echo $rows ? "ok" : json_encode(array('error' => $db->errorInfo()));
}

elseif($action == 'add'){
	$return = $db->add($table);
	if($return){
		$json = json_encode($return);
		echo $json;
	}
  else{
		echo json_encode(array('error' => $db->errorInfo()));
	}
}

elseif ($action == 'update'){
	$return = $db->update($table);
	echo $return ? "ok" : json_encode(array('error' => $db->errorInfo()));
}


elseif ($action == 'duplicate'){
	$return = $db->duplicate($table);
	echo $return ? "ok" : json_encode(array('error' => $db->errorInfo()));
	
}
elseif ($action == 'load') {
	$grid = new EditableGrid();
	$sql = "SELECT * FROM $table";
	$result = $db->query($sql);
	$result or fail('load','select failed'); //TODO: Show this properly
	add_columns_from_meta($result,$grid,$table);
	//var_dump($meta);fail;
	// send data to the browser
	$grid->renderJSON($result);
}
else {
	fail("Invalid action");
}
