<?php

class Communicator {
 
 
 
  	static function sendPlainMessage($args=array()) {
  	 
  	 	$mailto='';
  	 	$mailfrom='';
		$mailsubject='';
		$mailbody='';
  	 
  	 	$messagedata=ArrayUtil::getValue($args,'messagedata',array());
  	 	$keys=ArrayUtil::getKeys($messagedata);
  	 	$vals=ArrayUtil::getValues($messagedata);
  	 	$mailtemplate=ArrayUtil::getValue($args,'mailtemplate',FALSE);
  	 	if ($mailtemplate) {
  	 
			$mailto=ArrayUtil::getValue($mailtemplate,'To',$mailto);
			$mailsubject=ArrayUtil::getValue($mailtemplate,'Subject',$mailsubject);
			$mailfrom=ArrayUtil::getValue($mailtemplate,'From',$mailfrom);
			$bodytemplate=ArrayUtil::getValue($mailtemplate,'Body',$mailbody);
			if (!empty($messagedata)) {
				$mailbody=TextParser::replace($bodytemplate,$keys,$vals,'%%');
			}
		}
		// Override
		$mailto=ArrayUtil::getValue($args,'mailto',$mailto);
		
		
		$Mailer=new MailUtil();
		//$Mailer->Debug=TRUE;
	 	return $Mailer->sendMail($mailto,$mailsubject,$mailbody,$mailfrom);
 	 
 	}
 
 


 	static function email($mailTo='',$mailSubject='',$mailBody='',$mailFrom='',$mailSender='',$attachments=array()) {
 	  	 
 	 	$res=FALSE;
 	 	 	 
 	 	$util=new MailUtil();
 	
 		if (is_array($attachments)) {
 		 	foreach($attachments as $attFile) {
 		 		$myMailUtil->addAttachment($attFile);
 		 	}
 		}
		
		$res=$util->sendMail($mailTo,$mailSubject,$mailBody,$mailFrom,$mailSender) ;
					
 	 	
 	 	if ($res) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 	
 	
 	
 	

}



?>