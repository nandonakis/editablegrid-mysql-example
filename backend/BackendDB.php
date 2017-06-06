<?php

require_once "DB.php";
class BackendDB extends DB {

	function zz__construct($config) {
		$params = array('db_type','db_host','db_name','db_user','db_password');
		//TODO: Add these back into the object
		//foreach isset check or fail
		parent::__construct($config);
	}
	
	public function get_meta($table, $forced=FALSE) {
		$sql_dir = sprintf("%s/sql/%s", dirname(__FILE__), $this->db_type);
		$profile_dir = sprintf("%s/profiles/%s", dirname(__FILE__), $this->profile);
		$spec = sprintf("%s/%s.spec.tsv", $profile_dir, $table);
		
		if(!file_exists($spec) || $forced){
			$spec = sprintf("%s/%s.tsv", $profile_dir, $table);
			if(!file_exists($spec)|| $forced) {
				### create it here
				$query=file_get_contents("$sql_dir/meta.sql");
				if(!$query){
					fail('get_meta',"$sql_dir/meta.sql not found");
				}
				$query = sprintf($query, $table, $this->config['db_schema']);
				debug('get_meta','debug',$query);
				$result = $this->dbh->query($query);
				$rows = $result->fetchAll(PDO::FETCH_ASSOC);
				debug('get_meta',"result of rows:",print_r($rows,TRUE));
				$data = array();
				$i = 0;
				file_put_contents($spec, "");
				foreach($rows as $k => $v){
					$this->print_meta_row($v,$i++, $spec);
				}
			}
		}
		## load and parse it, cache it??
		return $this->parse_meta_file($spec);
	}
	
	public function is_primary_key($key){
		//if($row['column_key'] && strpos('PRI',$row['column_key']) !== false){
		if (isset($key) && strpos($key, 'PRI') !== false)
			return TRUE;
		return FALSE;
	}
	
	public function print_meta_row($row,$count,$file){
		$h=array();
		$cols= array();
		//if($row['column_key'] && strpos('PRI',$row['column_key']) !== false){
		if($this->is_primary_key($row['column_key'])){
			$row['editable'] = 0;
			debug('print_meta_row', $row['editable']);
		}
		foreach($row as $k => $v){
			$h[] =$k;
			// modify grid_type
			if($k === 'type'){
				$v = get_col_type($v, $cols[2]);
			}
			$cols[]  = $v;
		}
		if($count <1)	{
			file_put_contents($file, join("\t", $h) . "\n",FILE_APPEND);
		}
		file_put_contents($file, join("\t", $cols) . "\n",FILE_APPEND);
	}
	
	public function parse_meta_file($file, $sep="\t"){
		$csv = array();
		$handle = fopen($file, "r");
		$c=0;
		while ($data = fgetcsv($handle, 99999, $sep)){
			if($data !== FALSE)
				$csv[$c++] = $data;
		}
		//return $rows ;
		debug('parse_meta_file', $file, $csv);
		$header = array_shift($csv);
		//var_dump($header);
		array_walk($csv, function(&$a) use ($header) {
			//var_dump($a);
			$a = array_combine($header, $a);
		});
		// now, use name as key
		$rows= array();
		foreach($csv as $k => $v){
			$rows[$v['field']] = $v;
		}
		return $rows;
	}
	
