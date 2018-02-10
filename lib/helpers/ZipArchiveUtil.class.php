<?php


class ZipArchiveUtil {
 	
 	var $zipArchive = null;
 	var $extractDir	= '.';
 	var $zipWriter	= null;
 	
 	function __construct($filename) {
 	 
 	 	$this->zipArchive = $filename;
 	}

	function open() {	 
	 	$zipfile	= $this->zipArchive;	
		$zip		= zip_open($zipfile);
		return $zip;
	}

	function close($zip) {
	 	zip_close($zip);
	}

	function listEntries() {
	 	$zip=$this->open();
	 	$out=array();
		while ($entry=zip_read($zip)) {
	 	 	$name=zip_entry_name($entry);
	 	 	$size=zip_entry_filesize($entry);
	 	 	$out[]=array('name'=>$name,'size'=>$size);
	 	 	
	 	}
	 	$this->close($zip);
	 	return $out;
	 
	}

	function extractAll($path='') {
	 
	}

	function extractFile($file='',$destPath='.',$fileName='',$override=TRUE) {
	 	
		$filePath=$destPath;
		if (!FileUtil::isWritableDir($filePath)) {
		 	return FALSE;
		}
	 	$fileName=(empty($fileName)) ? basename($file) : $fileName;
	 	$filePath.='/'.$fileName;
		
		if (FileUtil::isFile($filePath) && !$override) {
		 	return $filePath;
		} 	 	
	 
	 	$content=$this->getFromZip($file);
	 	if ($content) {
	 	 	if (FileUtil::writeFile($filePath,$content,TRUE)) {
	 	 	 	return $filePath;
	 	 	}
	 	}
	 	return FALSE;
	}



	function getFromZip($what='') {
	 
	 	$zip=$this->open();
	 	
		while ($entry=zip_read($zip)) {
	 	 	$name=zip_entry_name($entry);
	 	 	$size=zip_entry_filesize($entry);
	 	 	if (strstr($name,$what)) {
	 	 	 	zip_entry_open($zip,$entry,'rb+');
	 	 	 	$cont=zip_entry_read($entry,$size);
	 	 	 	$this->close($zip);
	 	 	 	return $cont;
	 	 	 
	 	 	}
	 	}
		$this->close($zip);
	 
	 
	}

	function zipExport() {
	 	$this->zipWriter=new zipWriter();
	}
	
	
	function addToZip($content,$filename) {
	 	if (!$this->zipWriter) {
	 	 	$this->zipExport();
	 	}
	 	$this->zipWriter->addFile($content,$filename);
	}

	

 
 
}


?>