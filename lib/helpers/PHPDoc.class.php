<?php

class PHPDoc {
 
 	var $Count		= array();
	var $Register	= array();
	var $Namespaces = array();
	var $LogPath	= '.';
	
	var $ScanPath	= '.';
	var $StorePath	= '.';
	var $DumpData	= FALSE;
	
	var $Output='';
	var $DocumentTitle = '';

	var $Config = array();
	
	var $refresh	= FALSE;

	function __construct() {
	 	ob_start();
	 	$this->Count=array(
		 	'Files'			=> 0,
		 	'Classes' 		=> 0,
		 	'Functions' 	=> 0,
		 	'LinesOfCode' 	=> 0,
		 	'Bytes'			=> 0,
		 
		 );
		 
		 $this->Register=array(
		 	'Classes'		=> array(),
		 	'Methods'		=> array(),
		 	'Files'			=> array(),
		 	'Namespaces'	=> array(),
		 
		 );

	}
	

	function loadConfig() {
		$config = Loader::loadConfig('config','config.php',TRUE,FALSE);
		$this->Config = $config;
	}

	function registerNamespaces() {


		$namespaces = ArrayUtil::getValue($this->Config,'namespaces',null);
		if ($namespaces) {
			foreach ($namespaces as $namespace=>$info) {
				$pathToIndex = ArrayUtil::getValue($info,'path',null);
				$scriptName = ArrayUtil::getValue($info,'script','');
				$index = array();
				if ($pathToIndex) {
						$set = array(
							'path' => $pathToIndex,
							'scriptName' => $scriptName, 
						);
						$this->Namespaces[$namespace]=$set;
				}
			}
		}	
	}

	function isRegisteredNamespace($namespace) {
		return (ArrayUtil::hasKey($this->Namespaces,$namespace));
	}

	function getRegisteredNamespaceIndex($namespace='') {
	
		return $this->getOuterIndex($namespace);
	}

	function getRegisteredNamespaceScriptName($namespace='') {
		$namespaceInfo =  ArrayUtil::getValue($this->Namespaces,$namespace,array());
		$script =  ArrayUtil::getValue($namespaceInfo,'scriptName','');
		return $script;
	}

	function run() {
	 

		$this->loadConfig();
		$this->registerNamespaces();

	 	$cmd=isset($_REQUEST['doc_do']) ? $_REQUEST['doc_do'] : '';
	 	if ($cmd=='refresh') {
	 	 	$this->refresh = TRUE;
	 	}
	 	
	 	if ($cmd=='dump') {
	 	 	$this->DumpData = TRUE;
	 	 	$this->startDump();
	 	 	$this->refresh	= TRUE;
	 	}
	 
		$scanFiles=($this->refresh) ? TRUE : FALSE;
		if (!$scanFiles) {
		 	$this->loadIndex($scanFiles);
		}
		if ($scanFiles) {	 	
	 		$this->walkDir($this->ScanPath);
	 		$this->storeIndex();	
		}

		$this->dispatch(); 
	}
	
	function dispatch() {
	 	$cmd=isset($_REQUEST['doc_do']) ? $_REQUEST['doc_do'] : '';
		switch ($cmd)  {
		 	case 'classes' 	:
			 				break;	 
		 
		 	default			: $this->Output=$this->infoScreen($cmd);
		} 
	 
	} 


	function loadIndex(&$reScan) {

		$index=$this->StorePath.'/docIndex.php';
		if (!file_exists($index)) {
		 	$reScan=TRUE;
		 	return FALSE;
		}
		
		$file=file_get_contents($index);
		$raw=base64_decode($file);
		$data=unserialize($raw);
		$this->Count=$data['Count'];
		$this->Register=$data['Register'];
		
		$reScan=FALSE;
		return TRUE;
					 
	}

	function getOuterIndex($namespace) {

		if (!$this->isRegisteredNamespace($namespace)) {
			return FALSE;
		}

		$namespaces = $this->Namespaces;
		$namespaceInfo = ArrayUtil::getValue($namespaces,$namespace,array());

		$namespaceIndex = ArrayUtil::getValue($namespaceInfo,'index',null);
		if ($namespaceIndex) {
			return $namespaceIndex;
		}

		$namespacePathToIndex = ArrayUtil::getValue($namespaceInfo,'path',null);

		$index = $this->loadOuterIndex($namespacePathToIndex,$namespace);
		if ($index) {
			$this->Namespaces[$namespace]['index']= $index;
			return $index;
		}

		return FALSE;
	
	}

