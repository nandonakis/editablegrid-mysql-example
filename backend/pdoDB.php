<?php

class DBClass{
	//private $pdh;
	//private $db_type;
	//private $db_name;
	//private $db_host;
	//private $db_password;
	//private $db_user;
	//private $db_table;
	
	private $config = array();
	
	function __construct($config) {
		$params = array('db_type','db_host','db_name','db_user','db_password');
		//TODO: Add these back into the object
		//foreach isset check or die
		
		$this->db_type = $config['db_type'];
		$this->dbh = new PDO(sprintf('%s:host=%s;dbname=%s', $config['db_type'],$config['db_host'],$config['db_name']), $config['db_user'], $config['db_password']);
		if(isset($config['db_schema']) and $config['db_type'] == 'pgsql') {
			$schema = $config['db_schema'];
			$this->dbh->exec("SET search_path TO $schema");
			
			
		}else{
			
			$config['db_schema'] = $config['db_name'];
		}
		
		$this->config = $config;
		
		debug('__construct', $this->config['db_schema'], $this->config);
	}
	
	public function get_meta($table, $forced=FALSE){
			$dir = sprintf("%s/profiles/%s", dirname(__FILE__), $this->config['db_type'] == 'mysql'?$this->config['db_type']:'postgres');
			
			$file = sprintf("%s/%s.spec.tsv", $dir, $table);
			
			if(!file_exists($file) || $forced){
				$file = sprintf("%s/%s.tsv", $dir, $table);
				if(!file_exists($file)|| $forced){
					### create it here
					$query=file_get_contents("$dir/meta.sql");
					
					if(!$query){
						debug('get_meta', "$dir/meta.sql not found", "OK");
						die;
						
					}
					
					/*
					
					$query = <<<EOF
					select  tab_columns.table_schema as "schema",tab_columns.table_name as "table", tab_columns.column_name as field, tab_columns.column_name as label, tab_columns.ordinal_position as "order", tab_columns.data_type as type, tab_columns.character_maximum_length, tab_constraints.constraint_type as column_key, tab_columns.column_default as extra,  1 as display, 1 as editable from information_schema.columns AS tab_columns 
	LEFT OUTER JOIN 
	information_schema.constraint_column_usage AS col_constraints 
	ON tab_columns.table_name = col_constraints.table_name AND 
	tab_columns.column_name = col_constraints.column_name 
	LEFT OUTER JOIN 
	information_schema.table_constraints AS tab_constraints 
	ON tab_constraints.constraint_name = col_constraints.constraint_name
	where tab_columns.table_name = 'demo' order by ordinal_position
	EOF;

	*/
				//var_dump($this->config);
				
				$query = sprintf($query, $table, $this->config['db_schema']);

					
				debug('get_meta', $query, $query);
				
				$result = $this->dbh->query($query);
				$rows= array();
				
				$rows = $result->fetchAll(PDO::FETCH_ASSOC);
				
				## dump to file
				#$rows
				
				debug('get_meta', print_r($rows, TRUE), $rows);
				
				$data = array();
				$i = 0;
				file_put_contents($file, "");
				foreach($rows as $k => $v){
					$this->print_meta_row($v,$i++, $file);
					
				}
				
			}
		}
		
		## load and parse it, cache it??
		
		
		
		return $this->parse_meta_file($file);
		
	}
	
