<?php

require_once('config.php'); 
class DBClass{
    private $pdh;
    private $db_type;
    private $db_name;
    private $db_host;
    private $db_password;
    private $db_user;
    private $db_table;
    
    function __construct($db_type='mysql',$host='localhost',$db='demo',$user='demo',$pw='demo')
	{
		$this->db_type = $db_type;
		$this->db_name = $db;
		$this->db_host = $host;
		$this->db_password = $pw;
		$this->db_user = $user;
        
        $this->init_db();
	}
    private function init_db(){
        $this->dbh = new PDO(sprintf('%s:host=%s;dbname=%s', $this->db_type, $this->db_host, $this->db_name ), $this->db_user, $this->db_password);
        
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
    
    public function get_column_meta($rs){
        $meta = array();
        
        for($i=0; $i< $rs->columnCount()-1; $i++){
            $meta[] = $rs->getColumnMeta($i);
            
            
        }
    
        return $meta;
        
    }
    public function get_table_columns($tablename){
        
        $rs = $this->dbh->query(sprintf("select * from %s limit 0", $tablename));
    
        $meta = $this->get_column_meta($rs);
        $cols = array();
        foreach($meta as $i => $v){
            $k = $v['name'];
            $t= $v['native_type'];
            $cols[$k] = $v; 
            
        }
        return $cols;
    }
    
    public function add(){
        global $_POST;
        $tablename = strip_tags($_POST['tablename']);
        $cols = $this->get_table_columns($tablename);
        $fields = array();
        $values = array();
        foreach($cols as $k => $v){
            if(array_key_exists($k, $_POST)){
               $fields[] = $k;
                $values[] = $this->dbh->quote(strip_tags($_POST[$k]));
            }
            
        }
        $query = sprintf("INSERT INTO %s  (%s) VALUES (%s)", $tablename, join(',', $fields), join(',',$values)); 
        file_put_contents('update.log', $query ."\n");
        return $this->dbh->query($query);
    }
    
    public function update(){
        global $_POST;
        $tablename = strip_tags($_POST['tablename']);
        $id = $this->dbh->quote(strip_tags($_POST['id']));
        $field = strip_tags($_POST['colname']);
        $value = $this->dbh->quote(strip_tags($_POST['newvalue']));
        $query = sprintf("UPDATE %s SET %s=%s WHERE id = %s", $tablename, $field, $value, $id );
        //file_put_contents('update.log', $query);
        
        return $this->dbh->query($query);
    }
    
    public function delete(){
        global $_POST;
        $tablename = strip_tags($_POST['tablename']);
        $id = $this->dbh->quote(strip_tags($_POST['id']));
        $query = sprintf("DELETE FROM %s  WHERE id = %s", $tablename, $id );
        //file_put_contents('update.log', $query ."\n");
        return $this->dbh->query($query);
    }
    
    public function duplicate(){
        global $_POST;
        $tablename = strip_tags($_POST['tablename']);
        $cols = $this->get_table_columns($tablename);
        
        $fields = join(',',array_diff(array_keys($cols), ['id']));
        
        
        $id = $this->dbh->quote(strip_tags($_POST['id']));
        $query = sprintf("INSERT INTO %s (%s) SELECT %s FROM %s WHERE id=%s", $tablename, $fields, $fields, $tablename, $id );
        file_put_contents('update.log', $query ."\n");
        $rs = $this->dbh->query($query);
        
        return $rs;
        
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
}

$db = new DBClass('mysql', $config['db_host'],$config['db_name'], $config['db_user'], $config['db_password']);