	function add_columns_from_meta($result, $grid, $table){
		//$meta = $this->get_table_columns($table);
		$meta = $this->get_meta($table);
		// var_dump($meta);die;
		//$grid->addColumn('id', 'ID', 'integer', NULL, false); 
		foreach($meta as $name => $v){
			$editable = $v['editable']; $name === 'id' and $editable = false;
			//$type = get_col_type($v["native_type"],$name);
			$type = $v['type'];
			if($type === false) {
					continue;
			}
			$grid->addColumn($name,$v['label'],$type,NULL,$editable);
		}
		$grid->addColumn('action', 'Action', 'html', NULL, false, 'id');
	}  
	
	
	public function get_col_type($type,$name=''){
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
	
	
	public function get_primary_values($table, $values){
		$cols  = $this->get_meta($table);
		$primary = $this->get_primary($cols);
		$keys = array();
		foreach($primary as $k => $v){
			$keys[$k] = $values[$k];
		}
		return $keys;
	}
	
	//auto_increment
	public function get_primary($cols){
		$primary = array_filter($cols, function($a){return $this->is_primary_key($a['column_key']);});
		return $primary;
	}
	
	public function is_auto_primary($row){
		return isset($row['extra']) && preg_match('/(nextval|auto_increment)/',$row['extra']);
	}
	
	public function get_next_id($table, $k){
		$table =  $this->quote_identifier($table);
		$query = sprintf("select max(%s)+1 from %s", $k, $table);
		$result = $this->dbh->query($query);
		debug('get_next_id',$query, $result);
		return $result->fetchColumn();
	}
	
	public function add($table, $values, &$info=""){
		$cols = $this->get_meta($table);
		$primary = $this->get_primary($cols);
		if(count($primary)>1){
			$info = "Multi-primary keys";
			return FALSE;
		}
		$values = array();
		$k = array_keys($primary)[0];
		$id = "";
		
		if(isset($values[$k])){
			$valuesvalues[$k] = $values[$k]; //????
		}
		else{
			if($this->is_auto_primary($primary[$k]) === false){
				if($primary[$k]['type'] === 'integer'){
					$id = $this->get_next_id($table, $k);
					$values[$k] = $id;
				}
				else{
					$info = "Primary not integer and it is null";
					return FALSE;
				}
			}
			elseif($this->config['db_type'] !== 'mysql'){
				$values[$k] = 'DEFAULT';
			}
		}
		debug('add', $k . ' vs ' . $values[$k] . 'v:' . $primary[$k]['extra'], $k);
		// get default
		foreach($cols as $k2 => $c){
			if($k2 == $k) {
				continue;
			}
			$col = $cols[$k2];
			
			if(isset($values[$k2])){
				$values[$k2] = strip_tags($values[$k2]);
			}
			else{
				if(isset($col['extra']) && $col['extra'] !== ""){
					$values[$k2] = $col['extra'];
				}
				else{
					$values[$k2] = 'DEFAULT';
				}
			}
		}
		debug('add', print_r($values, TRUE), $values);
		return $this->insert($table,$values,$id);
	}
	
	public function update($table, $values, $row, &$info){
		$colname = $values['colname'];
		$field = strip_tags($colname);
		//$id = $this->quote_value(strip_tags($_POST['id']));
		$key_cols = $this->get_primary_values($table, $row);
		$length = strlen($values['newvalue']);
		$value = strip_tags($values['newvalue']);
		$coltype = strip_tags($values['coltype']);
		
		$cols = $this->get_meta($table);
		
		if($coltype == 'date'){
			if ($value === "")
				$value = NULL;
			else{
				$value = date_parse_from_format('d/m/Y', $value);
				//date_info = date_parse_from_format('YY-mm-dd', $value);
				$value = "{$value['year']}-{$value['month']}-{$value['day']}";
				//$value = $this->quote_value($value);
			}
		}
		elseif($coltype == 'string' && $this->config['db_type'] === 'pgsql'){
			// move on top so that it can be passed into modify
			//$cols = $this->get_meta($table);
			if(array_key_exists($colname, $cols)){
				$max_length = $cols[$colname]['character_maximum_length'];
				if($max_length && $length > $max_length){
					$info = "Value is too long!";
					return FALSE;
				}
			}
			else{
				$info = "Field does not exist!";
				return FALSE;
			}
		}
		debug('update db_cols', $colname . ':' .  $value);
		$result = parent::modify($table, array($colname => $value), $key_cols);
		debug('modify',$field,$result);
		return $result;
	}
	public function  delete($table,$row){
		$key_cols = $this->get_primary_values($table, $row);
		$rows = parent::delete($table,$key_cols);
		return $rows;
	}
	
	public function duplicate($table, $row, &$info){
		$key_cols = $this->get_primary_values($table, $row);
		$row = $this->get($key_cols,$table);
		
		if($row){
			$cols = $this->get_meta($table);
			//debug('duplicate',$query,print_r($row, TRUE));
	
			$primary = $this->get_primary($cols);
			// update default field
			foreach($row as $k => $v){
				if (array_key_exists($k, $primary)){
					continue;
				}
				$col = $cols[$k];
				if(!isset($v)){
					// have default value
					if(isset($col['extra']) && $col['extra'] !== ""){
						//$row[$k]= $this->quote_value($col['extra']);
						$row[$k]= $col['extra'];
					}
					else{
						if($col['db_type'] ==='date' || $col['db_type'] === 'timestamp') {
							$row[$k]='NULL';
						}
						else {
							$row[$k]='';
						}
					}
				}
				else{
					$row[$k]= $v;
				}
			}
			// do a bit more check/amending before let it go
			$id = "";
			foreach($primary as $k => $v){
				if($this->is_auto_primary($v)) {
					$row[$k] = 'DEFAULT';
				}
				else if ($v['type'] === 'integer'){
					$id = $this->get_next_id($table, $k);
					$row[$k] = $id;
				}
			}
			return $this->insert($table,$row,$id);
		}
		$info = "Record not found!";
		return $row;
	}
	
	
	function show_result($result,$error) {
		
		if($error == "" && $result) {
			print 'ok';
			die;
		}
	
		elseif ($error ==='') {
			$err = $this->dbh->errorInfo();
			$error = $err[2];	
		}
		print json_encode(array("success" => false,"error" => $error));
		die;
	}

}
