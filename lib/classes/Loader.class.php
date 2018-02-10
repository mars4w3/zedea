<?php

class Loader {

 
	static function load($loadpath,$loadfile='index.php',$include=TRUE,$local=TRUE) {

		$uri='';
		if ($uri=Loader::getPath($loadpath,$local)) {
		 	$uri.='/'.$loadfile;
		}
		else {
		 	return FALSE;
		}
	
		if (!FileUtil::isFile($uri)) {
		 	ErrorHandler::Err((_ErrCode_RsrcNotFound_),'Class '.(__CLASS__).' - '.(__METHOD__).' : file not found: <i>'.$uri.'</i>');
		 	return FALSE;
		}
		
		
		if ($include) {
		 	include($uri);
		 	return get_defined_vars();
		}
		else {
		 	$content=FileUtil::getContent($uri);
		 	return $content;
		 
		}
		
		return FALSE;
		
	 
	}
	
	
	static function getPath($loadpath,$local=TRUE) {
	 
	 	$path=str_replace('.','/',$loadpath);
	 	$dir=defined('__FrameWorkRessourcePath__') ? constant('__FrameWorkRessourcePath__') : '.';
	 	if ($local) {
	 		$dir=defined('__ApplicationRessourcePath__') ? constant('__ApplicationRessourcePath__') : $dir;
	 	}
		
		$uri=$dir.'/'.$path;
		if (!is_dir($uri)) {
		 	ErrorHandler::Err((_ErrCode_RsrcNotFound_),'Class <b>'.(__CLASS__).'</b> - <b>'.(__METHOD__).'</b> : path or directory not found: <i>'.$uri.'</i>');
		 	return FALSE;
		}
		
		return $uri;
		
	}
 
 
 	static function loadConfig($loadpath,$filename='index.php',$local=TRUE,$convert=TRUE) {
 	
 		if (empty($filename)) {
 		 	$filename='index.php';
 		}
 	
 	 	$vars=Loader::load($loadpath,$filename,TRUE,$local);
 	 	$config=$vars['config'];
 	 	
 		if ($convert) {
 	 		mb_convert_variables('UTF-8','ISO-8859-1',$config);
 	 	}
 	 	
 	 	return $config;
 	 
 	}
 	
 	
 	static function loadFile($loadpath,$file='') {
 	 	if (empty($file)) {
 	 	 	return FALSE;
 	 	}
 	 	$content=Loader::load($loadpath,$file,FALSE);
 	 	return $content; 
 	}
 	
 	static function loadTemplate($loadpath,$file='',$local=FALSE) {
 	 	if (empty($file)) {
 	 	 	return FALSE;
 	 	}
 	 	$content=Loader::load($loadpath,$file,FALSE,$local);
 	 	return $content; 
 	}
 	
 	
 	static function embedFile($loadpath,$file='') {
 	 	if (empty($file)) {
 	 	 	return FALSE;
 	 	}
 	 	
 	 	ob_start();
 	 	Loader::load($loadpath,$file,TRUE);
 	 	$content=ob_get_contents();
 	 	ob_end_clean();
 	 	return $content;
 
 	}
 	
 	
 	static function loadClass($classname='',$classpath='',$local=FALSE) {
 	 	if (class_exists($classname,FALSE)) {
 	 	 	return TRUE;
 	 	}
 	 	if (empty($classname)) {
 	 	 	return FALSE;
 	 	}
		if (empty($classpath)) {
 	 	 	$classpath='lib.classes';
 	 	}
 	 
 	 	$classfile=$classname.'.class.php';
 	 	Loader::load($classpath,$classfile,TRUE,$local);
 	 	if (!class_exists($classname,FALSE)) {
 	 	 	return FALSE;
 	 	}
 	 	return TRUE;
 	 
 	}
 	
 	
 	static function loadModule($moduleName,$classpath='',$local=FALSE,$config=array()) {
 	 
 	 	$classname='Mod'.$moduleName;
 	 	$classpath=(empty($classpath)) ? 'modules.mod_'.strtolower($moduleName) :$classpath;
 	 	Loader::loadClass($classname,$classpath,$local);
 		$module =new $classname;
 	 	return $module;
 	 		 
 	 }	
 	
 
}

?>