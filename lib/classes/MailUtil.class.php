<?php

class MailUtil {

 

	var $eol=PHP_EOL; 
	
	var $MailSubject='';
 	var $MailMessage='';
 	var $MailMessageHTML='';
	var $MailHeader='';

	
	var $MailSender='';
	var $MailFrom='';
	
	var $MailAttachments=array();
	
	
	var $MimeBoundary='';
	var $InlineBoundary='';
 
 	var $Charset='UTF-8';
 	
 	var $Debug=FALSE;
 	
 	var $LogFile = FALSE;
 
 	function MailUtil() {
 		
 		$this->MimeBoundary='MIME'.md5(time());	
		$this->InlineBoundary='PARTS'.md5(time());	 
		
		// enable log
		$logPath = Loader::getPath('log',TRUE);
		 
		if (FileUtil::isWritableDir($logPath)) {
		 	$logFile = $logPath.'/mail.log';
		 	$this->LogFile=$logFile;
		}
 	 
 	}
 
 
 	function sendMail($to='',$subject='',$message='',$sender='',$from='') {
 	 
 	 	
 	 	$mailto=$this->rfc2047encode($to);
 	 	
 	 	$this->setSubject($subject);
 	 	$this->setMessage($message);
 	 	$this->setSender($sender);
 	 	if ($this->isMailAddr($from)) { $this->MailFrom=$from ; }
 	 	
 	 	$res=$this->sendMailTo($mailto);
 	 
 	 	return $res;
 	}
 
 
 	function isMailAddr($addr) {
 	 
 	 	if (preg_match("/([A-Z0-9\.\_\-]*\@[A-Z0-9\.\_\-]*)/",$addr)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 
	function rfc2047encode($string) {
	 
	 		$string=utf8_encode($string);
	 
			preg_match_all('/(\w*[\x80-\xFF]+\w*)/', $string, $matches);
			foreach ($matches[1] as $value) {
				$replacement = preg_replace('/([\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
				$string = str_replace($value, ' =?' . $this->Charset . '?Q?' . $replacement . '?= ', $string);
			}
	
			return $string;
	 
	}


	function setSender($address) {
	 
	 		$this->MailHeader.="\n".'From: '.$this->rfc2047encode($address);
	 		$this->MailHeader.="\n".'Reply-To: '.$this->rfc2047encode($address);
	 		
	 		$this->MailSender = $address;
	}

	function setCC($address) {
	 
	 		$this->MailHeader.="\n".'Cc: '.$this->rfc2047encode($address);
	}
	function setBCC($address) {
	 
	 		$this->MailHeader.="\n".'Bcc: '.$this->rfc2047encode($address);
	}


	function setSubject($string) {
	 
	 		$this->MailSubject=$this->rfc2047encode($string);
	}

	function setMessage($string) {
	 
	 		if ($this->isHTML($string)) {
	 		 	$this->setHTMLMessage($string);
	 		 	$string=$this->convertHTMLtoText($string);
	 		}
	 
	 		$string=stripslashes(strip_tags($string));
	 		$this->MailMessage=$string;
	}
	
	function setHTMLMessage($string) {
	 		$string=stripslashes($string);
	 		$this->MailMessageHTML=$string;
	}
	
	
	
	function isHTML($string) {
	 	
	 	if (strstr($string,'<') && strstr($string,'>')) {
	 	 	return TRUE;
	 	}
	 	return FALSE;
	 
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
	 	
	 	// strip double empty lines
	 	$out=preg_replace("/([\n|\s]{2,})/","$1",$out);
	 	
	 	$out=trim($out);
	 	
	 	return $out;
	 
	}


	function addAttachment($file) {
	 
	 	$attachment=array();
	 	if (file_exists($file)) {
	 	 	if (is_file($file)) {
		 	 	$attachment['file']=$file;
		 	 	$attachment['encoded']=$this->encodeAttachment($file);
		 	 	$this->MailAttachments[]=$attachment;
	 	 	}
	 	}	
	 
	}


	function sendMailTo($mailto) {
	 
	 	$mailParams=(!empty($this->MailFrom)) ? '-f'.$this->MailFrom : '';
	 	
	 	$this->formatMultipartMail();
	 	$this->MailHeader=trim($this->MailHeader);
	 	
	 	//var_dump(array($mailto,$this->MailSubject,$this->MailBody,$this->MailHeader,$mailParams));
	 	
	 	if ($this->Debug) {
	 	 	$this->printMail($mailto);
	 	 	return FALSE;	
	 	}
	 	else {
	 	 	if( ini_get('safe_mode') ) {
				$res=mail($mailto,$this->MailSubject,$this->MailBody,$this->MailHeader);
			}
			else {
	 			$res=mail($mailto,$this->MailSubject,$this->MailBody,$this->MailHeader,$mailParams);
	 		}
	 	}
	 	if ($res) {
	 	 	$this->log($this->MailSender,$mailto,$this->MailSubject,$this->MailBody,TRUE);
			return TRUE;  
		}	
		
		$this->log($this->MailSender,$mailto,$this->MailSubject,$this->MailBody,FALSE,'PHP mail() failed');
	 	return FALSE;
	 	
	}


	function printMail($mailto) {
	 	$out='';
	 	$out.='<div class="debug" style="border:solid 1px #d00;">';
	 	$out.='<div class="email">';
	 	$out.='<h2 style="color:#fff;background-color:#369;padding:0.5em">'.$this->MailSubject.'</h2>';
	 	$out.='<div class="email-header" style="background-color:#ccc;padding:1em;margin-bottom:1em;">';
	 	$out.='<p>To:'.$mailto.'</p>';
	 	$out.='<p>ReturnPath:'.htmlentities($this->MailFrom).'</p>';
	 	$out.='<p>Header:'.htmlentities($this->MailHeader).'</p>';
	 	$out.='</div>';
	 	$out.='<div class="email-body-encoded" style="background-color:#eee;font-family:monospace;">';
	 	$out.=nl2br($this->MailBody);
	 	$out.='</div>';
	 	$out.='<div class="email-body-plain" style="border:solid 1px #ccc;padding:1em;">';
	 	$out.=nl2br(htmlentities($this->MailMessage));
	 	$out.='</div>';
	 	$out.='</div>';
	 	$out.='</div>';
	 	echo $out;
	 	
	 
	}

 
	


 
 
 	function formatMultipartMail() {
	 
			if (!strstr($this->MailHeader,'Content-Type')) {	 
				$this->MailHeader .= "\nMIME-Version: 1.0\n" ."Content-Type: multipart/mixed;\n" ." boundary=\"{$this->InlineBoundary}\"";
			}
			
			$message='';	

			$content= "This is a multi-part message in MIME format.\n\n";

			//  Plain Text
			$content.="--{$this->InlineBoundary}\n"
				."Content-Type:text/plain;\n"
				." charset=\"utf-8\"\n"
				."Content-Transfer-Encoding: base64\n\n";
				
				$message.=$this->MailMessage;
				$content.=$this->encodeMessage($message);

			//  Attachments
			for ($a=0;$a<count($this->MailAttachments);$a++) {
				$att=$this->MailAttachments[$a];
				$attdata=$att["encoded"];
				$content.=$attdata;

			}

			$content.= "\n\n--{$this->InlineBoundary}--\n";

			$this->MailBody=$content;
	 	
	 	
	 
	 
	}


	function encodeMessage($text) {
	 
	 	$out=strip_tags($text);
	 	$out=stripslashes($out);
	
	 	//$out=utf8_encode($out);
	 	$out=CharConvUtil::mixedToUTF8($out);
	 		
		$out=chunk_split(base64_encode($out));
	 
	 	return $out;
	 
	}

	function encodeAttachment($file,$alias='') {

		unset ($attachment);
		if (!file_exists($file)) {
		 	return FALSE;
		}
		
		$fp=fopen($file,"rb");
		$data=fread($fp,filesize($file));
		fclose($fp);
		
		
		$mimetype= $this->getFileMimeType($file);  
		$filename=basename($file);
		$alias=empty($alias) ?  $filename : $alias;
		
		

		if (!empty($data)) {
			$attachmentdata = chunk_split(base64_encode($data));
			

			$attachment= "\n\n--{$this->InlineBoundary}\n" .
					"Content-Type:".$mimetype.";\n" .
					" name=\"".$filename."\"\n" .
					"Content-Disposition:inline;\n" .
					" filename=\"".$filename."\"\n" .
					"Content-Transfer-Encoding: base64\n" .
					"Content-ID: <".$alias.">\n\n" .
					$attachmentdata;

		}
		return $attachment;


	}
 
 
 
 	

    function getFileMimeType($file) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

		$finfo=pathinfo($file);
        $ext = strtolower($finfo['extension']);
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $file);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
    
    
    function log($from,$to='',$subject='',$msg='',$success=FALSE,$errInfo=FALSE) {
	 
	 	$logFile = $this->LogFile;
	 	if (!$logFile) {
	 	 	return;
	 	}
	 	
	 	$logEntry="\n-- SENDMAIL --------------------- \n";
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