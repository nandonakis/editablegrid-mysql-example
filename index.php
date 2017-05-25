<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<!--
/*
 * examples/mysql/index.html
 * 
 * This file is part of EditableGrid.
 * http://editablegrid.net
 *
 * Copyright (c) 2011 Webismymind SPRL
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://editablegrid.net/license
 */
-->

<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>EditableGrid Demo - Database Link</title>
		<link rel="stylesheet" href="css/style.css" type="text/css" media="screen">
		<link rel="stylesheet" href="css/responsive.css" type="text/css" media="screen">

        <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
		<link rel="stylesheet" href="css/font-awesome-4.1.0/css/font-awesome.min.css" type="text/css" media="screen">
        	</head>
	
	<body>
		<div id="wrap">
		<h1>EditableGrid Demo - Database Link</h1> 
		
			<!-- Feedback message zone -->
			<div id="message"></div>

            <div id="toolbar">
              <input type="text" id="filter" name="filter" placeholder="Filter :type any text here"  />
              <a id="showaddformbutton" class="button green"><i class="fa fa-plus"></i> Add new row</a>
              
              
                tables:
              <?php
              require_once('pdoDB.php'); 
              $tablename = isset($_GET['db_tablename'])?$_GET['db_tablename']:'country';
              $list_tables = $db->list_tables();
              
              ?>
              <select name="db_tablename" onchange="location='?db_tablename='+this.value;">
              <?php foreach($list_tables as $i => $v){
                echo '<option' .  ($v==$tablename?' selected':'') . '>'.  $v. '</option>';  
              }
              ?>
              </select>
              
            </div>
			<!-- Grid contents -->
			<div id="tablecontent"></div>
		
			<!-- Paginator control -->
			<div id="paginator"></div>
		</div>  
		
		<script src="js/editablegrid-2.1.0-b25.js"></script>   
		<script src="js/jquery-1.11.1.min.js" ></script>
        <!-- EditableGrid test if jQuery UI is present. If present, a datepicker is automatically used for date type -->
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
		<script src="js/demo.js" ></script>

		<script type="text/javascript">
		
            var datagrid = new DatabaseGrid('<?php echo $tablename; ?>');
			window.onload = function() { 

                // key typed in the filter field
                $("#filter").keyup(function() {
                    datagrid.editableGrid.filter( $(this).val());

                    // To filter on some columns, you can set an array of column index 
                    //datagrid.editableGrid.filter( $(this).val(), [0,3,5]);
                  });

                $("#showaddformbutton").click( function()  {
                  showAddForm();
                });
                $("#cancelbutton").click( function() {
                  showAddForm();
                });

                $("#addbutton").click(function() {
                  datagrid.addRow();
                });

        
			}; 
		</script>

        <!-- simple form, used to add a new row -->
        <div id="addform">
        
        <?php
            
            
            $cols = $db->get_table_columns($tablename);
             //var_dump($cols);
            foreach($cols as $k => $v){
                
            
                $name = $v['name'];
                if($name == 'id' || count($v['flags']) < 1)
                    continue;
                
              ?>

            <div class="row">
                <input type="text" id="<?php echo $name?>" name="<?php echo $name?>" placeholder="<?php echo $name?>" />
            </div>

            <?php
            
            }
            
            
            ?>
            
             

            <div class="row tright">
              <a id="addbutton" class="button green" ><i class="fa fa-save"></i> Apply</a>
              <a id="cancelbutton" class="button delete">Cancel</a>
            </div>
        </div>
        
	</body>

</html>
