<?php	 

function debug()  {
	$stamp = date("Y-m-d H:i:s");
	$scalars = array();
	$vars = array();
	$args = func_get_args();
	$type = $args[1];
	foreach ($args as $var) {
		if (is_scalar($var)) {
			array_push($scalars,$var);
		}
		else {
			array_push($vars,$var);
		}
	}
	$line = implode(":",$scalars);
	file_put_contents('backend.log',"$stamp:$line\n",FILE_APPEND);
	
	if(0) {
		foreach ($vars as $var) {
			$line = print_r($var,1);
			file_put_contents('backend.log',"$stamp:$line\n",FILE_APPEND);
		}
	}
}

function fail($message='fail') {
	debug('fail',$message);
	die($message);
}

?>
