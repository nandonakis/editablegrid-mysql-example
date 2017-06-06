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
	
	$db = new BackendDB($config,$profile);
	isset($db) or die("No DB");
	
	$error="";
	if($action == 'add'){
		$return = $db->add($table,$_POST,$error);
		$db->show_result($return,$error);
	}
	elseif ($action == 'update'){
		$return = $db->update($table, $_POST, isset($_POST['row'])?$_POST['row']:$_POST, $error);
		$db->show_result($return, $error);
	}
	elseif ($action == 'delete'){
		$return = $db->delete($table, isset($_POST['row'])?$_POST['row']:$_POST);
		$db->show_result($return,$error);
	}
	elseif ($action == 'duplicate'){
		$return = $db->duplicate($table, isset($_POST['row'])?$_POST['row']:$_POST, $error);
		$db->show_result($return, $error);
	}
	elseif ($action == 'load') {
		$grid = new EditableGrid();
		$result = $db->load($table);
		$result or fail('load','select failed'); //TODO: Show this properly
		$db->add_columns_from_meta($result,$grid,$table);
		$grid->renderJSON($result);
	}
	else {
		fail("Invalid action $action");
	}
