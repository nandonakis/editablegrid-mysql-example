<?php

class DB{

	protected $config = array();
	
	function __construct($config,$profile) {
		$params = array('db_type','db_host','db_name','db_user','db_password');
		$this->db_type = $config['db_type'];
		$this->profile = $profile;
		$string = sprintf('%s:host=%s;dbname=%s', $config['db_type'],$config['db_host'],$config['db_name']);
		if (isset($config['db_port'])) {
			$string .= ";port=".$config['db_port'];
		}
		$this->dbh = new PDO($string, $config['db_user'], $config['db_password']);
		if(isset($config['db_schema']) and $config['db_type'] == 'pgsql') {
			$schema = $config['db_schema'];
			$this->dbh->exec("SET search_path TO $schema");
		}
		else{
			$config['db_schema'] = $config['db_name'];
		}
		$this->config = $config;
		debug('__construct', $this->config['db_schema'], $this->config);
	}
	
	public function quote_value($value) {
		if(preg_match('/^(DEFAULT|NULL)$/',$value)) {
			return $value;
		}
		else {
			return $this->dbh->quote($value);
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
			fail("Unrecognised type $type");
		}
	}
	
	
	public function fetch_pairs($sql){
		if (!($res = $this->dbh->query($sql, PDO::FETCH_ASSOC))) {
			return FALSE;
		}
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
	
	public function build_cond($values){
		if(is_array($values)===false){
			$values = array('id' => $values);
		}
		$conds = array();
		foreach($values as $k=>$v){
			$k = $this->quote_identifier($k);
			$v = $this->quote_value($v); //TODO: don't do this for non-text fields
			$conds[]=sprintf("%s=%s", $k, $v);
		}
		return join(" and ", $conds);
	}
	
	public function query($sql, $args=array()){
		return $this->dbh->query($sql, PDO::FETCH_ASSOC);
	}
	
	public function get($id,$table){
		$cond = $this->build_cond($id);
		debug('get cond', $cond, $cond);
		$table =  $this->quote_identifier($table);
		$sql = sprintf("select * from %s where %s", $table, $cond);
		$result = $this->dbh->query($sql, PDO::FETCH_ASSOC);
		debug('get',$result,$sql);
		return $result->fetch(PDO::FETCH_ASSOC);
	}
	
	public function load ($table){
		$table =  $this->quote_identifier($table);
		$sql = "SELECT * FROM $table";
		$result = $this->dbh->query($sql);
		debug('db:load',$result,$sql);
		return $result;
	}
	
	public function insert($table, $record, $id=""){
		$fields = array();
		$values = array();
		$table =  $this->quote_identifier($table);
		foreach($record as $k => $v){
			$v = $this->quote_value($v);
			$k = $this->quote_identifier($k);
			$fields[] = $k;
			$values[] = $v;
		}
		
		$sql = sprintf("INSERT INTO %s.%s  (%s) VALUES (%s)", $this->config['db_schema'],$table, join(',', $fields), join(',',$values)); 
		$rows = $this->dbh->exec($sql);
		$id = $this->dbh->lastInsertId();
		debug('db:insert',$rows,"rows:$rows id:$id query:$sql");
		return $rows;
	}
	
	public function modify($table, $values, $id){
		$cond = $this->build_cond($id);
		$fields = array();
		
		foreach($values as $k => $v){
			$v = $this->quote_value($v); 
			$k = $this->quote_identifier($k);
			$fields[] = sprintf("%s=%s", $k, $v);

		}
		$table =  $this->quote_identifier($table);
		$sql = sprintf("UPDATE %s SET %s WHERE %s", $table, join(',',$fields), $cond );
		$rows = $this->dbh->exec($sql);
		debug('db:modify',$rows,"rows:$rows query:$sql");
		return $rows;
	}
	
	public function delete($table, $id){
		$cond = $this->build_cond($id);
		$table =  $this->quote_identifier($table);
		$sql = sprintf("DELETE FROM %s  WHERE %s", $table, $cond);
		$rows = $this->dbh->exec($sql);
		debug('db:delete',$sql,$rows);
		return $rows;
	}

}