	function loadOuterIndex($path=null,$namespace='') {

		if (!$path) {
			return;
		}
		
		if (!file_exists($path)) {
		 	return FALSE;
		}
		
		$file=file_get_contents($path);
		$raw=base64_decode($file);
		$data=unserialize($raw);
		$index=$data['Register'];

		return $index;
					 
	}

	
	
	
	function storeIndex() {
		$index=$this->StorePath.'/docIndex.php';
		$out=array(
			'Count' => $this->Count,
			'Register' => $this->Register,
		);
		$store=base64_encode(serialize($out));
		$fp=fopen($index,'wb+');
		fwrite($fp,$store);
		fclose($fp); 
	}

	 	
	function walkDir($dir) {
	 
	 //var_dump($dir);
	 //echo '<br/>';
	
		$h=opendir($dir);
		while ($f=readdir($h)) {
		 	if (substr($f,0,1)!='.' && substr($f,0,1)!='_') {
		 	 	$path=$dir.'/'.$f;
		 	 	if (is_dir($path)) {
		 	 	 	$this->walkDir($path);
		 	 	}
		 	 	else if (strstr($f,'.php')) {
		 	 	 	$this->Count['Files']++;
		 	 	 	$fileindex=md5($path);
		 	 	 	$fileinfo=array(
		 	 	 			'filename'=>$f,
		 	 	 			'path'=>$path,
		 	 	 			'size'=>filesize($path),
		 	 	 			'mtime'=>filemtime($path),
		 	 	 			//'Classes'=>array(),
		 	 	 			//'Functions'=>array(),
		 	 	 	);
		 	 	 	$fileindex=md5($path);
		 	 	 	$this->Register['Files'][$fileindex]=$fileinfo;
		 	 	 	$this->parse($path);
		 	 	}
		 	}
		} 
	 
	}
	
	
	function parse($path) {
	 
	 	$s=filesize($path);
	 	$this->Count['Bytes']+=intval($s);
	 	$f=file($path);
	 	$this->Count['LinesOfCode']+=intval(count($f));
	 	$c=implode("\n",$f);
	 	$ctotxt='';
	 	for ($l=0;$l<count($f);$l++) {
	 	 	$line=trim($f[$l]);
	 	 	if (!empty($line)) {
	 	 	 	$ctotxt.="\n".trim($f[$l],"\n\r\0 ");
	 	 	}
	 	}
	 	
	 	$c=$this->stripComments($c);
	 	
	 	if (strstr($c,'function ')) {
	 	 	$a=array();
	 	 	$fc=preg_match_all('/([\n|\s]+)(function)([ ]+)([a-z0-9_]+)/i',$c,$a);
	 	 	$this->Count['Functions']+=intval(count($a[0]));
	 	 	$this->registerFuncOrClass($a,$path,'Functions');
	 	 	//var_dump($a);
	 	}
	 	
	 	if (strstr($c,'class ')) {
	 	 	$a=array();
	 	 	$fc=preg_match_all('/([\n|\s]+)(class)([ ]+)([a-z0-9_]+)/i',$c,$a);
	 	 	$this->Count['Classes']+=intval(count($a[0]));
	 	 	$this->registerFuncOrClass($a,$path,'Classes');

	 	}
	 	
	 	
	 	$this->addToDump($ctotxt,$path);
	 	
	 
	}
	
	
	function stripComments($string) {
	 	$out=$string;
	 	$out=preg_replace('/\/\*(.*)\*\//msU','',$out);
	 	$out=preg_replace('/\/\/([^\n]*)[\n]/','',$out);
	 	return $out;
	 
	}
	
	
	function registerFuncOrClass($matches,$path,$what='Undefined') {
	 	$filename=basename($path);
	 	$fileindex=md5($path);
	 	for ($m=0;$m<count($matches[4]);$m++) {
	 	 	$classname=$matches[4][$m];
	 	 	//var_dump($what.': '.$classname.' loc: '.$filename.' ----- '.$matches[0][$m].'<br/>');
	 	 	$this->Register[$what][]=array($classname,$fileindex);
	 	 	$this->Register['Files'][$fileindex]['file']=$path;
	 	 	$this->Register['Files'][$fileindex][$what][]=$classname;
	 	 
	 	}
	 
	}
	
	
	function getClassesMenu() {
	 	$reg=$this->Register['Classes'];
	 	$out='';
	 	$classes=array();
	 	foreach($reg as $num=>$data) {
	 	 	$class=$data[0];
	 	 	$fileindex=$data[1];
	 	 	$classes[$class]=$fileindex;
	 	}
	 	
	 	ksort($classes);
	 	
	 	// a-z register
	 	$azreg=array('toc'=>array(),'anchors'=>array());
	 	$toc='';
	 	foreach($classes as $class=>$fileindex) {
	 	 	$regChar=strtoupper(substr($class,0,1));
	 	 	if (!isset($azreg['toc'][$regChar])) {
	 	 	 	$azreg['toc'][$regChar]=$regChar;
	 	 	 	$azreg['anchors'][$fileindex]=$regChar;
	 	 	}
	 	}
	 	foreach($azreg['toc'] as $tocChar) {
	 	 	$toc.='<li><a href="#toc'.$tocChar.'" class="toc">'.$tocChar.'</a></li>';
	 	}
	 	if (!empty($toc)) {
	 	 	$out.='<ul class="toc toc-az">'.$toc.'</ul><div class="eol eos"></div>'."\n";
		}
		// -----
	 	
	 	
	 	$out.='<ul class="wrapper list-wrapper">';
	 	foreach($classes as $class=>$fileindex) {
	 	 	$attr='';
	 	 	if (isset($_REQUEST['f'])) {
	 	 	 	if ($fileindex==$_REQUEST['f']) {
	 	 	 	 	$attr.=' class="active-item" id="filesIndexActiveItem" ';
	 	 	 	}
	 	 	}
	 	 	if (isset($azreg['anchors'][$fileindex])) {
	 	 	 	$attr.=' name="toc'.$azreg['anchors'][$fileindex].'" ';
	 	 	 	unset($azreg['anchors'][$fileindex]);
	 	 	}
	 	 	$out.= '<li>';
			$out.= '<div><a '.$attr.' href="?doc_do=f&amp;f='.$fileindex.'">'.$class.'</a></div>';
			$out.='</li>'."\n";
	 	}
		$out.='</ul>';
		return $out;
	 	
	}
	
