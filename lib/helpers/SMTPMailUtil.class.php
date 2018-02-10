<?php

class SMTPMailUtil {
 
 	var $Config = array();
 	var $Mailer = null;
 	var $Alerts = '';
 	var $LogFile = FALSE;
 	
 	var $PluginDir = FALSE;
 
 	function __construct($config=array(),$dir=FALSE,$logs=TRUE)  {
 	 
 		$this->Config = $config;	
		//date_default_timezone_set('Etc/UTC'); 
		
		if ($dir) {
		 	$this->PluginDir = $dir;
		}
		else {
		 	$this->PluginDir = Loader::getPath('lib.extensions.phpmailer',FALSE);
		 	$this->PluginDir.='/';
		}
		
		
	
		if ($logs) {
		 	$logPath = Loader::getPath('log',TRUE);
		 
		 	if (FileUtil::isWritableDir($logPath)) {
		 		$logFile = $logPath.'/mail_smtp.log';
		 		$this->LogFile=$logFile;
		 	}
		}
		
	
		$this->initMailer();				
 
 	}
 
 	function initMailer() {
 	 
 	 	require $this->PluginDir.'class.phpmailer.php';
		$this->Mailer = new PHPMailer();
 	 
 	 	$this->Mailer->PluginDir = $this->PluginDir;
		//Tell PHPMailer to use SMTP
		$this->Mailer->IsSMTP();
		
		$this->Mailer->XMailer='zedea SMTP/PHPMailer ';

		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->Mailer->SMTPDebug  = 0;
		
		//Ask for HTML-friendly debug output
		$this->Mailer->Debugoutput = 'html';

		//Set the hostname of the mail server
		$this->Mailer->Host       = ArrayUtil::getValue($this->Config,'SMTP_Host','smtp.localhost');
		//Set the SMTP port number - likely to be 25, 465 or 587
		$this->Mailer->Port       = ArrayUtil::getValue($this->Config,'SMTP_Port',465);
		//Whether to use SMTP authentication
		$this->Mailer->SMTPAuth   = true;
		$this->Mailer->SMTPSecure = "ssl";   
		$this->Mailer->AuthType   = 'PLAIN';
		
		//Username to use for SMTP authentication
		$this->Mailer->Username   = ArrayUtil::getValue($this->Config,'SMTP_User',FALSE);
		//Password to use for SMTP authentication
		$this->Mailer->Password   = ArrayUtil::getValue($this->Config,'SMTP_Pass',FALSE);
 	 
 	 
 	}

	function sendMail($to,$subject,$body,$from) {
	 	$this->sendSMTPMail($to,$subject,$body,$from,$from);
	}


	function sendSMTPMail($to,$subject,$msg,$from,$replyto) {

		$mail = $this->Mailer; 	
	
		$mail->CharSet = 'utf-8';
		$mail->Encoding = 'base64';
		
		$this->setAddr($to,'To');
		$this->setAddr($from,'From');
		$this->setAddr($replyto,'Reply-To');	
		
		$mail->Subject = $subject;
		
		// set a plain body
		$mail->Body = $this->convertHTMLtoText($msg);
	
		
		// use phpmailer HTML
		//$mail->MsgHTML($msg);
		
		//var_dump($mail);
	 
	 
	 	if(!$mail->Send()) {
  			$this->Alerts= "Mailer Error: " . $mail->ErrorInfo;
  			$this->log($from,$to,$subject,$mail->Body,FALSE,$mail->ErrorInfo);
  			return FALSE;
		} else {
  			$this->Alerts= "Message sent!";
  			$this->log($from,$to,$subject,$mail->Body,TRUE);
  			return TRUE;
		}
	
		
	}
	
	
	function setAddr($addr,$what='To') {
	 	$mailer = $this->Mailer;
	 	
	 	$addrAddr 	= $addr;
	 	$addrName	= '';
	 	if (preg_match('/([^<]*)<([^>]*)>/',$addr,$matches)) {
	 	 	$addrName = trim($matches[1]);
	 	 	$addrAddr = trim($matches[2]);
	 	}
	 	
	 	switch ($what) {
	 	 	case 'To' :
	 	 				$mailer->AddAddress($addrAddr,$addrName);
	 	 				break;
	 	 	case 'From' :
	 	 				$mailer->SetFrom($addrAddr,$addrName);
	 	 				break;
	 	 	case 'Reply-To' :
	 	 				$mailer->AddReplyTo($addrAddr,$addrName);
	 	 				break;
	 	 	default :
	 	 				$mailer->AddAddress($addrAddr,$addrName);
	 	 				break;
	 	 
	 	}
	 	
	 	
	}
	
	
		
	function convertHTMLtoText($string) {
	
	 	$out=$string;
	
		//add whitespace before and after tag
		$out=str_replace(array('<','>'),array(' <','> '),$out);
	
		$blockElements=array(
								'div','br','p',
								'table','tr', 
								'ul','ol','dl',
								'li',
								'dd','dt',
								'h1','h2','h3','h4','h5','h6'
								);
		$blockElementsReplace=array();
		for ($b=0;$b<count($blockElements);$b++) {
		 	$blockElements[$b]='<'.$blockElements[$b];
		 	$blockElementsReplace[]="\n".$blockElements[$b];	
		}
		 	
	 	$out=str_replace($blockElements,$blockElementsReplace,$out);
	 	$out=strip_tags($out);
	 	
	 	// strip double empty lines
	 	$out=preg_replace("/([\n|\s]{2,})/","$1",$out);
	 	
	 	$out=trim($out);
	 	
	 	return $out;
	 
	}
	
	
	function log($from,$to='',$subject='',$msg='',$success=FALSE,$errInfo=FALSE) {
	 
	 	$logFile = $this->LogFile;
	 	if (!$logFile) {
	 	 	return;
	 	}
	 	
	 	$logEntry="\n-- SMTP --------------------- \n";
	 	$logEntry.=strftime(' - %d.%m.%Y %H:%M:%S - ');
	 	$logEntry.=($success) ? ' 200 - SENT ' : ' 500 - ERROR - '.$errInfo; 
	 	$logEntry.="\n";
	 	$logEntry.=' - From: '.$from;
	 	$logEntry.=' - To: '.$to;
	 	$logEntry.="\n";
	 	$logEntry.=' - Subject: '.$subject;
	 	$logEntry.="\n";
	 	$logEntry.=' - Msg (compressed): '. base64_encode(gzcompress($msg));
	 	
		$fp=fopen($logFile,'ab+');
		fwrite($fp,$logEntry);
		fclose($fp);
		return;	 	
	 	
	 
	}


}



?>