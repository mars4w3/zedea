<?php

class Babel {
 
 
 	static function _($string='',$repl=array(),$context='global') {
 
 		if (empty($string)) {
		 	return ''; 
		}	 	
 	 	$lang=RequestUtil::getLanguage();
	
		Babel::switchContext($context); 	 	
 	 	$out=Babel::getTranslation($string,$lang,$repl);
 	 	Babel::switchContext('global');
 	 	return $out;
 	 	
 	}
 	
 	
 	
 
 	static function switchContext($context) {
 		Babel::setCacheContext($context);
		Babel::getLangCache();	 
		//ErrorHandler::dump(__CLASS__,__METHOD__,$context);
 	}
 
 	static function getTranslation($string='',$lang='de',$repl=array()) {
 		if (empty($string)) {
		 	return ''; 
		}
		
		$translationTable=Babel::getTranslationTable($lang);
		
		$trans=ArrayUtil::getValue($translationTable,$string,FALSE);
		if (!$trans) {
			Babel::addTranslation($string,$lang);
			$trans=$string;  	
		}
		
		$out=$trans;
		
		
		if (!empty($repl)) {
		 	$search=ArrayUtil::getKeys($repl);
		 	$replace=ArrayUtil::getValues($repl);
		 	$out=TextParser::replace($out,$search,$replace,'%%');
		}
 	 	
 	 	return $out;
 	 
 	}
 	
 	
 	static function getTranslationTable($lang='de') {
 	 	$translations= Babel::getLangCache($lang);
 	 	if (!$translations) {
 	 	 	$translations=Babel::loadTranslationTable($lang);
 	 	}	
 	 
 	 	return $translations;
 	}
 	
 	static function loadTranslationTable($lang='de') {
 	 	$loadpath	= Babel::getLoadPath();
 	 	$loadfile	= 'lang_'.$lang.'.php';
		$local		= Babel::getLoadLocal();
		
		$convert	= (!defined('__ModBabel_IsUTF__')) ? TRUE : FALSE;
		
		$config=Loader::loadConfig($loadpath,$loadfile,$local,$convert);
 	 	 	
 	 	Babel::registerLangCache($lang,$config);
 	 	return $config;
 	 
 	}
 	
 	
 	static function addTranslation($string,$lang,$translation='') {
 	 
 	 	if (empty($translation)) {$translation=$string; }
 	 
 	 	$cache=Babel::getLangCache($lang);
 	 	$cache[$string]=$translation;
 	 	Babel::registerLangCache($lang,$cache);
 	 	Babel::writeTranslationTable($lang);
 			 	
 	}
 
 
 	static function writeTranslationTable($lang) {
 	 	$loadpath	= Babel::getLoadPath();
 	 	$loadfile	= 'lang_'.$lang.'.php';
		$local		= Babel::getLoadLocal();
		
		$uri=Loader::getPath($loadpath,$local);
		$uri.='/'.$loadfile;
		
		
		$cache=Babel::getLangCache($lang);
		$out=RenderUtil::toPHP($cache);
		
		$convert	= (!defined('__ModBabel_IsUTF__')) ? TRUE : FALSE;
		if ($convert) {
			$out=utf8_decode($out);
		}
		FileUtil::writeFile($uri,$out,TRUE);
 	 
 	}
 
 
 	static function getLoadPath() {
 	 	return Babel::getFromCache('loadpath');
 	}
 	
	static function getLoadLocal() {
 	 	return Babel::getFromCache('local');
 	}
 	
 	static function getLangCache($lang='de') {
	 	$translations	= Babel::getFromCache('translations',array());
		$langCache 		= ArrayUtil::getValue($translations,$lang,FALSE);
		return $langCache;
	} 
 
 	static function registerLangCache($lang='de',$data=array()) {
 	 	global $_BabelContextCache;
 	 	if (!is_array($_BabelContextCache)) {
 	 	 	Babel::initContextCache();
 	 	}
 	 	$context	= ArrayUtil::getValue($_BabelContextCache,'_currentContext','global');
		$_BabelContextCache[$context]['translations'][$lang]=$data;
 	}


