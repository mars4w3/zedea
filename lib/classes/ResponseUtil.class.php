<?php

class ResponseUtil {
	
 
 	static function sendHeader($mimetype,$headers=array())  {
	  	
	  	ResponseUtil::clean();
	  	ResponseUtil::setheader('Content-Type:'.$mimetype.'; charset=UTF-8;');
		ResponseUtil::setheader('Accept-Charset: UTF-8');
	  	
	  	if (is_array($headers)) {
			
			foreach ($headers as $key=>$val) {
			 
			 	switch ($key) {
			 	 
			 	 	case 'filename' : 
			 	 				ResponseUtil::setheader("Content-Description: File Transfer");
								ResponseUtil::setheader("Content-Disposition: attachment; filename=".$val);
								break;
								
					case 'image' : 
			 	 				ResponseUtil::setheader("Content-Description: File Transfer");
								ResponseUtil::setheader("Content-Disposition: inline; filename=".$val);
								break;
								
					case 'nocache' : 
								ResponseUtil::setheader("Cache-Control: must-revalidate, post-check=0, pre-check=0");
								//ResponseUtil::setheader("Cache-Control: no-cache");
								break;
											
					default:
								ResponseUtil::setheader($key.': '.$val);
								break;
			 	 						
			 	 
			 	}
			}
	  	}
	} 

	static function setheader($string) {
	 	$out=ucfirst($string);
	 	header($out);
	 
	}
	static function clean() {
	 	
	 	//ob_end_flush();
	 	ob_end_clean();
	 	
	}

	static function redirect($url) {
	 	ob_end_clean();
	 	header('Location: '.$url);
	 	
	}	
	
	

	
	
	
}

?>