	function listClasses() {
	 	$reg=$this->Register['Classes'];
	 	asort($reg);
	 	
	 	$findex=$this->Register['Files'];
	 	$out='<h3>Classes</h3>';
	 	$query='';
	 	$isQuery=FALSE;
	 	if (isset($_REQUEST['fCbN'])) {
			 	$query=strip_tags(stripslashes($_REQUEST['fCbN']));
			 	if (!empty($query)) {
			 		$isQuery=TRUE;
			 	}
		}
	 	
	 	$out.='<form method="post" action="">';
	 	$out.='<fieldset>';
	 	$out.='<label for="fCbN">Filter or find classes by name:</label>';
	 	$out.='<input class="input" type="text" id="fCbN" name="fCbN" value="'.$query.'"/>';
	 	$out.='</fieldset>';
	 	$out.='</form>';
	 	
	 	$out.='<div class="wrapper table-wrapper">';
	 	$out.= '<table>';
	 	$out.='<colgroup><col width="5%"/><col width="15%"/><col width="80%"/></colgroup>'."\n";
	 	foreach($reg as $num=>$data) {
	 	 	$class=$data[0];
	 	 	$fileindex=$data[1];
	 	 	$classindex=md5($class);
	 	 	$fileinfo=$findex[$fileindex];
		 	$filename=$fileinfo['path'];
			
			$showClass=TRUE;
			if ($isQuery) {
			 	$showClass=FALSE;
			 	if (stristr($class,$query)) {
			 	 	$showClass=TRUE;
			 	}
			}
			else {
			 	// on empty query show only lib.classes
			 	$showClass=FALSE;
			 	if (strstr($filename,'lib/')) {
			 	 	$showClass=TRUE;
			 	}
			}
			
			if ($showClass) {
				$linkToClass=$this->getLinkToClass($class);
			  	
		 	 	$out.= '<tr>';
				$out.= '<th colspan="3"><a class="aclass" href="'.$linkToClass.'">'.$class.'</a></th>';
				$out.='</tr>';
				$out.='<tr>';
				
				$out.= '<td>&nbsp;</td><td>File</td><td><a class="afile" href="?doc_do=f&amp;f='.$fileindex.'">'.basename($filename).'</a></td>';
				$out.='</tr><tr>';
				$out.= '<td>&nbsp;</td><td><div>Methods:</div></td>';
				$out.='<td>';
		 	 	$out.=$this->listFunctions($fileindex,$linkToClass,array('liCSSClass'=>'cell30'));
		 	 	$out.= '</td>';
		 	 	$out.='</tr>';
		 	 	$out.="\n";
		 	}
	 	 	
	 	}
	 	$out.= '</table>';
	 	$out.='</div>';
	 	return $out;
	 
	}
	
	
	function listMethods() {
	 	$reg=$this->Register['Functions'];
	 	asort($reg);
	 	
	 	$findex=$this->Register['Files'];
	 	$out='<h3>Function reference</h3>';
	 	$query='';
	 	$isQuery=FALSE;
	 	if (isset($_REQUEST['fMbN'])) {
			 	$query=strip_tags(stripslashes($_REQUEST['fMbN']));
			 	if (!empty($query)) {
			 		$isQuery=TRUE;
			 	}
		}
	 
	 	
	 	$out.='<form method="post" action="">';
	 	$out.='<fieldset>';
	 	$out.='<label for="fMbN">Filter or find methods/functions by name:</label>';
	 	$out.='<input class="input" type="text" id="fMbN" name="fMbN" value="'.$query.'"/>';
	 	$out.='</fieldset>';
	 	$out.='</form>';
	 	
	 	if ($isQuery) {
	 	 	$out.='<dl class="wrapper methods-list-wrapper">';
	 	 	foreach($reg as $num=>$data) {
	 	 	 	$method=$data[0];
	 	 		$fileindex=$data[1];
	 	 		$classindex=md5($class);
	 	 		$fileinfo=$findex[$fileindex];
		 		$filename=$fileinfo['path'];
		 		
		 		if (stristr($method,$query)) {
		 			
				  
		 		 	$class=$this->Register['Files'][$fileindex]['Classes'][0];
		 		 	$linkToClass=$this->getLinkToClass($class);
		 		 	$linkToMethod=$linkToClass.'#'.$method;
		 		 
					$out.='<dt>Method: '.'<a class="amethod" href="'.$linkToMethod.'">'.$method.'</a></dt>';
					$out.='<dd><code class="code-block">'.$this->extractFuncFromFile($fileindex,$method).'</code></dd>';
					$out.='<dd class="descr">defined in class <a class="aclass" href="'.$linkToClass.'">'.$class.'</a></dd>'; 		 
		 		}
		 		
	 	 	}
	 		$out.='</dl>';
	 	 
	 	}
	 	
	 	return $out;
	 
	}
	
	
	
