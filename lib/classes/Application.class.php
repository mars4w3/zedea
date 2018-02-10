<?php

class Application {
 
 	var $Config = array();
 	
 	var $Cache = array();
 	
	var $loaded = FALSE;
 	var $running = FALSE;
 	
 	var $Modules=array();
 	var $OutputModules=array();
 	var $Schedule=array();
 	
 	var $Plugins=array();

 	var $Hooks=array();
 	
 	var $User=null;
 
 	function __construct() {
	 
		$this->load();
		
		if ($this->loaded) {
		 	$this->init();
		 	//$this->run();
		} 	 
 	 
 	}


	function load() {
	 
	 	// load config
	 	importClass('AppConfig');
	 	$this->Config=AppConfig::getConfig();
	 	
	 	// import classes
	 	importClass('SessionUtil');
		importClass('ResponseUtil');
		importClass('RequestUtil');
		importClass('FileUtil');
		importClass('ArrayUtil');
		importClass('HTMLDocument');
			
		
		importClass('DBDriver');
		importClass('MySQLDriver');
		
		SessionUtil::startSession();
		OverrideUtil::registerApp($this);
		Loader::loadClass('ModUser','modules.mod_user');
		if (class_exists('ModUser')) {
			$this->User=new ModUser();
			$this->User->config['template']=array('loadpath'=>'tmpl','loadfile'=>'user_dashboard.php','loadlocal'=>TRUE);
			$this->User->initDriver($this->Config['DBDriver']);
			$this->User->load();
			$this->Modules['User']=$this->User;
		}
		//$this->registerOutput('User');
		
		
		Loader::loadClass('ModAccess','modules.mod_access');
		if (class_exists('ModAccess')) {
			ModAccess::onStaticLoad();
		}
		
		$this->loaded=TRUE;	 
	}


	function init() {
	 
	 	
	 	if (RequestUtil::hasParam('_lang')) {
	 	 	$this->setLang();
	 	}
	 	
	 	//OverrideUtil::registerApp($this);
	 	
			 
	}

	
	function check() {
	 
	 	// environment
	 	
	 	// accesscontrol
	 
	}

	function run() {
	 	ob_start();
		RequestUtil::setParam('_AppRun_',TRUE);
		$this->running=TRUE;
		if ($this->User) {
			$this->User->start();
		}	 
		$this->schedule();
		$this->launchModules();
		
	}

	function stop() {
		ob_end_flush();	 
	}


	function setLang() {
	 	$currLang=RequestUtil::getLanguage();
	 	$newLang=RequestUtil::getParam('_lang');
	 	if ($currLang!=$newLang) {
	 	 	SessionUtil::setValue('lang',$newLang);
	 	}
	}


	function cfg($key='') {
	 	return ArrayUtil::getValue($this->Config,$key,FALSE);
	}
	
	function getConfigValue($key='',$default) {
	 	return ArrayUtil::getValue($this->Config,$key,$default);
	}
 
 
 

 	
 	
 	
 	
 	
 	function registerPlugin($pluginName,$local=FALSE) {
 
 	 	$classname='Plugin_'.$pluginName;
 	 	Loader::loadClass($classname,'plugins',$local);
 	 	$this->Plugins[$pluginName]=new $classname;
 	 	
 	 	$plugin=$this->Plugins[$pluginName];
 	 	$plugin->onRegister();
 	 	
 	 
 	}
 	



	function registerModule($moduleName,$classpath='',$local=FALSE,$config=array()) {
 	 
 	 	$classname='Mod'.$moduleName;
 	 	$classpath=(empty($classpath)) ? 'modules.mod_'.strtolower($moduleName) :$classpath;
 	 	Loader::loadClass($classname,$classpath,$local);
 		$this->Modules[$moduleName]=new $classname;
 	 	$this->Modules[$moduleName]->initDriver($this->Config['DBDriver']);
 	 	$this->Modules[$moduleName]->config=$config;
 	 	$this->Modules[$moduleName]->setup();
 	 	$this->Modules[$moduleName]->onRegister();
 	 	
 	 		 
 	 }	

	function schedule() {
	 	foreach ($this->Modules as $moduleName=>$module) {
		 	if ($this->Modules[$moduleName]->listen()) {
	 	 	 	$this->Schedule[]=$moduleName;
	 	 	}
 	 	}
	 
	}


	function launchModules() {
	 	$scheduled=$this->Schedule;
	 	foreach ($scheduled as $moduleName) {
			$this->Modules[$moduleName]->load();
 	 	
 	 		if ($this->Modules[$moduleName]->hasOutput()) {
 	 	 		$this->registerOutput($moduleName);
 	 		}	 	 
	 	}
	 
	}

 	
 	
 	function loadModule($moduleName,$classpath='',$local=FALSE,$config=array()) {
 	 
 	 	$classname='Mod'.$moduleName;
 	 	$classpath=(empty($classpath)) ? 'modules.mod_'.strtolower($moduleName) :$classpath;
 	 	Loader::loadClass($classname,$classpath,$local);
 		$this->Modules[$moduleName]=new $classname;
 	 	
 	 	$this->Modules[$moduleName]->initDriver($this->Config['DBDriver']);
 	 	$this->Modules[$moduleName]->config=$config;
 	 	$this->Modules[$moduleName]->setup();
 	 	$this->Modules[$moduleName]->onRegister();
 	 	$this->Modules[$moduleName]->load();
 	 	
 	 	if ($this->Modules[$moduleName]->hasOutput()) {
 	 	 	$this->registerOutput($moduleName);
 	 	}
 	 		 
 	 }	
		
		
	 function registerOutput($moduleName) {
	  	$this->OutputModules[]=$moduleName;
	  
	 }
	 
