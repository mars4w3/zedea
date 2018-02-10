<?php
/**
*****************************************************************
* Class Module
* parent class for all modules built with [zedea]-framework
*
* Created 	: 2011-11-01 <mars@4w3.de>
* Revision 	: 2012-05-05
*****************************************************************
**/


class Module extends Component {

	
	var $name			= 'A Module';
	var $slug			= 'module';
	var $version		= '1.0.0';
	var $basepath		= 'mod_module';
	
	var $isLocal 		= FALSE;
	var $localConfig 	= FALSE;
	var $localSetup 	= FALSE;
	
	var $selfName		= 'Module';		// canonical name (e.g. for registry purpose)
	
	var $hasSetup		= FALSE;		// TRUE if optional params can be set (to be defined in config/setup.php)	
	
	var $hasWorkflow	= FALSE;		// TRUE if extended workflow functions enabled
	
	var $hasContentOpts	= FALSE;		// TRUE if content options defined in config/contentoptions.php
	
	var $output			= '';
	

	var $params			= array();
	var $listen			= array('mod_module');
	

	function __construct($autoload=FALSE) {
		if ($autoload) {
			$this->load();
		}
	}

	static function factory($name='') {
	 	$classname='Module_'.$name;
	 	$module=new $classname();
		return $module;  
	 
	}


	function onConstruct() {
	 
	}

	function onRegister() {
	 
	 	$modConf=Loader::loadConfig($this->basepath.'.config','modelconfig.php',FALSE,FALSE);
  	 	OverrideUtil::registerStoreConf(get_class($this),$modConf);
	}
	
	function registerLang() {
	 	$langpath=$this->basepath.'.lang';
  	 	Babel::registerContext(get_class($this),$langpath);
	 
	}


	// Control

	function listen() 
	{
		return RequestUtil::hasParams($this->listen);
		
	}
	
	
	function load() {
	 	if ($this->listen()) {
	 	 	$this->loadModel();
	 	 	$this->loadController();
	 	 	$this->onLoad();
	 	 	$this->call();
	 	}
	 	
	}


	function onLoad() {
	}

	function call() 
	{	 
		$this->dispatch();
		$this->execute();	  
	}
	
	
	function loadModel() {
	 	$this->Model=new Model();
	 	$this->Model->Parent=$this;
	}
	
	function loadController() {
	 	$this->Controller=new Controller();
		$this->Controller->Parent=$this;
		$this->Controller->Model=$this->Model;	
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
	 	
	}


	function hasOutput() {
	 	if (!empty($this->output)) {
	 	 	return TRUE;
	 	}
	 	return FALSE;
	}
	
	function out() {
	 	return $this->output;
	}


	
	function loadTemplate($args=array()) {
 		
		$default=ArrayUtil::getValue($this->config,'template',array());
		$tmpl=ArrayUtil::getValue($args,'template',$default);
		
		$loadpath=ArrayUtil::getValue($tmpl,'loadpath',$this->basepath.'.tmpl');
		$loadfile=ArrayUtil::getValue($tmpl,'loadfile','fullpage.php');
		$loadlocal=ArrayUtil::getValue($tmpl,'loadlocal',FALSE);
				 	
	 	$out=Loader::loadTemplate($loadpath,$loadfile,$loadlocal);
 	 	return $out;
 	 
 	}


	function getMenu() {
	 
	}

	function getTitle() {
	 
	}

	function getPagebar() {
	 
	}

	function getSidebar() {
	 
	}

	function getContent() {
	 
	}


	function getItem($context='',$id) {

		if (empty($context)) {
		 	return FALSE;
		}	 	

	 	$args	= $this->getContextArgs($context);
	 	
		$args['resultMode']	= 'record';
	 	$args['filter']		= "".$args['pkey']."='".$id."'";
	 	$args['limit']		= 1;
	 	$args['resulttype']	= 'record';
	 	
		$dbDriver			= $this->DBDriver;
		$item				= $dbDriver->select($args);
		return $item;
	 
	}

	function getItems($context='',$conf=array(),$accessControl=TRUE) {
	 	
	 	if (empty($context)) {
		 	return FALSE;
		}
	 
	 	$args=$this->getContextArgs($context);
	 	$args['filter']		= ArrayUtil::getValue($conf,'filter',"1");
	 	$args['limit']		= ArrayUtil::getValue($conf,'limit',5);
	 	$args['sortBy']		= ArrayUtil::getValue($conf,'sortBy','lastmod');
	 	$args['sortOrder']	= ArrayUtil::getValue($conf,'sortOrder','DESC');
	 	$args['resulttype']	= 'data';
	 	
	 	if ($accessControl) {
	 	 	$withWorkFlow	= $this->hasWorkflow;
	 		$args['filter']	= ModAccess::getAccessFilters($args['filter'],$withWorkFlow);
	 	}
	 	
	 	//var_dump($context,$accessControl,$args['filter']);
	 	
	 	$dbDriver			= $this->DBDriver;
		$items				= $dbDriver->select($args);
		return $items;
	 
	}


	function callForContent() {
	 	$this->loadModel();
	 	$this->loadController();
	 
	}
 

	function getContextArgs($context) {
	 
	 	$dataModel		= OverrideUtil::getStoreConfByContext($context);
	 	$dbtable		= ArrayUtil::getValue($dataModel,'dbtable',FALSE);
	 	$pkey			= ArrayUtil::getValue($dataModel,'pkey',FALSE);
	 	$param			= ArrayUtil::getValue($dataModel,'param',FALSE);
	 	$contentcols	= ArrayUtil::getValue($dataModel,'contentcols',FALSE);
	 	$dbcols			= array();
	 	foreach ($contentcols as $cast=>$realname) {
	 	 	$dbcols[]	= ' '.$realname.' AS '.$cast;
	 	}
	 	$dbcols[]		= " CONCAT('".$param."','=',".$pkey.") AS itemlink ";
	 	$args=array(
	 		'dbtable'	=> $dbtable,
	 		'columns'	=> $dbcols,
	 		'pkey' 		=> $pkey,
	 	);
	 	
	 	//ErrorHandler::dump(__CLASS__,__METHOD__,$args);
	 	
	 	return $args;
	 		
	 
	}
	
	
	
	function setup($return=FALSE) {
	 	
	 	if ($this->hasSetup) {
	 	 	$loadPath=$this->basepath.'.config';
	 	 	$params=Loader::loadConfig($loadPath,'setup.php',$this->isLocal,FALSE);
	 	 	if ($return) {
	 	 	 	return $params;
	 	 	}
	 	 	foreach ($params as $paramName=>$paramData) {
	 	 	 	Application::setModuleParam($this->selfName,$paramName,$paramData,TRUE);
	 	 	}
	 	}
	 	else {
	 		if ($return) {
	 			return FALSE;	 
	 		}
		}
	 
	 
	}

 
}


?>