<?php

class FileUtil {

	static function open($path,$mode='rb') {
	 	if ($handle=fopen($path,$mode)) {
	 	 	return $handle;
	 	}
	 	return FALSE;
	} 
 
 	static function close($handle) {
 	 	if (fclose($handle)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 
 
 	static function isFile($path) {
 	 	
 	 	if (!file_exists($path)) {
 	 	 	return FALSE;
 	 	}
 	 	if (is_dir($path)) {
 	 	 	return FALSE;
 	 	}
 	 	return TRUE;
 	}
 
 	static function isReadableFile($path) {
 	 	if (!FileUtil::isFile($path)) {
 	 	 	return FALSE;
 	 	}
 	 	if (is_readable($path)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 
 	static function isWritableFile($path) {
 	 	if (!FileUtil::isFile($path)) {
 	 	 	return FALSE;
 	 	}
 	 	if (is_writable($path)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 
 	static function isDir($path) {
 	 	if (!is_dir($path)) {
 	 	 	return FALSE;
 	 	}
 	 	return TRUE;
 	}
 	
 	static function isWritableDir($path) {
 	 	if (!FileUtil::isDir($path)) {
 	 	 	return FALSE;
 	 	}
 	 	if (is_writable($path)) {
 	 	 	return TRUE;
 	 	}
 	 	return FALSE;
 	}
 	
 	
 	static function createDir($path,$dirname='') {
 	 	if (!FileUtil::isWritableDir($path)) {
 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Dir '.$path.' not writable');
 	 	 	return FALSE;
 	 	}
 	 	if (empty($dirname)) {
 	 	 	return FALSE;
 	 	}
 	 	if (FileUtil::isWritableDir($path.'/'.$dirname)) {
 	 	 	return TRUE;
 	 	}
 	 	if (strstr($dirname,'/')) {
 	 	 	$dirs=explode('/',$dirname);
 	 	 	for ($d=0;$d<count($dirs);$d++) {
 	 	 	 	$subdir=$dirs[$d];
 	 	 	 	if (FileUtil::createDir($path,$subdir)) {
 	 	 	 	 	$path.='/'.$subdir;
 	 	 	 	}
 	 	 	 	else {
 	 	 	 	 	return FALSE;
 	 	 	 	}
 	 	 	}
 	 	 	return TRUE;
 	 	}
 	 	else {
 	 	 	$newdir=$path.'/'.$dirname;
 	 	 	if (mkdir($newdir,0777)) {
 	 	 		chmod($newdir,0777);
 	 	 		return TRUE;
 	 	 	}
 	 	}
 	 	return FALSE;
 	 
 	}
 
 
 	static function getContent($path) {
 	 	if (!FileUtil::isReadableFile($path)) {
		 	return FALSE;  
		}	
 	 
 	 	if ($content=file_get_contents($path)) {
 	 	 	return $content;
 	 	}
 	 	return FALSE;
 	 
 	}
 	
 	static function getContentFromURL($url) {
 		$mode=FALSE;
 		if (ini_get('allow_url_fopen')==1) {
 		 	$mode='urlfopen';
 		}
 		else if (function_exists('curl_init')) {
 		 	$mode='curlopen';
 		}
 		else {
 		 	$mode='fsockopen';
 		}
 
 		switch ($mode) {
 		 	case  'urlfopen' :
			  					if ($content=file_get_contents($url)) {
 	 	 							return $content;
 	 							} 
 	 							else {
 	 							 	return FALSE;
 	 							}
 	 		case  'curlopen' :
 	 							$content=FALSE;
 	 							$ch=curl_init();
 	 							if ($curl) {
 	 								curl_setopt($ch, CURLOPT_URL, $url);
 									curl_setopt($ch, CURLOPT_HEADER, FALSE);
 									curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
 									curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); 
 									$content=curl_exec($ch);
 									curl_close($ch);
 								}
 	 							
			  					if ($content) {
 	 	 							return $content;
 	 							}
 	 							else {
 	 							 	return FALSE;
 	 							}
 		 
 		}
	 	 
 	 	
 	 	return FALSE;
 	 
 	}
 	
 	
 	
 	
 	static function writeFile($path,$data,$create=FALSE) {
 	 	return FileUtil::write($path,$data,'wb',$create);
 	}
 	
	static function appendFile($path,$data,$create=FALSE) {
 	 	return FileUtil::write($path,$data,'ab',$create);
 	}
 	
 	
 	static function write($path,$data,$mode='wb',$create=FALSE) {
 	 
 	 	if ($create) {
		 	$mode.='+';  
		}
		$content=$data;
		
		if (!FileUtil::isFile($path)) {
		 	if (!$create) {
		 	 	return FALSE;
		 	}
		} 
		
		if (is_array($data)) {
		 	$content=CastUtil::serialize($data);
		}
			
	  	$handle=FileUtil::open($path,$mode);
	  	if (!$handle) {
	  	 	return FALSE;
	  	}
	  	if (fwrite($handle,$content)) {
	  	 	FileUtil::close($handle);
			return TRUE;   	
	  	}
	  	FileUtil::close($handle);
	  	return FALSE;
	  	 
	} 
 	
 	
 	static function fullpath($path) {
 	 	return realpath($path);
 	}
 	
 	
 	static function getDirItemType($path,$item) {
 	 	
 	 	if (is_dir($path)) {
 	 	 	$out='dir';
 	 	 	if ($item=='.') {
 	 	 	 	return 'self';
 	 	 	}
 	 	 	if ($item=='..') {
 	 	 	 	return 'parent';
 	 	 	}
 	 	}
 	 	if (is_file($path)) {
 	 	 	$out='file';
 	 	}
 	 	return $out;
 	}
 	
 	static function getDir($path='.',$recursive=FALSE,$exclude=array(),$opendir='') {
 	 
 	 	$out=array();
 	 	$handle=opendir($path);
 	 	
 	 	$excludeFilenames=ArrayUtil::getValue($exclude,'_names',FALSE);
 	 	$excludeFiletypes=ArrayUtil::getValue($exclude,'_types',FALSE);
 	 	
 	 	while ($item=readdir($handle)) {
 	 	 	$itempath=FileUtil::fullpath($path._DS.$item);
 	 	 	$itemtype=FileUtil::getDirItemType($itempath,$item);
 	 	 	$itemsize=FileUtil::getSize($path._DS.$item);
 	 	 	$access='rwp';
 	 	 	
 	 	 	$showDir=$recursive;
 	 	 	if (!empty($opendir)) {
			    $openDirPath=FileUtil::fullpath($opendir);
				if (strstr($openDirPath,$itempath)) {
 	 	 	 		$showDir=TRUE;
 	 	 	 	}
 	 	 	}
 	 	 	
 	 	 	$info=array('_name'=>$item,'_type'=>$itemtype,'_path'=>$itempath,'_access'=>$access,'_children'=>array());
 	 	 	//$info=array('_name'=>$item,'_children'=>array());
 	 	 	
 	 	 	$include=TRUE;
 	 		if ($excludeFilenames) {
			 	$include=preg_match('/'.$excludeFilenames.'/',$item) ? FALSE : $include;  
			} 	
 	 	 	
 	 	 	if ($include) {
	 	 	 	if ($itemtype==='dir' && $showDir) {
	 	 	 	 	$info['_children']=FileUtil::getDir($itempath,$recursive,$exclude,$opendir);
	 	 	 	}
	 	 	 	if ($itemtype==='dir' || $itemtype==='file') {
	 	 	 		$out[]=$info;
	 	 	 	}
	 	 	 	
	 	 	}
 	 	 
 	 	}
 	
 	 	return $out;
 	 
 	}
 	
 	static function dumpDir($path='.',$recursive=FALSE,$options=array()) {
 	 	if (empty($options) || !is_array($options)) {
 	 	 	$options=array(
				'listTag'=>'ul',
				'listItemTag'=>'li',
				'listItemKeyTag'=>'strong',
				'listItemValueTag'=>'em',   
			);
 	 	}
 	 	$dir=FileUtil::getDir($path,$recursive);
 	 	$out=ArrayUtil::toHTML($dir,$options);
 	 	return $out;
 	}
 	
 	
 	static function handleUploads($uploadArgs=array(),$callbackCheckUpload='') {
 	 	$files=RequestUtil::filterParams('FILES');
 
 	 	foreach ($files as $file) {
 	 	 	
 	 	 	$moveUpload = TRUE;
 	 	 	if (!empty($callbackCheckUpload)) {
 	 	 		if (is_callable($callbackCheckUpload)) {
 	 	 	 		$moveUpload = call_user_func($callbackCheckUpload,$file);
 	 	 	 	}
 	 	 	}
 	 	 	if ($moveUpload) {
 	 	 		FileUtil::moveUpload($file,$uploadArgs) ;	
 	 	 	}
 	 		
 	 	}
 	 
 	 
 	}
 	
 	static function getUploadInfo($key='',$what='tmp_name') {
 	 	$files=RequestUtil::filterParams('FILES');
 	 	$file=ArrayUtil::getValue($files,$key,FALSE);
 	 	if ($file) {
 	 	 
 	 	 	$filepath=ArrayUtil::getValue($file,'tmp_name',FALSE);
 	 	 	
 	 	 	switch ($what) {
 	 	 	 
 	 	 	 	case 'error' 	: return ArrayUtil::getValue($file,'error',FALSE);
 	 	 	 	case 'data'		: $data=FileUtil::getContent($filepath); return $data;
 	 	 	 	case 'file'		: return $file;
 	 	 	 	default 		: 
								if (isset($file[$what])) {
		 							return $file[$what];
								}
								return $filepath;
 	 	 	 
 	 	 	}
 	 	 
 	 	}
 	 	return FALSE;
 	 	
 	 
 	}
 	
 	
 	static function moveUpload($file,$args=array()) {
 	 
 	 	if ($file['error']>0) {
 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Upload Error '.$file['error']);
 	 	 	return FALSE;
 	 	}
 	 
 	 	if (!defined('__ApplicationUploadPath__')) {
 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'No Upload-Dir defined');
 	 	 	return FALSE;
 	 	}
 	 
 	 	$destpath=constant('__ApplicationUploadPath__');
 	 	$folder=ArrayUtil::getValue($args,'folder');
 	 	$createIfNotExists=ArrayUtil::getValue($args,'createFolder',FALSE);
 	 	if (!empty($folder)) {
 	 	 	if (FileUtil::isWritableDir($destpath.'/'.$folder)) {
 	 	 	 	$destpath=$destpath.'/'.$folder;
 	 	 	}
 	 	 	else if ($createIfNotExists) {
 	 	 	 	if (FileUtil::createDir($destpath,$folder)) {
 	 	 	 		$destpath=$destpath.'/'.$folder;	 
 	 	 	 	}
 	 	 	}
 	 	}
 	 
 	 
 	 	$source=$file['tmp_name'];
 	 	$newfilename=FileUtil::getValidFilename($file['name']);
 	 	$prefix=ArrayUtil::getValue($args,'fileprefix','');
		if (!empty($prefix)) {
		 	$newfilename=$prefix.$newfilename;
		}  	
 	 	
 	 	if (ArrayUtil::getValue($args,'useTmpName',FALSE)===TRUE) {
 	 	 	$newfilename=FileUtil::getFileName($source);
 	 	}
 	 	
 	 	// override with given name
 	 	$overrideName = ArrayUtil::getValue($args,'useOldFileName',FALSE);
 	 	if ($overrideName && FileUtil::isFile($destpath.'/'.$overrideName)) {
 	 	 	$newfilename=$overrideName;
 	 	}
 	 	// ---
 	 	
 	 	
 	 	$dest=$destpath.'/'.$newfilename;
 	 	
 	 	if (move_uploaded_file($source,$dest)) {

 	 	 	return $dest;
 	 	}
 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Upload failed');
 	 	
 	 	return FALSE;
 	 
 	}
 	
 	
 	static function getValidFilename($name) {
 	 	$out=utf8_decode($name);
 	 	$search=array(' ','"',"'",'ä','ü','ö','Ä','Ö','Ü','ß');
 	 	$replace=array('_','','','ae','ue','oe','Ae','Oe','Ue','ss');
 	 	$out=str_replace($search,$replace,$out);
 	 	return $out;
 	}


	static function getFileExtension($filename,$default) {
	 	$info=pathinfo($filename);
	 	$extension=ArrayUtil::getValue($info,'extension',$default) ;
		return $extension;
	 	
	}

	static function getMimeType($file) {
	 	$mimetypes=Application::getCacheValue('MimeTypes');
	 	if (!is_array($mimetypes)) {
	 		$mimetypes=Loader::loadConfig('config.protected.mimetypes','',FALSE);
	 		Application::cache('MimeTypes',$mimetypes);
	 	}
	 	$extension=FileUtil::getFileExtension($file,'');
	 	$filemimetype=ArrayUtil::getValue($mimetypes,strtolower($extension),FALSE);
	 	return $filemimetype;
	}
	
	static function getBaseMimeType($file) {
	 	$mime=FileUtil::getMimeType($file);
	 	$parts=explode('/',$mime.'/');
	 	return strtolower($parts[0]);
	}
	
	static function getSubMimeType($file) {
	 	$mime=FileUtil::getMimeType($file);
	 	$parts=explode('/',$mime.'/');
	 	return strtolower($parts[1]);
	}
	

	static function getFileName($file) {
	 	return basename($file);
	}
	
	static function getDirName($file) {
	 	return dirname($file);
	}
	
	
	static function getSize($file) {
	 	if (is_file($file)) {
	 	 	return filesize($file);
	 	}
	 	return 0;
	}
	
	
	static function describeFile($file,$getAllDetails=FALSE) {
	 	if (!is_file($file)) {
	 	 	return FALSE;
	 	}
	 	
	 	$out=array(
			'FileSize'=> filesize($file),
			'FileMTime'=> filemtime($file),
			'FileCTime'=> filectime($file),
			'FileATime'=> fileatime($file),
			'PathInfo'=> pathinfo($file),
			'BaseName'=> basename($file),
			'DirName'=> dirname($file),
			'Readable'=>is_readable($file),
			'Writable'=>is_writable($file),
			'MimeType'=>FileUtil::getMimeType($file),	
		);
	 	
	 	$pathinfo=ArrayUtil::getValue($out,'PathInfo',array());
	 	$out['Extension']=ArrayUtil::getValue($pathinfo,'extension','');
	 	
		$mimeType=ArrayUtil::getValue($out,'MimeType','');
		if (strstr($mimeType,'image') && $getAllDetails ) {
		 	if (function_exists('getimagesize')) {
		 	 	$out['ImageSize']=getimagesize($file);
		 	}
		 	if (function_exists('exif_read_data')) {
		 	 	if (preg_match('/tif|jp(e*)g/i',$out['MimeType'])) {
		 			$out['EXIF']=exif_read_data($file);
				} 
		 	}
		 
		}	 	
	 
	 	return $out;	
	 	
	 	
	 
	}
	
	
	
	static function deleteFile($path) {
	 	if (!FileUtil::isWritableFile($path)) {
	 	 	return FALSE;
	 	}
	 	unlink($path);
	 
	}
	
	
	static function getURLInfo($url) {
	 	$info=parse_url($url);
	 	if ($info) {
	 	 	return $info;
	 	}
	 	return FALSE;
	 
	}
	
	static function getURLPart($url,$partname='path') {
	 	
		$part=$partname;
	 	switch ($partname) {
	 		case 'dirname' : $part='path'; break;
			case 'filename' : $part='path'; break;
	 	}
	 	$parts=FileUtil::getURLInfo($url);
	 	$out=ArrayUtil::getValue($parts,$part,'');
	 	switch ($partname) {
	 		case 'dirname' : $out=FileUtil::getDirName($out); break;
			case 'filename' : $out=FileUtil::getFileName($out); break;
	 	}
	 	
	 	return $out;
	}
	
	static function getURLQuery($url) {
	 	$query=FileUtil::getURLPart($url,'query');
	 	$out=array();
	 	if (!empty($query)) {
	 	 	$parts=explode('&',$query);
	 	 	foreach ($parts as $part) {
	 	 	 	$keyval=explode('=',$part);
	 	 	 	$out[($keyval[0])]=$keyval[1];
	 	 	}
	 	}
	 	return $out;
	}

		
	static function getHTTPPath($path) {
	 	
	 	if (!FileUtil::isDir($path) && !FileUtil::isFile($path)) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Could not translate <i>'.$path.'</i>. Ressource does not exist');
			return $path;
	 	 	
	 	}
	
		/*
	 	FileUtil::convertSlashes($path);	
		$docroot=$_SERVER['DOCUMENT_ROOT'];
		FileUtil::convertSlashes($docroot);
	
		$out=str_replace($docroot,'',$path);
		*/
		
 
	 	$s	= realpath($_SERVER['SCRIPT_FILENAME']);  			
		$p	= $_SERVER['SCRIPT_NAME'];		
		$d	= substr($s,0,strlen($s)-strlen($p)); 
		$h	= substr($path,strlen($d),strlen($path)); 

		FileUtil::convertSlashes($h);
		return $h; 
  	 
	}
	
	
	static function convertSlashes(&$path,$to='/') {
	 	$search=array('\\','/');
	 	$replace=$to;
	 	$path = str_replace($search,$replace,$path);
	 
	 
	}
 
 
 	static function displaySize($bytes) {
	 	$mill=1024;
	 	$mill=1000;
	 	$kb=intval($bytes/$mill);
	 	$mb=intval($kb/$mill);
	 	
	 	if ($mb>0) {
	 	 	$out=$mb.'.'.intval(($kb-($mill*$mb))/10).'&nbsp;MB';
	 	 	return $out;
	 	}
	 	if ($kb>0) {
	 	 	$out=$kb.'.'.intval(($bytes-($mill*$kb))/10).'&nbsp;kB';
	 	 	return $out;
	 	}
		else {
		 	$out=$bytes.'&nbsp;Bytes';
		 	return $out;
		}
	 
	}
	
	
	static function copyFile($source,$dest,$overwrite=FALSE) {
	 
	 	if (!FileUtil::isFile($source)) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Source <i>'.$source.'</i> does not exist or is not a file');
	 	 	return FALSE;
	 	}
	 	if (FileUtil::isFile($dest)) {
	 	 	if  (!$overwrite) {
	 	 		ErrorHandler::throwException(__CLASS__,__METHOD__,'Destination <i>'.$dest.'</i> exists. No permission to overwrite existing file.');
	 	 		return FALSE;
	 	 	}
	 	 	if (!FileUtil::isWritableFile($dest)) {
	 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Destination <i>'.$dest.'</i> exists. This is not a writable file.');
	 	 		return FALSE;
	 	 	}
	 	}
	 	if (copy($source,$dest)) {
	 	 	return TRUE;
	 	}
	 	else {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Copy <i>'.$source.'</i> to <i>'.$dest.'</i> failed.');
	 	 	return FALSE;
	 	}
	 
	}
	
	static function copyDirFiles($source,$dest,$overwrite=FALSE) {
	 	if (!FileUtil::isDir($source)) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'SourceDir <i>'.$source.'</i> does not exist');
	 	 	return FALSE;
	 	}
	 	if (FileUtil::isDir($dest)) {
	 	 	if (!FileUtil::isWritableDir($dest)) {
	 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'DestinationDir <i>'.$dest.'</i> exists. This is not a writable directory.');
	 	 		return FALSE;
	 	 	}
	 	}
	 	
	 	$handle=opendir($source);
 	 	while ($item=readdir($handle)) {
 	 	 	$sourcepath	=$source._DS.$item;
 	 	 	$destpath	=$dest._DS.$item;
 	 	 	FileUtil::copyFile($sourcepath,$destpath,$overwrite);
 	 	}
	 
	}
 
 
}

?>