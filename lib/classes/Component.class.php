<?php


class  Component {

	var $Model=null;
	var $Views=array();
	var $Controller=null;
	
	var $DBDriver=null;
	
	var $name='A Component';
	var $slug='component';
	var $basepath='com_component';
	var $template='fullpage.php';
	var $version='1.0.0';
	
	var $output='';

	var $listen=array('com_compontent');
	var $params=array();
	var $config=array();
	

	function __construct() {
		$this->loadController();
	}

	static function factory($name='') {
	 	$classname	= $name;
	 	$classname	= 'Component_'.$classname;
	 	$component	= new $classname();
		return $component;  
	 
	}


	function loadController() {
	 	$this->Controller=$this;
	 	if ($this->listen()) {
	 	 	$this->call();
	 	}
	}


	function loadParams() {
	 	$loadpath=$this->basepath.'.config';
	 	$loadfile='params.php';
	 	$params=Loader::loadConfig($loadpath,$loadfile,FALSE);
	 
	 	if ($params) {
	 		$this->params=$params;
	 	}
	 
	}
	
	
	function getParam($context='',$key='',$default='') {
	 	$params=$this->params;
	 	if (!is_array($params)) {
	 	 	return $default;
	 	}
	 	$cParam=ArrayUtil::getValue($params,$context,array());
	 	$out=ArrayUtil::getValue($cParam,$key,$default);
	 	
	 	return $out;
	 	
	}

	// Control


	
	function listen() 
	{
		return RequestUtil::hasParams($this->listen);
		
	}
	
	

	function call() 
	{
	 	$this->load();
		$this->dispatch();
		$this->execute();	 
	 
	}
	
	

	function load() {
	 	
	}
	

	function dispatch() {
	 
	 
	}


	function route() {
	 
	 
	}

	function redirect($url) {
	 	ResponseUtil::clean();
	 	ResponseUtil::redirect($url);
	}


	function execute() {
	 
	 	$this->output= 'Hi, I am a Component';
	}
	
	function out() {
	 	return $this->output;
	}
	
	
	
	
	function setDriver($driver) {
	 	if (is_object($driver)) {
	 	 	$this->DBDriver=$driver;
	 	}
	}

	function initDriver($config) {
	 	/*
	 	$dbhost=ArrayUtil::getValue($config,'dbhost',FALSE);
	 	$dbuser=ArrayUtil::getValue($config,'dbuser',FALSE);
	 	$dbpass=ArrayUtil::getValue($config,'dbpass',FALSE);
	 	$dbname=ArrayUtil::getValue($config,'dbname',FALSE);
	 	$tblprefix=ArrayUtil::getValue($config,'tblprefix',FALSE);
	 	
	 	$driver=new MySQLDriver();
	 	if ($dbhost) { $driver->Host=$dbhost; }
	 	if ($dbuser) { $driver->User=$dbuser; }
	 	if ($dbpass) { $driver->Pass=$dbpass; }
	 	if ($dbname) { $driver->Database=$dbname; }
	 	if ($tblprefix) { $driver->TablePrefix=$tblprefix; }
	 	*/
	 	$driver=new MySQLDriver();
	 	$driver->configure($config);
		$this->DBDriver=$driver;
	 
	}
	
	function getDriver() {
	 	return $this->DBDriver;
	}



	function loadTemplate($args=array()) {
 		
		$tmpl=ArrayUtil::getValue($this->config,'template',array());
		
		$loadpath=ArrayUtil::getValue($tmpl,'loadpath',$this->basepath.'.tmpl');
		$loadfile=ArrayUtil::getValue($tmpl,'loadfile',$this->template);
		$loadlocal=ArrayUtil::getValue($tmpl,'loadlocal',FALSE);
				 	
	 	$out=Loader::loadTemplate($loadpath,$loadfile,$loadlocal);
 	 	return $out;
 	 
 	}
 
 
}


?>