	/**
	/* list functions / methods defined in a specified file
	/* 
	/* @param 	string 	$fileindex		
	/* @param 	string 	$linkToClass 
	/* @param 	array 	$args		
	**/	 
	
	
	
	function listFunctions($fileindex,$linkToClass,$args=array()) {
	 	$reg=$this->Register['Files'][$fileindex]['Functions'];
	 	
	 	sort($reg);
	 	
	 	$liCSSClass = isset($args['liCSSClass']) ? $args['liCSSClass'] : 'list-item';
	 	
	 	$out='';
	 	$out.= '<ul>';
	 	for ($r=0;$r<count($reg); $r++) {
	 	   	$func=$reg[$r];	
	 	   	$linkToMethod=$linkToClass.'#'.$func;
	 	 	$out.= '<li class="'.$liCSSClass.'"><a class="amethod" href="'.$linkToMethod.'">'.$func.'</a></li>';
	 	 	
	 	}
	 	$out.= '</ul>';
	 	return $out;
	 
	}
	

	function addToDump($ctotxt,$path) {
	 	
	 	if ($this->DumpData) {
			$fp=fopen($this->LogPath.'/'.$this->LogFile,'ab+');
		 	fwrite($fp,"\n\n\n");
		 	fwrite($fp,"/** --------------------------------------------------------------------- **/\n");
		 	fwrite($fp,"/** File: ".$path." **/\n");
		 	fwrite($fp,"/** --------------------------------------------------------------------- **/\n");
		 	fwrite($fp,$ctotxt);
		 	fwrite($fp,"\n\n\n");
		 	fclose($fp);
	 	}

	 
	}
	
	function startDump() {
	 	if ($this->DumpData) {
		 	$fp=fopen($this->LogPath.'/'.$this->LogFile,'wb+');
		 	fwrite($fp,"\n\n\n");
		 	fwrite($fp,"/** --------------------------------------------------------------------- **/\n");
		 	fwrite($fp,"/** FileDump: ".strftime('%d.%m.%Y %H:%M:%S')." **/\n");
		 	fwrite($fp,"/** --------------------------------------------------------------------- **/\n");
		 	fwrite($fp,"\n\n\n");
		 	fclose($fp);
	 	}
	}
	
