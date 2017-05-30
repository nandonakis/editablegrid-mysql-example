<?php

function debug($op,$query,$sth)  {
	$result = $sth ? 'ok' : 'error';
	$stamp = date("Y-m-d H:i:s");
	file_put_contents('backend.log', "$stamp:$result:$query\n",FILE_APPEND);
}

function get_col_type($type,$name=''){
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
		//die ("Unrecognised type $type");
		debug('get_col_type', $type,'Unrecognised type');
		return false;
	}
	return 'string';
}