<?php     


/*
 * examples/mysql/backend.php
 * 
 * This file is part of EditableGrid.
 * http://editablegrid.net
 *
 * Copyright (c) 2011 Webismymind SPRL
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://editablegrid.net/license
 */
                              


/**
 * This script loads data from the database and returns it to the js
 *
 */
       
//require_once('config.php');      
require_once('EditableGrid.php');            
require_once('pdoDB.php'); 

function get_js_type($type){
    
    switch($type){
        case 'STRING';
        case 'VAR_STRING';
            return 'string';
            break;
        case 'TINY':
            return 'boolean';
            break;
        case "NEWDECIMAL":
            return 'float';
            break;
        case "DATE":
            return 'date';
            break;
            
        default:
            return 'interger';
           
        
    }
    
    
}
function add_columns_from_meta($result, $grid){
    global $db;
    
    $meta = $db->get_column_meta($result);
    //var_dump($meta);die;
    
    //$grid->addColumn('id', 'ID', 'integer', NULL, false); 
    foreach($meta as $k => $v){
        //if($v['name'] == 'id')
        //    continue;
        $name = $v['name'];
        $pos = strpos($name, 'id_');
        if($pos !== false){
        //if($name == 'id_country'){
            
            $instr = substr($name, 3);
            $grid->addColumn($name, $instr, 'string', $db->fetch_pairs('SELECT id, name FROM ' . $instr),true );  
        }else{
            
                $grid->addColumn($name, $name, get_js_type($v['native_type']));
        }
        
        
    }
    $grid->addColumn('action', 'Action', 'html', NULL, false, 'id');  
}  


$mydb_tablename = (isset($_GET['db_tablename'])) ? stripslashes($_GET['db_tablename']) : 'demo';
$action = (isset($_GET['action'])) ? stripslashes($_GET['action']) : 'list';

if($action == 'add'){
    
    $return = $db->add($mydb_tablename);
    //var_dump($return);
    
    
    
    
    
    if($return){
        $json = json_encode($return);
        //file_put_contents('update.log', $json ."\n");
        echo $json;
        
    }else{
        
        echo "error";  
    }
    
    //echo $return ? $data . $return : "error";  
    die;
}
if ($action == 'update'){
    $return = $db->update($mydb_tablename);
    echo $return ? "ok" : "error";  
    die;
}

if ($action == 'delete'){
    $return = $db->delete($mydb_tablename);
    echo $return ? "ok" : "error";  
    die;
}
if ($action == 'duplicate'){
    $return = $db->duplicate($mydb_tablename);
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

                                                                       
$result = $db->query('SELECT * FROM '.$mydb_tablename );


add_columns_from_meta($result, $grid);

//var_dump($meta);die;
//die;
// send data to the browser
$grid->renderJSON($result);

