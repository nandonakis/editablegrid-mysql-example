<?php

class DBClass{
	//private $pdh;
	//private $db_type;
	//private $db_name;
	//private $db_host;
	//private $db_password;
	//private $db_user;
	//private $db_table;
	
	function __construct($config) {
		$params = array('db_type','db_host','db_name','db_user','db_password');
		//TODO: Add these back into the object
		//foreach isset check or die
		
		$this->db_type = $config['db_type'];
		$this->dbh = new PDO(sprintf('%s:host=%s;dbname=%s', $config['db_type'],$config['db_host'],$config['db_name']), $config['db_user'], $config['db_password']);
		if(isset($config['db_schema']) and $config['db_type'] == 'pgsql') {
			$schema = $config['db_schema'];
			$this->dbh->exec("SET search_path TO $schema");
		}
	}
	
	public function quote_identifier($field) {
		$type = $this->db_type; 
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
		$rs = $this->dbh->query(sprintf("select * from %s where id=%s", $table, $id), PDO::FETCH_ASSOC);
		return $rs->fetch(PDO::FETCH_ASSOC);
	}
	
	public function add(){
		global $_POST;
		$table = strip_tags($_POST['tablename']);
		//$table = 'demo';
		$cols = $this->get_table_columns($table);
		$fields = array();
		$values = array();
		foreach($cols as $k => $v) {
			if ($k == 'id') continue;
			$fields[] = $this->quote_identifier($k);
			$values[] = 'NULL';
		}
		$query = sprintf("INSERT INTO %s  (%s) VALUES (%s)", $table, join(',', $fields), join(',',$values)); 
		//file_put_contents('update.log', $query ."\n");
		debug('add',$query,'todo');
		if($this->dbh->query($query)){
			$id = $this->dbh->lastInsertId();
			return $this->get($id, $table);
		}
		return FALSE;
	}

	public function duplicate(){
		global $_POST;
		$table = strip_tags($_POST['table']);
		$cols = $this->get_table_columns($table);
		$fields = join(',',array_diff(array_keys($cols), ['id']));
		$id = $this->dbh->quote(strip_tags($_POST['id']));
		
		
		$query = sprintf("INSERT INTO %s (%s) SELECT %s FROM %s WHERE id=%s", $table, $fields, $fields, $table, $id );
		$result = $this->dbh->query($query);
		debug('duplicate',$query,$result);
		return $result;
	}

	
	
	public function update(){
		global $_POST;
		$table = $this->quote_identifier(strip_tags($_POST['tablename']));
		$field = $this->quote_identifier(strip_tags($_POST['colname']));
		$id = $this->dbh->quote(strip_tags($_POST['id']));
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
		}
		$query = sprintf("UPDATE %s SET %s=%s WHERE id = %s", $table, $field, $value, $id );
	  $result = $this->dbh->query($query);
		debug('update',$query,$result);
		return $result;
	}
	
	public function delete(){
		global $_POST;
		$table = strip_tags($_POST['tablename']);
		$id = $this->dbh->quote(strip_tags($_POST['id']));
		$query = sprintf("DELETE FROM %s  WHERE id = %s", $table, $id );
		$result = $this->dbh->query($query);
		debug('delete',$query,$result);
		return $result;
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

