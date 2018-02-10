<?php

set_error_handler('ErrorHandler::PHPError');

class ErrorHandler {
 
  
  	static function PHPError($errno, $errstr, $errfile, $errline)
	{
	    if (!(error_reporting() & $errno)) {
	        // This error code is not included in error_reporting
	        return;
	    }
	
		$msg='';
		
		$errfile=str_replace(array('\\','/'),'/&#8203;',$errfile);
		
		$msgExt = ' in <em>'.$errfile.'</em> on line <em>'.$errline.'</em>';
	    switch ($errno) {
	    // user defined errors 
	    case E_USER_ERROR:
	        $msg.= "<b>ERROR</b> [$errno] $errstr<br />\n";
	        $msg.= "  Fatal error on line $errline in file $errfile";
	        $msg.= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
	        $msg.= "Aborting...<br />\n";
	        exit(1);
	        break;
	
	    case E_USER_WARNING:
	        $msg.= "<b>USER WARNING</b> [$errno] $errstr<br />\n";
	        break;
	
	    case E_USER_NOTICE:
	        $msg.= "<b>USER NOTICE</b> [$errno] $errstr<br />\n";
	        break;
	
		// real PHP errors        
	    case E_NOTICE:
	        $msg.= "<b>NOTICE</b> [$errno] $errstr $msgExt<br />\n";
	        break;
	    
		case E_WARNING:
	        $msg.= "<b>WARNING</b> [$errno] $errstr $msgExt<br />\n";
	        break;
	        
	    case E_STRICT:
	        $msg.= "<b>STRICT</b> [$errno] $errstr $msgExt<br />\n";
	        break;
	
		// unknown
	    default:
	        $msg.= "Unknown error type: [$errno] $errstr $msgExt<br />\n";
	        ErrorHandler::Err($errno,$msg,0);
	        ErrorHandler::abort();
	        exit(1);
	        break;
	    }
	
		ErrorHandler::Err($errno,$msg,0);
	  
	    return TRUE;
	}
  
  
  
  
  	static function Err($num=0,$msg='',$level=0) {
  	 
  		//var_dump($num,$msg,$level);	 
  		ErrorHandler::logRuntime($msg);
  		
  		if ($level==-1) {
  		 	//trigger_error($msg,$level);
  		 	ErrorHandler::abort();
  		}
  	}


	static function throwException($class='',$method='',$msg='') {
	 	$errNum=0;
	 	$errMsg='Class: <strong>'.$class.'</strong> Method: <strong>'.$method.'</strong> throws Exeption: <em>'.$msg.'</em> ';
	 	ErrorHandler::Err($errNum,$errMsg);
	 
	}
	
	static function throwDebugMsg($class='',$method='',$msg='') {
	 	$errNum=0;
	 	$errMsg='<strong>DEBUG</strong> '.$class.' ('.$method.') | Message: <em>'.$msg.'</em> ';
	 	ErrorHandler::Err($errNum,$errMsg);
	 
	}
	
	static function dump($class='',$method='',$var=null) {
	 	$errNum=0;
	 	$errMsg='<strong>DUMP</strong> '.$class.' ('.$method.') | Var: <em>'.var_export($var,TRUE).'</em> ';
	 	ErrorHandler::Err($errNum,$errMsg);
	 
	}


	static function logRuntime($msg) {
	 
	 	global $_ApplicationStackTrace;
	 	if (!is_array($_ApplicationStackTrace)) {
	 	 	$_ApplicationStackTrace=array();
	 	 
	 	}
	 
	 	$_ApplicationStackTrace[]=array(
		 	'time'=>microtime(),
		 	'msg'=>$msg
		);	
	 
	}


	static function stackTrace() {
	 
	 	$Runtime=ErrorHandler::getRuntime();
	 	ErrorHandler::dump(__CLASS__,__METHOD__,'Runtime:'.$Runtime);
	 
	 	global $_ApplicationStackTrace;
	 	if (!is_array($_ApplicationStackTrace)) {
	 	 	return FALSE;
	 	}
	 	
	 	
	 	$out='<div id="ErrStackTrace" style="text-align:left;font-size:0.8em;">';
	 	$out.='<h3>StackTrace</h3>';
	 	$out.='<dl>';
	 	
	 	foreach ($_ApplicationStackTrace as $item) {
	 	 	$out.="\n";
	 		$out.='<dt><code>'.DateTimeUtil::timeMilli(ArrayUtil::getValue($item,'time')).'</code></dt>'	;
			$out.='<dd><code>'.ArrayUtil::getValue($item,'msg').'</code></dd>';  	
	 	 
	 	}
	 
	 	$out.='</dl>';
	 	$out.='</div>';
		return $out;
	 
	}


	static function abort() {
	 	ob_end_clean();
	 	ErrorHandler::errorPage();
	 	die();
	}
	
	
	static function errorPage($code=500) {
	 	echo '<h1>Error '.$code.'</h1>';
	 	echo ErrorHandler::stackTrace();
	 
	}
	
	
	static function getRuntime() {
	 	if (!defined('__FWRuntimeStartTime')) {
	 	 	return 'No Info: starttime has not been set';
	 	}
	 	$start	= constant('__FWRuntimeStartTime');
	 	$now	= microtime();

	 	
	 	$diff	= DateTimeUtil::timeMilliSec($now)-DateTimeUtil::timeMilliSec($start);
	 	
	 	$info	= 'start: '.(DateTimeUtil::timeMilli($start));
	 	$info	.= '| end: '.(DateTimeUtil::timeMilli($now));
	 	$info	.= '| '.($diff).'sec';
	 	
	 	return $info;
	 
	}

}

?>
