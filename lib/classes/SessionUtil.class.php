<?php

class SessionUtil {
 
 
 		static function startSession() {
 		 	if (!defined('__AppSessSalt__')) {
 		 	 	define('__AppSessSalt__','abcDEFghiJKLmnoPQRstuVWXyz=');
 		 	}
 		 	
 		 	session_start();
 		}
 		
 		static function endSession() {
 		 	session_destroy();
 		 	unset($_SESSION);
 		 	session_start();
 		}
 
 		static function hasSession() {
 			return ArrayUtil::getValue($_SESSION,'id',FALSE);		 	
 		}
 
 		static function hasUser() {
 		 	$uid= ArrayUtil::getValue($_SESSION,'uid',FALSE);
 		 	//return (CastUtil::isNullOrEmpty($uid)) ? FALSE : TRUE;
 		 	return (CastUtil::toBoolean($uid));
 		 
 		}
 
 		static function hasUserToken() {
 		 	if (SessionUtil::getValue('uToken',FALSE)) {
 		 	 	return TRUE;
 		 	}
 		 	if (RequestUtil::hasParam('_uToken')) {
 		 	 	$uToken=RequestUtil::getParam('_uToken');
 		 	 	SessionUtil::setValue('uToken',$uToken);
 		 	 	return TRUE;
 		 	}	
 		 	return FALSE;
 		 
 		}
 
 		static function bindUser($uid='') {
 		 	if (CastUtil::isNullOrEmpty($uid)) return FALSE;
 		 	SessionUtil::setValue('uid',$uid);
 		 	SessionUtil::onStateChange();
 		 	
 		}
 		
 		static function unbindUser() {
 		 	SessionUtil::setValue('uid',FALSE);
 		 	SessionUtil::endSession();
 		 	SessionUtil::onStateChange();
 		 	
 		}
 		
 		static function onStateChange() {
 		 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
 		}
 		
 		static function getUID() {
 		 	$uid=ArrayUtil::getValue($_SESSION,'uid',FALSE);
 		 	return $uid;
 		}
 		
 		static function getUser() {
 		 	if (!SessionUtil::hasUser()) {
 		 		return 'Anonymous'; 
 		 	}
 		 	
 		 	if (RequestUtil::hasUser()) {
 		 	 	return 'Anonymous::AuthName:'.RequestUtil::getUserName();
 		 	}
 		 	
 		 	return 'Anonymous::IP:'.RequestUtil::getUserIP();
 		 	
 			
 		}
 		
 		static function getLanguage() {
 			return ArrayUtil::getValue($_SESSION,'lang',FALSE);		 
 		}
 		
 		static function getValue($key='',$default='') {
 			return ArrayUtil::getValue($_SESSION,$key,$default);		 
 		}
 		static function setValue($key='',$value='') {
 		 	if (!empty($key)) {
 		 		$_SESSION[$key]=$value;
			}		 
 		}
 	
 	
 		static function getDecryptedValue($key='',$default='') {
 		 	$encryptedKey=SessionUtil::doEncrypt($key);
 		 	$encryptedDefault=SessionUtil::doEncrypt($default);
 			$value= ArrayUtil::getValue($_SESSION,$encryptedKey,$encryptedDefault);
			return SessionUtil::doDecrypt($value);		 
 		}
 
 		static function setEncryptedValue($key='',$value='') {
 		 	$encryptedKey=SessionUtil::doEncrypt($key);
 		 	$encryptedValue=SessionUtil::doEncrypt($value);
 			if (!empty($encryptedKey)) {
 		 		$_SESSION[$encryptedKey]=$encryptedValue;
			} 		 
 		}
 	
 		static function getSalt() {
 		 	$default=str_shuffle(constant('__AppSessSalt__'));
 		 	$salt=SessionUtil::getValue('__s',$default);
 		 	SessionUtil::setValue('__s',$salt);
 		 	return $salt;
 		}
 	
 		static function doEncrypt($string) {
 		 	$base= base64_encode($string);
 		 	$salt=SessionUtil::getSalt();
 		 	$search=constant('__AppSessSalt__');
 		 	$out=strtr($base,$search,$salt);
 		 	return $out;
 		}
 		static function doDecrypt($string) {
 		 	$salt=SessionUtil::getSalt();
 		 	$replace=constant('__AppSessSalt__');
 		 	$base=strtr($string,$salt,$replace);
 		 	$out= base64_decode($base);
 		 	return $out;
 		}
 
 
}

?>