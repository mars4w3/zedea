<?php

class HTMLMailUtil extends MailUtil {
 
 
 	var $InlinePartBoundary = '';
 	var $MailMessagePlain	= '';
 	var $MailMessageHTML	= '';
 	
 	function __construct() {
 	 	$this->InlinePartBoundary='SUBPARTS'.md5(time());
 	 	parent::MailUtil();
 	 
 	}
 	
 	function setMessage($string) {
	 
	 		$this->setHTMLMessage($string);
	 		$string=$this->convertHTMLtoText($string);
	 
	 		$string=stripslashes(strip_tags($string));
	 		$this->MailMessagePlain=$string;
	}
 
  	function sendHTMLMail($to='',$subject='',$message='',$sender='',$from='') {
 	 
 	 	
 	 	$mailto=$this->rfc2047encode($to);
 	 	
 	 	$this->setSubject($subject);
 	 	$this->setMessage($message);
 	 	$this->setSender($sender);
 	 	if ($this->isMailAddr($from)) { $this->MailFrom=$from ; }
 	 	
 	 	$res=$this->sendMailTo($mailto);
 	 
 	 	return $res;
 	}
 
	function encodeHTMLMessage($text) {
	 
	 	$out=stripslashes($text);
	 	$out=CharConvUtil::mixedToUTF8($out); 		
		$out=chunk_split(base64_encode($out));
	 
	 	return $out;
	 
	}
 
 
 	function formatMultipartMail() {
	 
			if (!strstr($this->MailHeader,'Content-Type')) {	 
				$this->MailHeader .= "\nMIME-Version: 1.0\n" ."Content-Type: multipart/mixed;\n" ." boundary=\"{$this->InlineBoundary}\"";
			}
			
			$message='';	

			$content= "This is a multi-part message in MIME format.\n\n";

			// Multipart Alternative
			$content.="--{$this->InlineBoundary}\n"
				."Content-Type: multipart/alternative;\n"." boundary=\"{$this->InlinePartBoundary}\"";
				
				//  Plain Text
				$content.="\n\n--{$this->InlinePartBoundary}\n"
					."Content-Type:text/plain;\n"
					." charset=\"utf-8\"\n"
					."Content-Transfer-Encoding: base64\n\n";
					
					$message=$this->MailMessagePlain;
					$content.=$this->encodeMessage($message);
	
	
				//  HTML Text
				$content.="\n\n--{$this->InlinePartBoundary}\n"
					."Content-Type:text/html;\n"
					." charset=\"utf-8\"\n"
					."Content-Transfer-Encoding: base64\n\n";
					
					$message=$this->MailMessageHTML;
					$content.=$this->encodeHTMLMessage($message);
	
			$content.= "\n\n--{$this->InlinePartBoundary}--\n";


			//  Attachments
			for ($a=0;$a<count($this->MailAttachments);$a++) {
				$att=$this->MailAttachments[$a];
				$attdata=$att["encoded"];
				$content.=$attdata;

			}

			$content.= "\n\n--{$this->InlineBoundary}--\n";

			$this->MailBody=$content;
	 	
	 	
	 
	 
	}
	
}
	
?>