<?php

class AppConfig extends Application {
 
 	static function config() {
 		/* DEPRECATED */
 		ErrorHandler::throwException(__CLASS__,__METHOD__,'DEPRECATED - Pleade use getConfig() instead.');
		return AppConfig::getConfig(); 	 
 	}
 
 	static function getConfig() {
 	 
 		$config=Loader::loadConfig('config.protected','appConfig.php',TRUE);
 		return $config;
 
	}
	
	static function getModulesConfig() {
	 
	 	$config=Loader::loadConfig('config.protected','appModulesConfig.php',TRUE);
 		return $config;
	 
	}
	
	static function storeModulesConfig($config) {
	 	$storePath	= Loader::getPath('config.protected',TRUE);
	 	FileUtil::createDir($storePath);
	 	$storeFile	= $storePath.'/appModulesConfig.php';
	 	$data		= RenderUtil::toPHP($config,'config');
		FileUtil::writeFile($storeFile,$data,TRUE);
	 
	}
	
	
}

?>