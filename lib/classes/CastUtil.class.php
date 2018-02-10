<?php

class CastUtil {
 
 	static function getType($var) {
 	 	$out='NULL';
 	 	if (is_array($var)) {
 	 	 	return 'Array';
 	 	}
 	 	if (is_int($var)) {
 	 	 	return 'Integer';
 	 	}
 	 	if (is_string($var)) {
 	 	 	return 'String';
 	 	}
 	 	if (is_object($var)) {
 	 	 	return 'Object';
 	 	}
 	 	
 	 	return $out;
 	 
 	}
 
 	static function isNullOrEmpty($mixed) {
 	 
 	 	$type=CastUtil::getType($mixed);
 	 	if (is_null($mixed)) {
 	 	 	return TRUE;
 	 	}
 	 	switch ($type) {
 	 	 
 	 	 	case 'Integer' : return FALSE;
 	 	 	default  : return (empty($mixed)) ? TRUE : FALSE;
 	 	 	
 	 	}
 	}


	static function isBoolean($value) {
	 	
	 	return is_bool($value);
	 
	}
	
	
	static function toBoolean($value) {
	 	
		if ($value===1 || $value===0) {
	 	 	return ($value===1) ? TRUE : FALSE;
 	 	}
 	 	if ($value==='1' || $value==='0') {
	 	 	return ($value==='1') ? TRUE : FALSE;
 	 	}
 	 	if ($value==='TRUE' || $value==='FALSE') {
	 	 	return ($value==='TRUE') ? TRUE : FALSE;
 	 	}
 	 	if ($value==='true' || $value==='false') {
	 	 	return ($value==='true') ? TRUE : FALSE;
 	 	}
 	 	return (!$value  || empty($value)) ? FALSE : TRUE;
 	 	
 	 	
	}

	static function toString($value) {
	 
	 	$out='';
	 	switch (CastUtil::getType($value)) {	 	 
	 	 	case 'Array' : $out=CastUtil::serialize($value); break;
	 	 	default: $out=''.$value;
	 	}
	 	
	 	return $out;
	 
	}
	
	
	static function toArray($value) {
	 	$out=array();
	 	switch (CastUtil::getType($value)) {	 	 
	 	 	case 'Array' : return $value;
	 	 	default: $out=CastUtil::unserialize($value,'Array'); break;
	 	}
	 	if (!is_array($out)) {
	 	 	$out=array($value);
	 	}
	 	return $out;
	 	
	 
	}


	static function convert($value,$newtype='string') {
	 
	 	$newtype=strtolower($newtype);
	 
	 	$out=null;
	 
	 	switch ($newtype) {
	 	 
	 	 	case 'boolean' : $out=CastUtil::toBoolean($value); break;
	 	 	case 'array'   : $out=CastUtil::toArray($value); break;
	 		default: $out=CastUtil::toString($value); break;
	 	}
	 	
	 	return $out;
	 		
	}
 
	
	static function serialize($value) {
	 
	 	$out=serialize($value);
	 	return $out;
	 
	} 

	static function unserialize($value,$expect='') {
	 	
	 	if (!is_string($value)) {
	 	 	return FALSE;
	 	}
	 	if (empty($value)) {
	 	 	return FALSE;
	 	}
	 	if ($expect=='Array' && !strstr($value,'a:')) {
	 	 	return FALSE;
	 	}
	
	 	$out=unserialize($value);
	 	if (!$out) {
	 	 	ErrorHandler::throwDebugMsg(__CLASS__,__METHOD__,'unserialize failed for <i>'.$value.'</i>');
	 	}
	 	return $out;
	 
	}


}


?>