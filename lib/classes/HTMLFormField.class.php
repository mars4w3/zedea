<?php

class HTMLFormField {
 
 	var $cfg;
 	var $errMsg='';
 	
 	function __construct($config=array()) {
 	 	$this->cfg=$config;
 	 	$this->registerLang();
 	}
 
 	function registerLang() {
	 
	 	$langpath='modules.mod_forms.lang';
  	 	Babel::registerContext('ModFormField',$langpath);
  	 	Babel::registerContext('ModForms',$langpath);
	}
 	
 	function renderInput($conf=array()) {
 	 
 	 	$name=$conf['name'];
		$value=$conf['value'];
		$out='';
		$out.='<input class="input" type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'"/>';
		return $out;
 	}

	function renderOutput($conf=array()) {
 	 	$name=$conf['name'];
		$value=$conf['value'];
	 	
	 	return $value;
	}
	
	function renderOutputValue($conf=array()) {
 	 	$name=$conf['name'];
		$value=$conf['value'];
	 	
	 	return $value;
	}
	
	
	function renderStorageValue($conf=array()) {
 	 	$name=$conf['name'];
		$value=$conf['value'];
	 	
	 	return $value;
	}
	
	function checkInputValue($conf=array()) {
	 	$name=$conf['name'];
		$value=$conf['value'];
		
		return TRUE;
	}


	function getErrMsg() {
	 	return $this->errMsg;
	}


	function parseValue($value,$outType='') {
	 
	}


	static function factory($name='') {
	 	
	 	$classname		= $name;
		 	
	 	if (empty($classname) || $classname=='FF_') {
	 	 	$classname	= 'FF_text';
	 	}
	 	
	 	
	 	$classpath	='modules.mod_forms.view.FormField';
	 	$loadlocal	= FALSE;
	 	
	 	// local classes
	 	// a) by keyword LOCAL_
	 	if (strstr($classname,'FF_LOCAL_')) {
	 	 	$classname	= str_replace('FF_LOCAL_','FF_',$classname);
	 	 	$classpath	= 'modules.mod_forms.FF';
			$loadlocal 	= TRUE;   
	 	}
		// b) by path reference
		if (strstr($classname,'.')) {
		 	$classpath	= str_replace('FF_','',$classname);
		 	$classname	= 'FF_'.substr($classpath,strrpos($classpath,'.')+1);
		 	$classpath	= substr($classpath,0, strrpos($classpath,'.'));
		 	$loadlocal	= TRUE;
		}
	 	
	 
	 	$res=Loader::loadClass($classname,$classpath,$loadlocal);
	 	
	 	if ($res) {
	 	 	$object=new $classname;
	 	 	return $object;
	 	}
	 	else {
	 	 	$classname = __CLASS__;
            $object= new $classname;
            return $object;
	 	}
	 	
	}
	

 
}