	function report() {
	 
	 	$out='<table>';
	 	$count=$this->Count;
	 	foreach ($count as $key=>$val) {
	 	$out.= '<tr><td>'.$key.'</td><td class="align-right">'.$val.'</td></tr>';
	 	}
	 	$out.='<table>';
	 	
	 	$out.=$this->getClassesMenu();
	 	echo $out;
	 
	}
	
	
	function infoScreen($cmd) {
	 	
	 	$out = '';	 	
		$out.='<div id="mainmenu">'; 
	 	$content=$this->getMenu();
	 	$out.=$content;	
	 	$out.='<div class="eos"></div>';
	 	$out.='</div>';

	 	$out.='<div id="sidebar">';
	 	
	 	
	 	$out.='<div class="section">';
	 	$out.='<h3>Report</h3>';	 	
	 	$content='<div id="report">';
	 	$content.='<table>';
	 	$count=$this->Count;
	 	foreach ($count as $key=>$val) {
	 	$content.= '<tr><td>'.$key.'</td><td class="align-right">'.$val.'</td></tr>';
	 	}
	 	$content.='</table>';
	 	$content.='</div>';
	 	$out.=$content;
	 	$out.='</div>';
	 	
	 	$out.='<div class="section">';
	 	$out.='<h3>Classes</h3>';
	 	$content='<div class="sector" id="classMenu">';
	 	$content.=$this->getClassesMenu();
	 	$content.='</div>';
		$out.=$content;	 
		$out.='</div>';	
		$out.='</div>';


		$out.='<div id="content">'; 	 	

		$content=$this->getMainContent($cmd);
		$out.='<div id="main">';
		$out.='<div class="section">';
		$out.=$content;
		$out.='</div>';
		$out.='</div>';

		$out.='</div>';
		
		
		$out.='<script type="text/javascript">var ai=document.getElementById("filesIndexActiveItem");if(ai) {ai.focus();}</script>';
		
		
		return $out;
	 
	}


	function getMainContent($cmd) {
	 
	 	$content='';
	 	
	 	switch($cmd) {
	 		case 'f' :
			 			$f=$_REQUEST['f'];
						$content=$this->showFile($f);  
						break;
			case 'lc':  $content=$this->listClasses();  
						break;
						
			case 'lm':  $content=$this->listMethods();  
						break;
						
			case 'tree':  $content=$this->listFilesTree();  
						break;
						
			case 'd'	: 
						$f=$_REQUEST['f'];
						$this->download($f);
						break;	
	 	 	
	 	 	default : $content = $this->listFiles();
	 	 
	 	}
	 	return $content;
	 
	 
	}


	function getMenu() {
	 
	 	$out='<ul class="menu sector">';
	 	$out.='<li><a href="?doc_do=lc" title="list or find classes">Classes</a></li>';
	 	$out.='<li><a href="?doc_do=lm" title="find methods or functions">Functions</a></li>';
	 	$out.='<li><a href="?doc_do=files" title="list files">Files</a></li>';
	 	$out.='<li><a href="?doc_do=tree" title="list tree">Tree</a></li>';
	 	$out.='<li><a href="?doc_do=dump" title="dump sourcecode">Dump</a></li>';
	 	$out.='<li><a href="?doc_do=refresh" title="scan dir and refresh index">Refresh</a></li>';
	 	$out.='</ul>';
	 	$out.="\n";
	 	
	 	return $out;
	}
	