 	static function registerContext($context,$loadpath,$local=FALSE,$import=FALSE) {
 	 	global $_BabelContextCache;
 	 	if (!is_array($_BabelContextCache)) {
 	 	 	Babel::initContextCache();
 	 	}
 	 	
 	 	
 	 	// Localizer
 	 	if (defined('_FW_BABEL_LOAD_LOCAL_') || defined('__ModBabel_LoadLocal__')) {
 	 	 	
 	 	 	//$path=Loader::getPath('lang',TRUE);
 	 	 	//FileUtil::createDir($path,$context);
 	 	 	$res= Babel::initLocalStore($context,$loadpath,'lang',$local,$import);
 	 	 	if ($res) {
				$local=TRUE;
 	 	 		$loadpath='lang.'.$context;
 	 	 	}
 	 	}
 	 	// ---------
 	 	
 	 	
 	 	$_BabelContextCache[$context]=
		  			array(
 	 					'loadpath'		=> $loadpath,
 	 					'local'			=> $local,
						'translations'	=> array()
					);  	
 	}
 	
 	static function initLocalStore($context,$globalpath,$localpath,$waslocal,$import=FALSE) {
 	 	
		$realGlobalPath	= Loader::getPath($globalpath,$waslocal);
		$realLocalPath	= Loader::getPath($localpath,TRUE);
 	 
 	 	if (!FileUtil::isDir($realLocalPath)) {
		 	return FALSE;  // local dir /lang does not exist: fallback to global  
		}
		if (!FileUtil::isDir($realLocalPath.'/'.$context)) {	
	 	 	FileUtil::createDir($realLocalPath,$context);
	 	 	if (!FileUtil::isDir($realLocalPath.'/'.$context)) {
	 	 		return FALSE; // local context could not be created
	 	 	}
	 	 	if ($import) {
	 	 	 	$source	= $realGlobalPath;
	 	 	 	if (FileUtil::isDir($realGlobalPath)) {
	 	 	 		$dest 	= Loader::getPath($localpath.'.'.$context,TRUE);
	 	 	 		FileUtil::copyDirFiles($source,$dest,FALSE);
	 	 	 	}
	 	 	}
	 	}
	 	return TRUE;
 
 	}
 	
 	static function setCacheContext($context) {
 	 	global $_BabelContextCache;
 	 	if (!is_array($_BabelContextCache)) {
 	 	 	Babel::initContextCache();
 	 	}
 	 	$_BabelContextCache['_currentContext']=$context;
 	 
 	}
 
 
 	static function getFromCache($what='') {
 	 	global $_BabelContextCache;
 	 	if (!is_array($_BabelContextCache)) {
 	 	 	Babel::initContextCache();
 	 	}
 	 	$cache		= $_BabelContextCache;
 	 	$context	= ArrayUtil::getValue($cache,'_currentContext');
 	 	$concache	= ArrayUtil::getValue($cache,$context);
 	 	$local  	= (defined('_FW_BABEL_LOAD_LOCAL_') || defined('__ModBabel_LoadLocal__')) ? TRUE : FALSE;
 	 	switch ($what) {
 	 	 	case 'loadpath' 	: return ArrayUtil::getValue($concache,'loadpath','lang');
			case 'local'		: return ArrayUtil::getValue($concache,'local',$local);
			case 'translations'	: return ArrayUtil::getValue($concache,'translations',array());    
 	 	}
 	}
 
 	static function initContextCache() {
 	 	global $_BabelContextCache;
 	 	$local  	= (defined('_FW_BABEL_LOAD_LOCAL_') || defined('__ModBabel_LoadLocal__')) ? TRUE : FALSE;
 	 	if (!is_array($_BabelContextCache)) {
 	 	 	$_BabelContextCache=
			   array(
 	 	 			'global'=>array('loadpath'=>'lang','local'=>$local,'translations'=>array()),
 	 	 			'_currentContext'=>'global',
 	 	 		);
 	 	 	Babel::registerContext('global','lang',FALSE);
 	 	}
 	 
 	}

 
 
}