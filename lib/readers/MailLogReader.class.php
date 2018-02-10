<?php

class MailLogReader {

	var $LogDir 		= FALSE;
	var $LogFiles  		= array( 
						'sendmail' 	=> 'mail.log',
						'smtp'		=> 'mail_smtp.log',
					);
					
	var $showFullMsg 	= FALSE;
	var $showSource 	= FALSE;
	
	var $Mode 			= 'sendmail';
	
 
 	function __construct() {
		
		$localLogDir=Loader::getPath('log',TRUE);
		if (FileUtil::isDir($localLogDir)) {
		 	$this->LogDir=$localLogDir;
		}	  
		else {
		 	die ('Logfiles k&ouml;nnen nicht gelesen werden. Das Verzeichnis existiert nicht.');
		}  
	}
	
	function client() {
		
		$m = RequestUtil::getParam('m',FALSE);
		$which = 'sendmail';
		if ($m=='smtp') {
		 	$which='smtp';
		}
		$this->Mode = $which;
		
		$w = RequestUtil::getParam('wFM',FALSE);
		if ($w) {
		 	$this->showFullMsg=TRUE;
		}
		
		$s = RequestUtil::getParam('sLS',0);
		if ($s==1) {
		 	$this->showSource=TRUE;
		}
		
		
		$this->showControls();
		
		$this->showLog($which);
	 	
	
	 
	}
	
	
	function showControls() {
	 	$autosubmit=' onchange="this.form.submit();" ';
	 
	 	$out = '<div id="controls">';
	 	$out.= '<form method="GET" action="">';
	 	$out.='<fieldset>';
	 	$out.='<select  class="input" '.$autosubmit.' name="m" size="1">';
	 	$checked = ($this->Mode=='sendmail') ?  ' selected="selected" ' : ''; 	
	 	$out.='<option value="" '.$checked.'>Standard-Log (sendmail)</option>';
		$checked = ($this->Mode=='smtp') ?  ' selected="selected" ' : ''; 	
	 	$out.='<option value="smtp" '.$checked.'>SMTP-Log</option>';
	 	$out.='</select>';
	 	$out.='</fieldset>';
	
	 	$out.='<fieldset>';
	 	$out.='<select class="input" '.$autosubmit.' name="sLS" size="1">';
	 	$checked = (!$this->showSource) ?  ' selected="selected" ' : ''; 	
	 	$out.='<option value="0" '.$checked.'>Daten aufbereiten</option>';
		$checked = ($this->showSource) ?  ' selected="selected" ' : ''; 	
	 	$out.='<option value="1" '.$checked.'>Original Logfile anzeigen</option>';
	 	$out.='</select>';
	 	$out.='</fieldset>';
	 	
	 	$out.='<fieldset>';
		$checked = ($this->showFullMsg) ?  ' checked="checked" ' : ''; 	
	 	$out.='<input '.$autosubmit.' type="checkbox" name="wFM" value="1" '.$checked.'/><span title="nur bei Funktion &#34;Daten aufbereiten&#34;">Nachrichtentext anzeigen</span>';
	 	$out.='</fieldset>';
	 	$out.='</form>';
	 	$out.='</div>';
	 	
	 	echo $out;
	 
	}
	
	
	function showLog($which='sendmail') {
	 	$logFile = ArrayUtil::getValue($this->LogFiles,$which,'sendmail');
	 	$logPath = $this->LogDir.'/'.$logFile;
	 	
	 	$content = FileUtil::getContent($logPath);
	 	
	 	if ($this->showSource) {
	 	
			$out='<textarea class="source">'; 
	 		$out.=htmlentities($content);
	 		$out.='</textarea>';
	 		echo $out;
	 		return;
	 	}
	 	
	 	$items = $this->parse($content);
	 	
	 	
	 	$tmpl = '<div class="%%wrapclass%%"><h3 class="subject">%%subject%%</h3><div class="addr">%%address%%</div><div class="status">%%status%%</div><div class="msg">%%msgplain%%</div></div>';
	 	
	 	$out = RenderUtil::renderByTemplate($items,$tmpl);
	 	
	 	echo $out;
	 	
	} 
	
	function parse($content) {
	 	$logItems = explode("\n--",$content);
	 	$items = array();
	 	
	 	foreach ($logItems as $num=>$logItem) {
	 	 	$parts = explode("\n -",$logItem);
	 	 	$status = ArrayUtil::getValue($parts,'1','---');
	 	 	$addr	= ArrayUtil::getValue($parts,'2','---');
	 	 	$subject = ArrayUtil::getValue($parts,'3','---');
	 	 	$msginfo = ArrayUtil::getValue($parts,'4','---');
	 	 	
	 	 	$isErr = (strstr($status,'ERROR')) ? TRUE : FALSE;
	 	 	
	 	 	$msgplain = $this->extractTheMessage($msginfo);
	 	 	
	 	 	
	 	 	$item = array(
	 	 			'status' => $status,
	 	 			'address'	=> htmlentities($addr),
	 	 			'subject'	=> $subject,
	 	 			'msginfo' 	=> $msginfo,
	 	 			'wrapclass' => ($isErr) ? 'error' : 'success',
		 	);
		 	
		 	if ($this->showFullMsg) {
		 	 	$item['msgplain'] = utf8_encode($msgplain);
		 	}
		 	
		 	if ($status!='---') {
		 		$items[] = $item;
		 	}
		 	
		}
		rsort($items);
		return $items;
	 
	}
	
	function extractTheMessage($logline) {
	 	$line=trim($logline);
	 	$rawmsg=substr($line,strrpos($line,' '),strlen($line));
	 	
	 	$decoded = base64_decode($rawmsg);
	 	$uncompr = gzuncompress($decoded);
	 	
	 	return $uncompr;
	}
	
 
}


?>