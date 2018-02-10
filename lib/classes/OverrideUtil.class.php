<?php

class OverrideUtil {

	static function registerApp(&$app) {
	 	global $_theApp;
	 	$_theApp=$app;
	}


	static function registerAdminParams($classname='NULL',$instance=null,$params=array()) {
		global $_theApp;
 	 	$appHooks=$_theApp->Hooks;
 	 	
 	 	if (!isset($appHooks['AdminParams'])) {
 	 	 	$appHooks['AdminParams']=array();
 	 	}
		if (!isset($appHooks['AdminParams'][$classname])) {
 	 	 	$appHooks['AdminParams'][$classname]=array('static'=>array(),'instance'=>array());
 	 	}

		$appHooks['AdminParams'][$classname]['static']=$params;
		/*
 	 	if (is_object($instance)) {
 	 		$appHooks['AdminParams'][$classname]['instance'][]=array('instance'=>$instance,'params'=>$params);
 	 	}
 	 	*/
		$_theApp->Hooks=$appHooks;
	 
	 	 
	}

	static function getAdminParams($classname='',$staticOnly=TRUE) {
	 	global $_theApp;
 	 	$appHooks=$_theApp->Hooks;
 	 	$adminParams=ArrayUtil::getValue($appHooks,'AdminParams',array());
 	 	$items=array();

 	 	
 	 	foreach ($adminParams as $class=>$register) {
 	 	 	$item=array();
 	 	 	 	 	
 	 		$definedParams=ArrayUtil::getValue($register,'static',array());
 	 		foreach ($definedParams as $paramConf) {
 	 			$item['label']=ArrayUtil::getValue($paramConf,'label',$class);
 	 	 		$item['linkparam']=ArrayUtil::getValue($paramConf,'linkparam',array()); 
 	 	 		$items[]=$item;
 	 	 	}
 	 		
 	 		if (!$staticOnly) {
				$definedParams=ArrayUtil::getValue($register,'instance',array());	   	
 	 	 		foreach ($definedParams as $subregister) {
 	 	 		 	$instance=ArrayUtil::getValue($subregister,'instance',FALSE);
 	 	 		 	$params=ArrayUtil::getValue($subregister,'params',FALSE);
 	 	 			$item['label']=ArrayUtil::getValue($params,'label',$class);
 	 	 			$item['linkparam']=ArrayUtil::getValue($params,'linkparam',array());
	 	 	 		if (is_object($instance)) {
	 	 	 	 		$item['callback_dispatch']=array($instance,'dispatch');
	 	 	 		}
 	 	 			$items[]=$item;
 	 	 		}
 	 	 	}
 	 	 	
 	 	}
	 	return $items;
	}  


	static function registerStoreConf($class,$conf) {
		global $_theApp;	 
		$appHooks=$_theApp->Hooks;
		$dataContext=ArrayUtil::getValue($conf,'DataContext',array());
		foreach ($dataContext as $context=>$info) {
		 	$info['_class']=$class;
 	 		$appHooks['StoreConf'][$context]=$info;
 	 	}  
		$_theApp->Hooks=$appHooks;
	 
	}

	static function getClassByContext($context) {
	 	global $_theApp;	 
		$appHooks=$_theApp->Hooks;
		$storeConf=ArrayUtil::getValue($appHooks,'StoreConf',array());
		$info=ArrayUtil::getValue($storeConf,$context,array());
		$class=ArrayUtil::getValue($info,'_class','');
		return $class;
			 
	}
	
	static function getStoreConfByContext($context) {
	 	global $_theApp;	 
		$appHooks=$_theApp->Hooks;
		$storeConf=ArrayUtil::getValue($appHooks,'StoreConf',array());
		$info=ArrayUtil::getValue($storeConf,$context,array());
		return $info;		 
	}




 	
 	static function getCallbackResult($fromMethod='',$args=array()) {
 	 
 	 
		global $_theApp;
 		global $myFramework;

 		if (!is_object($_theApp)) {
 			return FALSE;
 		}
 			
	 	if (!is_array($_theApp->Hooks)) {
	 	 	return FALSE;
	 	}
	
	 	$register=ArrayUtil::getValue($_theApp->Hooks,'CallBack',FALSE);
	
	 	$callback=ArrayUtil::getValue($register,$fromMethod,FALSE);

	 	if ($callback) {
	 	 	return call_user_func($callback,$args);
	 	}
	 
		return FALSE ;
	 	
	 
	} 
	
	
	static function registerCallback($method,$callback) {
 	 
 	 	global $_theApp;

 	 	if (!is_object($_theApp)) {
 			return FALSE;
 		}
 	 
 	 	$appHooks=$_theApp->Hooks;

 	 	$appHooks['CallBack'][$method]=$callback;
 	 	   
		$_theApp->Hooks=$appHooks;
 	 
 	}
 	

	static function callHooks($classname='',$methodname='',$object=null,&$override=null) {

		global $_theApp;
 		global $myFramework;

 		if (!is_object($_theApp)) {
 			return FALSE;
 		}
 			
	 	if (!is_array($_theApp->Hooks)) {
	 	 	return FALSE;
	 	}
	
	 	$hooksOnClass=ArrayUtil::getValue($_theApp->Hooks,$classname,array());
	 	$hooksOnMethod=ArrayUtil::getValue($hooksOnClass,$methodname,array());
	 	
	 	//ErrorHandler::throwDebugMsg(__CLASS__,__METHOD__,'Call: '.$classname.' '.$methodname);

		foreach ($hooksOnMethod as $hook) {
		 	
		 	$callback=ArrayUtil::getValue($hook,'callback');
		 	$args=ArrayUtil::getValue($hook,'args');
		 	
		 	if (is_array($callback)) {
		 	 	$objOrClass=$callback[0];
		 	 	if ( (!is_object($objOrClass) && !class_exists($objOrClass)) || $objOrClass==='GetInstance' ) {
		 	 	 	$callback[0]=$object;
		 	 	 	
		 	 	}
		 	}
		 	if (is_callable($callback)) {
				//call_user_func($callback,$args,$override);
				call_user_func_array($callback,array($args,&$override)); 
			}	
		 		
		} 
	 	
	 
	} 	
	

	
	static function registerHook($class,$method,$hook) {
 	 
 	 	global $_theApp;
 		global $myFramework;
 	 
 	 	$appHooks=$_theApp->Hooks;
 	 
	  	if (!isset($appHooks[$class])) {
		   	$appHooks[$class]=array();
		}
		if (!isset($appHooks[$class][$method])) {
		   	$appHooks[$class][$method]=array();
		}		 
		   
 	 	$appHooks[$class][$method][]=$hook;
		   
		$_theApp->Hooks=$appHooks;
 	 
 	}
 	
 	
 
 
 
 
}


?>