	function listFiles() {
	 
	 	$this->DocumentTitle = 'files';
	 
	 	$files=$this->Register['Files'];
	 	uasort($files,array($this,'sortFiles'));
	 	$out='<h3>Files</h3>';
	 	$out.='<div class="wrapper table-wrapper">';
	 	$out.='<table border="1">';
	 	$so=($_REQUEST['so']=='DESC') ? 'ASC' : 'DESC';
	 	$slink='?so='.$so;
	 	$out.='<tr>
					<th><a href="'.$slink.'&amp;sb=filename'.'">File</a></th>
					<th><a href="'.$slink.'&amp;sb=path'.'">Path</a></th>
		 			<th><a href="'.$slink.'&amp;sb=mtime'.'">LastMod</a></th>
					<th align="right"><a href="'.$slink.'&amp;sb=size'.'">Size</a></th>
				</tr>';
	 	
	 	foreach ($files as $num =>$info) {
	 	 	$date=strftime('%d.%m.%Y %H:%M:%S',$info['mtime']);
	 	 	$size=$info['size'];
	 	 	$file=$info['filename'];
	 	 	$path=dirname($info['path']);
	 	 	$out.='<tr>
			  		<td><a class="afile" href="?doc_do=f&amp;f='.$num.'">'.$file.'</a></td>
					<td>'.$path.'</td>
					<td>'.$date.'</td>
					<td class="align-right">'.$size.'</td>
				</tr>';
	 	}
	 	$out.='</table>';
	 	$out.='</div>';
	 	return $out;
	 
	}
	

	
	function listFilesTree() {
	 
	 	$this->DocumentTitle = 'tree';
	 
	 	$files=$this->Register['Files'];
	 	uasort($files,array($this,'sortFilesTree'));
	 	
	 	$tree=array();
		foreach ($files as $num =>$info) {
	 	 	$file=$info['filename'];
	 	 	$disp='<a class="afile" href="?doc_do=f&amp;f='.$num.'">'.$file.'</a>';
	 	 	$path=dirname($info['path']);
	 	 	$parts=explode('/',$path);
	 	 	$index='';
	 	 	for ($p=0;$p<count($parts);$p++) {
	 	 	 	$dir=$parts[$p];
	 	 	 	$index.='["'.$dir.'"]';
	 	 	 	eval('if (!isset($tree'.$index.')) { $tree'.$index.'=array(); }');
	 	 	}
	 	
	 	 	eval ('$tree'.$index.'[]="'.addslashes($disp).'";');
	 	}


	 	$out='<h3>Tree</h3>';
	 	$out.='<div class="wrapper tree-wrapper" id="treeList">';
	 	$out.=$this->listArray($tree);
	 	$out.='</div>';
	 	
	 	$out.='<script type="text/javascript">
		 var t=document.getElementById("treeList");
		 var a=t.getElementsByTagName("a");
		 for(i=0;i<a.length;i++) {
		  		if (a[i].getAttribute("href")=="#") {
		  		 	a[i].nextSibling.style.display="none";
		  		 	a[i].onclick = function() { 	
		  		 	 	var u=this.nextSibling;
					    var s=u.style.display;
					    u.style.display=(s.indexOf("none")>-1) ? "block" : "none";
					    this.firstChild.innerHTML=(s.indexOf("none")>-1) ? "-" : "+";
					    };
 		  		}
			}
		 </script>';
	 	
	 	return $out;
	}
	
	
	function listArray($array=array(),$level=0) {
	 	$out='<ul class="level-'.$level.'">';
	 	foreach ($array as $num=>$val) {
	 	 	$out.='<li>';
	 	 	if (is_array($val)) {
	 	 		if ($num=='..') {
	 	 			$out.='<span>[ '.$num.' ]</span>';
	 	 		}
	 	 	 	else if ($level>0) {
	 	 	 		$out.='<a class="tree-toggle" href="#"><span>+</span>'.$num.'</a>';
	 	 	 	}
	 	 	 	else {
	 	 	 	 	$out.='<div>'.$num.'</div>';
	 	 	 	}
	 	 	 	$out.=$this->listArray($val,$level+1);
	 	 	}
	 	 	else {
	 	 	 	$out.='<span>'.$val.'</span>';
	 	 	}
	 	 	$out.='</li>'."\n";
	 	}
		$out.='</ul>'."\n";
		return $out;
	 
	}
	
	
	function sortFiles($a,$b) {
	 
	 	$r1= ($_REQUEST['so']=='DESC') ? -1 : 1;
	 	$r2= 0 - $r1;
	 	$key= (isset($_REQUEST['sb'])) ? $_REQUEST['sb'] : 'mtime';
	 
	 	return (($a[$key])>($b[$key])) ? $r1 : $r2;
	}
	
