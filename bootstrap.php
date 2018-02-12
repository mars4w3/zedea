<?php
// starttime
define('__FWRuntimeStartTime',microtime());
// ---

// startup params and/or phpini setup (locale / timezone etc) 
if (!defined('_DefaultTimezone_')) { define('_DefaultTimezone_','Europe/Berlin'); }
date_default_timezone_set(constant('_DefaultTimezone_')); 

ini_set('default_charset','UTF-8'); 
// ---


// bootstrap path constants
if (!defined('_DS')) {
 	define('_DS','/');
}
if (!defined('__FrameWorkRessourcePath__')) {
 	define('__FrameWorkRessourcePath__',dirname(__FILE__));
}
if (!defined('__ApplicationRessourcePath__')) {
 	define('__ApplicationRessourcePath__','..');
}

define('_ZEDEA_CLASSPATH_',__FrameWorkRessourcePath__._DS.'lib'._DS.'classes'._DS);
// ---


// errorcodes 
define('_ErrCode_RsrcAccessDenied_'	,40001);
define('_ErrCode_RsrcNotReadable_'	,40002);
define('_ErrCode_RsrcNotWritable_'	,40003);
define('_ErrCode_RsrcNotFound_'		,40004);
// ---


// load error handler
importClass('ErrorHandler');
// ---


// implementing autoload 
function importClass($classname) {
 	zedea_autoload($classname);
}

function zedea_autoload($classname) {
 
 	$pathToClass=((_ZEDEA_CLASSPATH_).$classname.'.class.php');	
 
 	if (!file_exists($pathToClass)) {
 	 	if (class_exists('ErrorHandler',FALSE)) {
 	 	 	ErrorHandler::Err(('_ErrCode_RsrcNotFound_'),' could not load class <i>'.$classname.'</i>',E_USER_WARNING);
 	 	}
 	 	else {
		   trigger_error("Could not load class $classname", E_USER_WARNING);
		}
 	 	return FALSE;
 	}
 	require_once($pathToClass);
 
 
}

spl_autoload_register('zedea_autoload');


// ---

?>