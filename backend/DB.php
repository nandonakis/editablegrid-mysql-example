<?php

class DB{
	//private $pdh;
	//private $db_type;
	//private $db_name;
	//private $db_host;
	//private $db_password;
	//private $db_user;
	//private $db_table;
	protected $config = array();
	
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
	
	public function build_cond($values){
		if(is_array($values)===false)
			$values = array('id' => $values);
		
		$conds = array();
		foreach($values as $k=>$v){
			$conds[]=sprintf("%s=%s", $k, $this->dbh->quote($v));
			
		}
		return join(" and ", $conds);
		
	}
	
	public function get($id, $table){
		
		$cond = $this->build_cond($id);
		
		debug('get cond', $cond, $cond);
		$query = sprintf("select * from %s where %s", $table, $cond);
		$rs = $this->dbh->query($query, PDO::FETCH_ASSOC);
		debug('get', $query, $rs);
		return $rs->fetch(PDO::FETCH_ASSOC);
	}
	
	
	
	
	public function get_next_id($table, $k){
		$query = sprintf("select max(%s)+1 from %s", $k, $table);
		$result = $this->dbh->query($query);
		debug('get_next_id',$query, $result);
		
		return $result->fetchColumn();
	}
	
	public function insert($tablename, $values, $id=""){
		
		$fields = array();
		$new_values = array();
		foreach($values as $k => $v){
			
			$fields[] = $this->quote_identifier($k);
			$new_values[] = $values[$k];
		}
		$query = sprintf("INSERT INTO %s.%s  (%s) VALUES (%s)", $this->config['db_schema'],$tablename, join(',', $fields), join(',',$new_values)); 

		//file_put_contents('update.log', $query ."\n");
		debug('insert',$query, 'ok');
		return $this->dbh->query($query);
		/* to simpify this method, other action should be added in its subclass,
		if the insert is ok
		
		if($this->dbh->query($query)){
			if($id === "")
				$id = $this->dbh->lastInsertId();
			
			return $this->get($id, $tablename);
		}
		return FALSE;
		*/
		
	}

	
	
	
	public function modify($table, $values, $id){
		
		
		
		
		$cond = $this->build_cond($id);
		$fields = array();
		foreach($values as $k => $v){
			$fields[] = sprintf("%s=%s", $k, $v);
		}
		
		$query = sprintf("UPDATE %s SET %s WHERE %s", $table, join(',',$fields), $cond );
	  $result = $this->dbh->query($query);
		debug('update',$query,$result);
		return $result;
	}
	
	public function delete($table, $id){
		
		$cond = $this->build_cond($id);
		
		//$id = $this->dbh->quote("".$id);
		$query = sprintf("DELETE FROM %s  WHERE %s", $table, $cond );
		$result = $this->dbh->query($query);
		debug('delete',$query,$result);
		return $result;
	}
	

	public function load ($table){
		$sql = "SELECT * FROM $table";
		return $this->dbh->query($sql);
	}
	
	
	public function errorInfo(){
		$err = $this->dbh->errorInfo();
		debug('errorInfo',$err[0],$err[1],$err[2]);
		return $err[2];
	}
}