	 function getModuleContent() {
	  	$out='';
	  	foreach ($this->OutputModules as $moduleName) {
	  	 	$out.=$this->Modules[$moduleName]->out();
	  	}
	  	return $out;
	 }		
 	 
 	 
 	 
 	static function getAppDBDriver() {
 		$appconf=AppConfig::getConfig();  
 		$dbconfig=ArrayUtil::getValue($appconf,'DBDriver',FALSE);
		if (!$dbconfig) {
		 	return FALSE;
		}  
 		$driver=new MySQLDriver();
	 	$driver->configure($dbconfig);
	 	
	 	return $driver;
 	  
 	}
 	
 	
 	static function getModule($name) {
 	 	if (empty($name)) {
 	 	 	return FALSE;
 	 	}
 	 	global $_theApp;
 
 	 	$module=ArrayUtil::getValue($_theApp->Modules,$name,FALSE);
 	 	if (!$module) {
 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Module <em>'.$name.'</em> is not registered.');
 	 	 	return FALSE;
 	 	}
 	 	return $module;
 	}


 	static function listModules() {
 	 	global $_theApp;
 
 	 	$modules=$_theApp->Modules;
 	 	if (!is_array($modules)) {
 	 	 	return FALSE;
 	 	}
 	 	$out=array();
 	 	foreach ($modules as $modName=>$module) {
 	 	 	$out[$modName]=array('moduleName'=>$modName);
 	 	}
 	 	return $out;
 	}


 	
 	static function updateSchedule() {
 	 	global $_theApp;
 	 	$_theApp->schedule();
 	}
 	
 	
 	static function recallModule($moduleName) {
 	 	if (empty($moduleName)) {
 	 	 	return FALSE;
 	 	}
 	 	global $_theApp;
 
 	 	$module=ArrayUtil::getValue($_theApp->Modules,$moduleName,FALSE);
 	 	if (!$module) {
 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Module <em>'.$name.'</em> is not registered.');
 	 	 	return FALSE;
 	 	}
 	 
 	 	if ($_theApp->Modules[$moduleName]->listen()) {
 	 	 	$_theApp->Modules[$moduleName]->load();
 	 	
 	 		if ($_theApp->Modules[$moduleName]->hasOutput()) {
 	 	 		$_theApp->registerOutput($moduleName);
 	 		}	
 	 	}
 	 
 	}
 	
 	
 	
 	static function defineConstant($constName,$constVal) {
 	 	if (!defined($constName)) {
 	 	 	define($constName,$constVal);
 	 	}
 	 
 	}
 	
 	static function getConstant($constName,$constDefault=FALSE) {
 	 	if (!defined($constName)) {
 	 	 	return $constDefault;
 	 	}
 		return constant($constName); 
 	 
 	}
 	
 	
 	static function setModuleParam($moduleName,$paramName,$paramData,$define=FALSE) {
 	 
 	 	global $_theApp;
 	 
 	 	if (!isset($_theApp->Config['Modules'])) {
 	 	 	$_theApp->Config['Modules']=AppConfig::getModulesConfig();
 	 	}
 	 	if (!isset($_theApp->Config['Modules'][$moduleName])) {
 	 	 	$_theApp->Config['Modules'][$moduleName]=array();
 	 	}
 	 	$defaultVal		= ArrayUtil::getValue($paramData,'default',FALSE);
 	 	$paramType		= ArrayUtil::getValue($paramData,'type',FALSE);
 	 	$paramVal		= ArrayUtil::getValue($_theApp->Config['Modules'][$moduleName],$paramName,$defaultVal);
 	 	$paramVal		= CastUtil::convert($paramVal,$paramType);
 	 	$_theApp->Config['Modules'][$moduleName][$paramName]=$paramVal;
 	 	
 	 	
 	 	if ($define) {
 	 	 	$paramVal	= $_theApp->Config['Modules'][$moduleName][$paramName];
 	 	 	$constName	= '_PARAM_'.$moduleName.'_'.$paramName;
 	 	 	$_theApp->defineConstant($constName,$paramVal);
 	 	}
 	 	
 	 
 	}
 	
 	static function getModuleParam($moduleName,$paramName,$default=FALSE) {
 	 
 	 	global $_theApp;
 	 	$conf			= $_theApp->Config;
 	 	
 	 	$modConfStore	= ArrayUtil::getValue($conf,'Modules',array());
 	 	$modConf 		= ArrayUtil::getValue($modConfStore,$moduleName,array());
 	 	
 	 	$param			= ArrayUtil::getValue($modConf,$paramName,FALSE);
 	 	return $param;
 	}
 
 
  	static function getAppPrefix() {
 	 	$prefix=(defined('__ApplicationPrefix__')) ? constant('__ApplicationPrefix__') : strftime('MyApp-%Y%m%d%H');
 	 	return $prefix;
 	 
 	}
 	
 	
 	static function cache($key='',$value='') {
 	 	global $_ApplicationRuntimeCache;
 	 	if (!is_array($_ApplicationRuntimeCache)) {
	 	 	$_ApplicationRuntimeCache=array();
	 	}
 	 	$_ApplicationRuntimeCache[$key]=$value;
 	
 	 
 	}
 	
 	static function getCacheValue($key,$default=FALSE) {
 	 	global $_ApplicationRuntimeCache;
 	 	$cache=$_ApplicationRuntimeCache;
 	 	if (!is_array($cache)) {
 	 	 	return $default;
 	 	}
 	 	return ArrayUtil::getValue($cache,$key,$default);
 	}
 

 
}



?>