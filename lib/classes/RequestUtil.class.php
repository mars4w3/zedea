<?php

class RequestUtil {
 
 
 	static function getMethod() {
 		
 		if (isset($_SERVER)) {
		 	return (isset($_SERVER['REQUEST_METHOD'])) ?  $_SERVER['REQUEST_METHOD'] : 'undefined';
		}	 
 	 	
 	 	
 	}


	static function hasNoParams() {
	 	$get=RequestUtil::filterParams('GET');
	 	$post=RequestUtil::filterParams('POST');
	 	if (!$get && !$post) {
	 	 	return TRUE;
	 	}
	 	return FALSE;
	}

	static function hasParam($key='',$context='') {
	 
		$params=RequestUtil::filterParams($context);		
	 	return ArrayUtil::hasKey($params,$key);
	}


	static function hasParams($keys=array(),$context='',$operator='OR') {
	 	if (!is_array($keys)) { return FALSE; }
	 	$result=FALSE;	 	
	 	foreach ($keys as $key) {
	 	 		 $tempres=($operator==='AND') ? FALSE : $result; 	
				 $result=(RequestUtil::hasParam($key,$context)) ? TRUE : $tempres;	 	
	 	}
	 	return $result;
	 
	}

 
 	static function getParam($key='',$default='',$context='') { 	 
 	 	
		$params=RequestUtil::filterParams($context);
		$value=ArrayUtil::getValue($params,$key,$default);
 	 	return $value;
 	 
 	}
 	
 	static function setParam($key,$value,$strict=FALSE) {
		$_REQUEST[$key]=$value;
		if ($strict) {
		 	$_GET[$key]=$value;
		}
 	}

	static function unsetParam($key,$strict=FALSE) {
		if (ArrayUtil::hasKey($_REQUEST,$key)) {
		 	unset ($_REQUEST[$key]);
		}
		if ($strict) {
			if (ArrayUtil::hasKey($_GET,$key)) {
		 		unset ($_GET[$key]);
			}
		}
 	}
  	
  	
  	static function filterParams($context='') {
 	 	
 	 	$params=$_REQUEST;
 	 	
 	 	if (!empty($context)) {
 	 	 	switch ($context) {
 	 	 	 	case  'POST' : $params=(isset($_POST)) ? $_POST : FALSE; break;
 	 	 	 	case  'GET' : $params=(isset($_GET)) ? $_GET : FALSE; break;
 	 	 	 	case  'COOKIE' : $params=(isset($_COOKIE)) ? $_COOKIE : FALSE; break;
 	 	 	 	case  'FILES' : $params=(isset($_FILES)) ? $_FILES : FALSE; break;
 	 	 	}
 	 	}
 	 
 	 	return $params;
 	 
 	}
  	
 	
 	static function getRequestUri($complete=FALSE) {
 	 
 	 	$uri=ArrayUtil::getValue($_SERVER,'SCRIPT_NAME','');
 	 	if ($complete) {
 	 	 	$protocol='http://';
 	 	 	$host=ArrayUtil::getValue($_SERVER,'SERVER_NAME');
 	 	 	$uri=$protocol.''.$host.''.$uri;
 	 	}
 	 
 	 	return $uri;
 	}
 	
 	static function getQueryString($newparams=array(),$newonly=FALSE,$ampentity=TRUE) {
	 	$params=RequestUtil::filterParams('GET');
	 	$amp=(!$ampentity) ?  '&' : '&amp;';
	 	
		if (is_array($newparams)) {
		 	foreach($newparams as $key=>$val) {
		 		$params[$key]=$val;	 
		 	}
		}
		$out='';
		if ($newonly) {
		 	$params=$newparams;
		}
		if (isset($params['clearAll'])) {
	 	 	unset($params['clearAll']);
	 	}
		
		foreach($params as $key=>$val) {
		 
		 	if (!empty($val)) {
		 	 	$out.=(empty($out)) ? '' : $amp;
		 		$out.=urlencode($key).'='.urlencode($val);
		 		
		 	}
		}
		 
		$out='?'.$out;
		return $out;  
	  
	} 
 	

 	
 	
 	
 	static function hasUploads() {
 	 	return (RequestUtil::filterParams('FILES')) ? TRUE : FALSE;
 	 
 	}
 	
 	static function hasUpload($key) {
 	 	$upload=RequestUtil::getParam($key,array(),'FILES');
 	 	$tmpName=ArrayUtil::getValue($upload,'tmp_name','');
 	 	return is_uploaded_file($tmpName);
 	}
 	
 	
 	static function getLanguage() {
 	 	$default='de';
 	 	
 	 	if ($lang=SessionUtil::getLanguage()) {
 	 	 	return $lang;
 	 	}
 	 	return $default;
 	 
 	}
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	static function collect($context='') {
 	 	$array=RequestUtil::filterParams($context);
 	 	return $array;
 	 
 	}
 	
 	
 	
 	static function dump($context='',$print=FALSE) {
 	 
 	 	$array=RequestUtil::filterParams($context);
 	 	$out= ArrayUtil::toHTML($array);
 	 	if ($print) {
 	 	 	echo $out;
 	 	 	return TRUE;
 	 	}
 	 	return $out;
 	 	
 	 
 	}
 	
 	
 	static function clean() {
 	 	$params=RequestUtil::collect();
 	 	foreach ($params as $key=>$val) {
 	 	 	RequestUtil::unsetParam($key);
 	 	}
 	 
 	}
 	
 	
 	static function hasUser() {
 	 	$user=$_SERVER['REMOTE_USER'];
 	 	if (!empty($user)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	 
 	}
 
 	static function getUserName() {
 		if (RequestUtil::hasUser()) {
 		 	return $_SERVER['REMOTE_USER'];
 		}
 	 
 	}
 	
 	static function getUserIP() {
 	 	return $_SERVER['REMOTE_ADDR'];
 	}
 
 
 	static function encodeSecureParam($value) {
 	 	return base64_encode($value);
 	}
 	
 	static function decodeSecureParam($encoded) {
 	 	return base64_decode($encoded);
 	}
 	
 	static function setSecureParam($key,$value) {
 	 	$value=RequestUtil::encodeSecureParam($value);
 	 	RequestUtil::setParam($key,$value);
 	}
 	
 	static function getSecureParam($key,$default='') {
 	 	$out=RequestUtil::getParam($key,$default);
 	 	if ($out!=$default) {
 	 	 	$out=RequestUtil::decodeSecureParam($out);
 	 	}
 	 	return $out;
 	}
 
 
 
}


?>