	function sortFilesTree($a,$b) {
	 	return (($a['path'])>($b['path'])) ? 1 : -1;
	}
	
	
	function extractFuncFromFile($fileindex='',$funcname='') {
	 
	 	$fileinfo=$this->Register['Files'][$fileindex];
	 	$filepath=$fileinfo['path'];
	 	if (file_exists($filepath)) {
	 		$file=file($filepath);
	 		$contents=implode("\n",$file);
			$matches=array();
			$pattern='/(.*)function([ ]*)'.$funcname.'([ ]*)\(([^\{]*)\{/U';
			preg_match_all($pattern,$contents,$matches);
			return $matches[0][0]; 	
	 	}
	 	
	 
	}
	
	
	function showFile($fileindex) {
	 	$fileinfo=$this->Register['Files'][$fileindex];
	 	$filepath=$fileinfo['path'];
	 	$filename=basename($filepath);
	 	
	 	$this->DocumentTitle=$filename;
	 	
	 	$file=file($filepath);
	 	$lines=count($file);
	 	
	 	$dLink='<a href="?doc_do=d&amp;f='.$fileindex.'" class="download">Download</a>';
	 	
	 	$out='<h3>File: '.$filename.''.$dLink.'</h3>';
	 	
	 	$out.=$this->tocMethods($fileindex);
	 	
	 	
	 	$out.='<div class="wrapper table-wrapper">';
	 	$out.='<table>';
	 	$out.='<tr>';
	 	$out.='<td valign="top" class="line-num" style="width:1%;text-align:right;">';
	 	for ($l=0;$l<$lines;$l++) {
	 	 	$out.=' <pre>'.($l+1).'</pre>';
	 	}
	 	
	 	$out.='</td>';
	 	
	 	$out.='<td valign="top" style="width:90%;background-color:#fff;wrap:nowrap;">';
	 	//$out.=$fileOut;
	 	
	 	$isComment=FALSE;
	 	$comOpen='<span class="comment">';
	 	$comClose='</span>';
	 	for ($l=0;$l<$lines;$l++) {
	 	 	$sol='';
	 	 	$eol='';
	 	 	$line=htmlentities($file[$l]);
	 	 	// handle comments
	 	 	if ($isComment) {
	 	 	 	$sol=$comOpen;
	 	 	 	$eol=$comClose;
	 	 	}
	 	 	if (strstr($line,'/*')) {
	 	 	 	$line=str_replace('/*',$comOpen.'/*',$line);
	 	 	 	$isComment=TRUE;
	 	 	 	$eol=$comClose;
	 	 	}
	 	 	if (strstr($line,'*/')) {
	 	 	 	$line=str_replace('*/','*/'.$comClose,$line);
	 	 	 	$isComment=FALSE;
	 	 	 	$eol='';
	 	 	}
	 	 	if (strstr($line,'//')) {
	 	 	 	if (!$isComment) {
	 	 	 		$line=str_replace('//',$comOpen.'//',$line);
					$eol=$comClose;   	 
	 	 	 	}
	 	 	}
	 	 	// ----
	 	 	
	 	 	$line=preg_replace_callback('/([\\\\A-Z_]*)\:\:([A-Z_]*)\(/i',array($this,'linkRessources'),$line);
	 	 	$line=preg_replace_callback('/new([\s]*)([A-Z_]*)\(/i',array($this,'linkConstructors'),$line);
	 	 	$line=preg_replace_callback('/extends([\s]*)([A-Z_]*)([\s])/i',array($this,'linkExtends'),$line);
	 	 	$line=preg_replace_callback('/function([\s])*([A-Z_]*)\(/i',array($this,'anchorizeMethods'),$line);
	 	 	
	 	 	$out.=' <pre>'.$sol.'&nbsp;'.''.($line).$eol.'</pre>';
	 	}
	 	
	 	
	 	$out.='</td>';
	 	
	 	
	 	$out.='</tr>';
	 	$out.='</table>';
	 	$out.='</div>';
	 	
	 	$out=$this->highlight_php($out);

	 	return $out;
	}	
	
	function highlight_php($string) {

		$find = array('class ','namespace ','extends ', 'var ','return ','switch ','case ','if ','else ','while ','foreach ');
		$repl = array();
		foreach ($find as $word) {
			$repl[]='<em class="phpword">'.$word.'</em>';
		}
		$string = str_replace($find,$repl,$string);

		$pattern = '/([\$]+)([A-Z_-]*)/i';
		$repl = "<em class=\"phpvar\">$1$2</em>";
		$string = preg_replace($pattern,$repl,$string);

		return $string;
	}

	function tocMethods($fileindex) {
	 	
	 	
	 	$methods=$this->Register['Files'][$fileindex]['Functions'];
	 	sort($methods);
	 	$out='<div class="rollover"><h3>Methods / Functions</h3>';
	 	$out.='<ul class="toc">';
	 	foreach($methods as $method) {
	 	 	$link='#'.$method;
	 	 	$out.='<li><a href="'.$link.'" class="amethod">'.$method.'</a></li>';
	 	}
	 	$out.='</ul>';
	 	$out.='</div>';
	 	return $out;
	}


	function resolveNameSpace($className) {

		$classNamespace= '';
		$in = $className;

		$classURI = str_replace('\\','.',$className);
		if (substr($classURI,0,1)=='.') {
			$classNamespace='_global_';
			$className = substr($classURI,1);
		}
    	if ($lastDot = strrpos($classURI, '.')) {
    		$classNamespace = substr($classURI, 0, $lastDot);

        	$className = substr($classURI, $lastDot + 1);	
    	}

    	$out = array(
    		'namespace' => $classNamespace,
    		'classname' => $className,
    		'classuri' => $classURI,
    		'in' => $in,
    	);
   

    	return $out;
	}
	
