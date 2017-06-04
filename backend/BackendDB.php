<?php
require_once "DB.php";
class BackendDB extends DB{

function __construct($config) {
	$params = array('db_type','db_host','db_name','db_user','db_password');
	//TODO: Add these back into the object
	//foreach isset check or fail
	parent::__construct($config);
	
}

public function get_meta($table, $forced=FALSE) {
	$sql_dir = sprintf("%s/sql/%s", dirname(__FILE__), $this->config['db_type'] == 'mysql'? $this->config['db_type']:'postgres');

	$profile_dir = sprintf("%s/profiles/%s", dirname(__FILE__), $this->config['db_type'] == 'mysql'? $this->config['db_type']:'postgres');
	$spec = sprintf("%s/%s.spec.tsv", $profile_dir, $table);
	//debug('get_meta','here');
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

public function print_meta_row($row, $count,$file){
	$h=array();
	$cols= array();
	if($row['column_key'] && strpos('PRI',$row['column_key']) !== false){
		$row['editable'] = 0;
		debug('print_meta_row', $row['editable']);
	}
	foreach($row as $k => $v){
		$h[] =$k;
		// modify grid_type
		if($k === 'type'){
			$v =  get_col_type($v, $cols[2]);
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
		
		$primary = array_filter($cols, function($a){return ($a['column_key'] && strpos($a['column_key'],"PRI")>=0);});
		return $primary;
	}
	
	public function is_auto_primary($row){
		//$primary[$k]['extra'] === "" || 
		return isset($row['extra']) && preg_match('/(nextval|auto_increment)/',$row['extra']);
		
		
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
			$valuesvalues[$k] = $values[$k];
		}else{
			if($this->is_auto_primary($primary[$k]) === false){
				if($primary[$k]['type'] === 'integer'){
					$id = $this->get_next_id($table, $k);
					$values[$k] = $id;
				}else{
					
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
			
			$col = $cols[$k2];
			
			if(isset($values[$k2])){
				$values[$k2] = $this->dbh->quote(strip_tags($values[$k2]));;
			}else{
				if(isset($col['extra']) && $col['extra'] !== "")
					$values[$k2] = $this->dbh->quote($col['extra']);
				else
					$values[$k2] = 'NULL';
				
			}
			
		}
		
		debug('add', print_r($values, TRUE), $values);
		
		
		
		return $this->insert($table, $values, $id);
		
	}
	
	public function get_next_id($table, $k){
		$query = sprintf("select max(%s)+1 from %s", $k, $table);
		$result = $this->dbh->query($query);
		debug('get_next_id',$query, $result);
		
		return $result->fetchColumn();
	}



public function update($table, $values, $row, &$info){
		$colname = $values['colname'];
		$field = $this->quote_identifier(strip_tags($colname));
		//$id = $this->dbh->quote(strip_tags($_POST['id']));
		$key_cols = $this->get_primary_values($table, $row);
		
		$val_len = strlen($values['newvalue']);
		$value = $this->dbh->quote(strip_tags($values['newvalue']));
		$coltype = strip_tags($values['coltype']);
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
				
			
				$db_len = $cols[$colname]['character_maximum_length'];
				if($db_len && $val_len > $db_len){
					$info = "Value is too long!";
					return FALSE;
				}
			}else{
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
	$result = parent::delete($table, $key_cols);
	$rows = $result->rowCount(); // TODO: Make query return number of rows deleted, 0 or undef for error
	return $rows;
}




public function duplicate($table, $row, &$info){
		
		$key_cols = $this->get_primary_values($table, $row);
		$row = $this->get($key_cols, $table);
		
		
		
		
		if($row){
			
		
			$cols = $this->get_meta($table);
			//debug('duplicate',$query,print_r($row, TRUE));
			
			$primary = $this->get_primary($cols);
			// update default field
			foreach($row as $k => $v){
				if (array_key_exists($k, $primary))
					continue;
				
				
				$col = $cols[$k];
				if(!isset($v)){
					// have default value
					if(isset($col['extra']) && $col['extra'] !== ""){
						$row[$k]= $this->dbh->quote($col['extra']);
					}else{
						if($col['db_type'] ==='date' || $col['db_type'] === 'timestamp')
							$row["$k"]='NULL';
						else
							$row["$k"]=$this->dbh->quote('');
						
					}
					
				}
				else
					$row[$k]= $this->dbh->quote($v );
				
				
			}
			
			// do a bit more check/amending before let it go
			$id = "";
			foreach($primary as $k => $v){
				if($this->is_auto_primary($v))
					$row[$k] = 'DEFAULT';
				else if ($v['type'] === 'integer'){
					
					$id = $this->get_next_id($table, $k);
					$row[$k] = $id;
				}
					
					
				
			}
		
		
			return $this->insert($table, $row, $id);
		}
		
		$info = "Record not found!";
		return $row;
}


public function errorInfo(){
	$err = $this->dbh->errorInfo();
	debug('errorInfo',$err[0],$err[1],$err[2]);
	return $err[2];
}

}