	public function print_meta_row($row, $count,$file){
		$h=array();
		$cols= array();
		foreach($row as $k => $v){
			$h[] =$k;
			
			// modify grid_type
			if($k === 'type')
				$v =  get_col_type($v, $cols[2]);
			
		
			$cols[]  = $v;
			
		}
		if($count <1)			
			file_put_contents($file, join("\t", $h) . "\n",FILE_APPEND);
		
		
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
	
	public function quote_identifier($field) {
		$type = $this->config['db_type']; 
		if ($type == 'mysql') {
			return "`".str_replace("`","``",$field)."`";
		}
		elseif($type == 'pgsql') {
			return '"'.$field.'"';
		}
		else {
			die("Unrecognised type $type");
		}
	}
	public function clean_arg($arg,$type) {
		if ($type == 'value') {
			return $this->dbh->quote(strip_tags($_POST[$arg]));
		}
		elseif ($type == 'field') {
			return $this->quote_identifier(strip_tags($_POST[$arg]));
		}
		else {
			die("Invalid args");
		}
	}

	

	public function query($query, $args=array()){
		return $this->dbh->query($query, PDO::FETCH_ASSOC);
	}
	
	public function fetch_pairs($query){
		if (!($res = $this->dbh->query($query, PDO::FETCH_ASSOC))) return FALSE;
		$rows = array();
		while ($row = $res->fetch()) {
			$first = true;
			$key = $value = null;
			foreach ($row as $val) {
				if ($first) { $key = $val; $first = false; }
				else { $value = $val; break; } 
			}
			$rows[$key] = $value;
		}
		return $rows;
	}
	
	public function get_column_meta($table){
		$rs = $this->dbh->query(sprintf("select * from %s limit 1", $table));
		
		$meta = array();
		for($i=0; $i< $rs->columnCount(); $i++){
			$meta[] = $rs->getColumnMeta($i);
		}
		// for compatible with all other 
		//$rs  = $this->dbh->query("desc $table", PDO::FETCH_ASSOC);
		//return $rs->fetchAll();
		return $meta;
		
	}
	public function get_table_columns($table){
		$meta = $this->get_column_meta($table);
		$cols = array();
		foreach($meta as $i => $v){
			//$k = $v["Field"];
			//$t= $v["Type"];
			$k = $v["name"];
			$cols[$k] = $v; 
		}
		return $cols;
	}
	
	public function get($id, $table){
		$query = sprintf("select * from %s where id=%s", $table, $id);
		$rs = $this->dbh->query($query, PDO::FETCH_ASSOC);
		debug('get', $query, $rs);
		return $rs->fetch(PDO::FETCH_ASSOC);
	}
	
	//auto_increment
	public function get_primary($cols){
		
		$primary = array_filter($cols, function($a){return ($a['column_key'] && strpos($a['column_key'],"PRI")>=0);});
		return $primary;
	}
	
	public function is_auto_primary($row){
		//$primary[$k]['extra'] === "" || 
		return isset($row['extra']) && preg_match('/(nextval|auto_increment)/',$row['extra']);
		
		
	}
	public function add($table, &$info=""){
		global $_POST;

		//$tablename = strip_tags($_POST['tablename']);
		//$tablename = 'demo';
		$cols = $this->get_meta($table);
		
		$primary = $this->get_primary($cols);
		
		if(count($primary)>1){
			$info = "Multi-primary keys";
			return FALSE;
		}
		$values = array();
		$k = array_keys($primary)[0];
		if(isset($_POST[$k])){
			$valuesvalues[$k] = $_POST[$k];
		}else{
			if($this->is_auto_primary($primary[$k]) === false){
				if($primary[$k]['type'] === 'integer')
					$values[$k] = $this->get_next_id($table, $k);
				else{
					
					$info = "Primary not integer and it is null";
					return FALSE;
				}
				
				
			}elseif($this->config['db_type'] !== 'mysql'){
				$values[$k] = 'DEFAULT';
			}
		}
		debug('add', $k . ' vs ' . $values[$k] . 'v:' . $primary[$k]['extra'], $k);
		// get default
		foreach($cols as $k2 => $c){
			if($k2 == $k)
				continue;
				
				
			if(isset($_POST[$k2])){
				$values[$k2] = $this->dbh->quote(strip_tags($_POST[$k2]));;
			}else{
				
				$values[$k2] = 'NULL';
				
			}
			
		}
		
		debug('add', print_r($values, TRUE), $values);
		
		
		
		return $this->insert($table, $values, $cols, $info, $values[$k]);
		
	}
	
	public function get_next_id($table, $k){
		$query = sprintf("select max(%s)+1 from %s", $k, $table);
		$result = $this->dbh->query($query);
		debug('get_next_id',$query, $result);
		
		return $result->fetchColumn();
	}
	
	public function insert($tablename, $values, $cols=FALSE, &$info="", $id=""){
		
			
		
			
		//$cols = $this->get_table_columns($tablename);
		
		
		
		//debug('insert cols',print_r($cols,TRUE),'ok');
		
		$fields = array();
		$new_values = array();
		foreach($cols as $k => $v){
			// just simple ignore 'id', if it is primray key
			//if(array_key_exists($k,$excl_cols))
			//if($cols[$k]['column_key'] == 'PRIMARY KEY')
			//	continue;
			
			
			$fields[] = $this->quote_identifier($k);
			
			if(array_key_exists($k, $values)){
			   
				$new_values[] = $values[$k];//$this->dbh->quote(strip_tags($values[$k]));
			}else{
				$new_values[] = 'NULL';
			}
		}
		$query = sprintf("INSERT INTO %s.%s  (%s) VALUES (%s)", $this->config['db_schema'],$tablename, join(',', $fields), join(',',$new_values)); 

		//file_put_contents('update.log', $query ."\n");
		debug('insert',$query, 'ok');
		if($this->dbh->query($query)){
			if($id === "")
				$id = $this->dbh->lastInsertId();
			
			return $this->get($id, $tablename);
		}
		return FALSE;
	}

	
	
	
	public function update($table, &$info=''){
		global $_POST;
		//$table = $this->quote_identifier(strip_tags($_POST['tablename']));
		
		$colname = $_POST['colname'];
		$field = $this->quote_identifier(strip_tags($colname));
		$id = $this->dbh->quote(strip_tags($_POST['id']));
		$val_len = strlen($_POST['newvalue']);
		$value = $this->dbh->quote(strip_tags($_POST['newvalue']));
		$coltype = strip_tags($_POST['coltype']);
		if($coltype == 'date'){
			if ($value === "")
				$value = NULL;
			else{
				$date_info = date_parse_from_format('d/m/Y', $value);
				$value = "{$date_info['year']}-{$date_info['month']}-{$date_info['day']}";
				$value = $this->dbh->quote($value);
			}
		}elseif($coltype == 'string' && $this->config['db_type'] === 'pgsql'){
			$cols = $this->get_meta($table);
			if(array_key_exists($colname, $cols)){
				debug('update db_cols', $colname, $cols);
			
				$db_len = $cols[$colname]['character_maximum_length'];
				if($val_len > $db_len){
					$info = "Value is too long!";
					return FALSE;
				}
			}else{
				$info = "Field does not exist!";
				return FALSE;
			}
			
		}
		$query = sprintf("UPDATE %s SET %s=%s WHERE id = %s", $table, $field, $value, $id );
	  $result = $this->dbh->query($query);
		debug('update',$query,$result);
		return $result;
	}
	
	public function delete($table){
		global $_POST;
		//$table = strip_tags($_POST['tablename']);
		$id = $this->dbh->quote(strip_tags($_POST['id']));
		$query = sprintf("DELETE FROM %s  WHERE id = %s", $table, $id );
		$result = $this->dbh->query($query);
		debug('delete',$query,$result);
		return $result;
	}
	

	public function duplicate($table, &$info=""){
		global $_POST;
		//$tablename = strip_tags($_POST['table']);
		$cols = $this->get_meta($table);
		//$fields = join(',',array_diff(array_keys($cols), ['id']));
		$id = $this->dbh->quote(strip_tags($_POST['id']));
		
		
		$query = sprintf("SELECT * FROM %s WHERE id=%s", $table,  $id );
		$result = $this->dbh->query($query);
		if($result === FALSE){
			$info = "$id could not be found";
			return $result;
		}
			
		
		$row = $result->fetch(PDO::FETCH_ASSOC);
		debug('duplicate',$query,print_r($row, TRUE));
		
		
		// update default field
		foreach($row as $k => $v){
			if(!isset($row[$k])){
					if($cols[$k]['db_type'] ==='date' || $cols[$k]['db_type'] === 'timestamp')
						$row[$k]='NULL';
					else
						$row[$k]=$this->dbh->quote($k . ':' . $cols[$k]['db_type']);
			}
			else
				$row[$k]= $this->dbh->quote($row[$k]);
			
			
		}
		
		// do a bit more check/amending before let it go
		$primary = $this->get_primary($cols);
		foreach($primary as $k => $v){
			if($this->is_auto_primary($v))
				$row[$k] = 'DEFAULT';
			else if ($v['type'] === 'integer')
				$row[$k] = $this->get_next_id($table, $k);
				
			
		}
		
		
		return $this->insert($table, $row, $cols, $info);
	}

	public function list($table){
		$sql = "SELECT * FROM $table";
		return $this->dbh->query($sql);
	}
	
	public function list_tables(){
		try {   
				$tableList = array();
				$result = $this->dbh->query("SHOW TABLES");
				while ($row = $result->fetch(PDO::FETCH_NUM)) {
					$tableList[] = $row[0];
				}
		}
		catch (PDOException $e) {
			echo $e->getMessage();
		}
		return $tableList;
	}
	public function errorInfo(){
		$err = $this->dbh->errorInfo();
		debug('errorInfo',$err[0],$err[1],$err[2]);
		return $err[2];
	}
}