	function linkRessources($matches) {
	 	$class=$matches[1];
	 	$method=$matches[2];
	 	$out=$matches[0];

	 	//handleNameSpace
	 	$classInfo = $this->resolveNamespace($class);
	 	$className = ArrayUtil::getValue($classInfo,'classname','');
	 	$namespace = ArrayUtil::getValue($classInfo,'namespace','');
	
	
		$linkToClass=$this->getLinkToClass($className); 
		if ($linkToClass) {	
		 	$linkToMethod=$linkToClass.'#'.$method;
	 		$out='<a href="'.$linkToClass.'" class="aclass">'.$class.'</a>::<a href="'.$linkToMethod.'" class="amethod">'.$method.'</a>(';
	 	}
	 	else if ($this->isRegisteredNameSpace($namespace)) {
	 	
	 		$linkToClass=$this->getLinkToClass($className,$namespace);
	 		if ($linkToClass) {	
		 		$linkToMethod=$linkToClass.'#'.$method;
	 			$out='<a href="'.$linkToClass.'" class="aclass">'.$class.'</a>::<a href="'.$linkToMethod.'" class="amethod">'.$method.'</a>(';
	 		}
	 	}

	 	return $out;
	} 
	
	function linkConstructors($matches) {
	 	$constr=$matches[0];
	 	$class=$matches[2];
	 	$repl=$class;
	 	if (substr($class,0,1)!='$') {
			$linkToClass=$this->getLinkToClass($class); 
			if ($linkToClass) {	
	 			$repl='<a href="'.$linkToClass.'" class="aclass">'.$class.'</a>';
	 		}
	 	}
	 	
	 	$out=str_replace($class,$repl,$constr);
	 	return $out;
	} 
	
	function linkextends($matches) {
	 	$constr=$matches[0];
	 	$class=$matches[2];
	 	$repl=$class;
	 	if (substr($class,0,1)!='$') {
			$linkToClass=$this->getLinkToClass($class); 
			if ($linkToClass) {	
	 			$repl='<a href="'.$linkToClass.'" class="aclass">'.$class.'</a>';
	 		}
	 	}
	 	
	 	$out=str_replace($class,$repl,$constr);
	 	return $out;
	} 
	
	function anchorizeMethods($matches) {
	 	$out=$matches[0];
	 	$func=$matches[2];
	 	$out='<a class="anchor" name="'.$func.'">'.$out.'</a>';
	 	return $out;
	 
	}
	
	
	function getClassFromIndex($class,$namespace='') {
	 	$reg=$this->Register['Classes'];
	 	$scriptName = '';
	 	if (!empty($namespace)) {
	 		if ($this->isRegisteredNamespace($namespace)) {
	 			$index = $this->getRegisteredNamespaceIndex($namespace);
	 			$reg = ArrayUtil::getValue($index,'Classes',array());
	 			$scriptName = $this->getRegisteredNamespaceScriptName($namespace);
	 		}
	 	}
	 	foreach($reg as $num=>$info) {
	 	 	if ($class==$info[0]) {
	 	 	 	$out = $info;
	 	 	 	$out['script'] = $scriptName;
	 	 	 	return $out;
	 	 	}
	 	}
	 	return FALSE;
	} 


 
	
	function getLinkToClass($class,$namespace='') {
	 	$info=$this->getClassFromIndex($class,$namespace);
	 	if (!$info) {
	 	 	return FALSE;
	 	}
	 	$fileindex=$info[1];
	 	$scriptName = ArrayUtil::getValue($info,'script','');

	 	$out=$scriptName.'?doc_do=f&amp;f='.$fileindex;
	 	return $out;
	}


	
	function out() {
	 	ob_end_clean();
	 	$out='
	 	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	 	<html>
		<head>
			<title>zedea.de :: doc :: '.$this->DocumentTitle.'</title>
			<link href="doc.css" rel="stylesheet" type="text/css"/>
		</head>
		<body>
		';
	 	$out.= $this->Output;
	 	$out.='
	 	<div class="eos"></div>
	 	</body>
		</html>
		';
		echo $out;
	 	
	 
	}
	
	function download($fileindex) {
	 	$fileinfo=$this->Register['Files'][$fileindex];
	 	$filepath=$fileinfo['path'];
	 	$filename=basename($filepath);
	 	$size=$fileinfo['size'];
	 	
	 	header('Content-type:text/php');
	 	header('Content-length:'.$size);
	 	header('Content-disposition:attached;filename='.$filename);
	 	readfile($filepath);
	 	die();
	 	
	}

}



?>