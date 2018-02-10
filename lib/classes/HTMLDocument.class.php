<?php

class HTMLDocument {
 
 	var $output='';
 	var $DOM=null;
 	var $outputEncoding='UTF-8';
 	
 	var $doctype='xhtml 1.0';
 	
 	var $elements=array();
 
 	function __construct() {
 	 
 	}

	function newLine() {
	 	$this->output.="\n";
	}

	function open() {
	 	$this->doctype_declaration();	
	 	$this->output.='<html>';
	 	$this->newLine();
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	 
	}
	
	function close() {
	 	$this->newLine();
	 	$this->output.='</html>';
	}


	function createElement($tag='',$value='',$attr=array()) {
	 
	 	$elem=array($tag,$value,$attr);
	 	return $elem;
	}
	
	function createNode($name,$parent='body',$tagname='div',$attr=array()) {
	 	$this->elements[$name]=array();
	 	$node=$this->createElement($tagname,array('node'=>$name),$attr);
	 	$this->appendChild($node,$parent);
	}
	
	
	function appendChild($elem,$node='body') {
	 	$this->elements[$node][]=$elem;
	}
	
	function appendData($data,$node='body',$tagname='div',$attr=array()) {
	 	if (!empty($data)) {
			$this->elements[$node][]=array($tagname,$data,$attr); 
		}
	}

	function header() {
	 
	 	$this->meta('content-type','text/html'.';charset='.$this->outputEncoding.';',TRUE);
	
		$this->output.='<head>';
		$this->newLine();	 
		OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	 	$elements=ArrayUtil::getValue($this->elements,'head',array());
		$this->output.=$this->build($elements);
		$this->output.='</head>';
		$this->newLine();	 
	 
	}
	
	
	function body() {
	 	
	 	$this->output.='<body>';
		$this->newLine();
	 	$elements=ArrayUtil::getValue($this->elements,'body',array());
		$this->output.=$this->build($elements);
		$this->output.='</body>';
		$this->newLine();
	 
	}
	
	
	function build($elements=array()) {
	 	
	 	$out='';
	 	for ($e=0;$e<count($elements);$e++) {
	 	 	$elem=$elements[$e];
	 	 	$tag=$elem[0];
	 	 	$value=$elem[1];
	 	 	$args=$elem[2];
	 	 	
	 	 	$attr=ArrayUtil::getValue($args,'attr',$args);
	 	 	
	 	 	$attlist=HTMLFragment::renderAttrList($attr);
	 	 	
	 	 	if (empty($value)) {
	 	 	 	$out.='<'.$tag.$attlist.'/>';
	 	 	}
	 	 	else if (is_array($value)) {
	 	 	 	$nodename=ArrayUtil::getValue($value,'node',array());
	 	 	 	$childs=$this->elements[$nodename];
	 	 	 
	 	 	 	$out.="\n";
	 	 	 	$out.='<'.$tag.$attlist.'>';
				$out.=$this->build($childs);   
				$out.='</'.$tag.'>';
	 	 	}
	 		else {
	 	 		$out.='<'.$tag.$attlist.'>'.$value.'</'.$tag.'>';
	 	 	}
	 	 	$out.="\n";
	 	 
	 	}
	 	return $out;
	 
	}


	function doctype_declaration() {
	
		$dtd=''; 
		$doctype='html';
	 	switch ($this->doctype) {
	 	 
	 	 
	 		case 'html' :  $dtd='"-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"' ; break;
			case 'html 4.01' :  $dtd= '"-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd"' ; break;
	 		
	 		case 'xhtml' :  $dtd= '"-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"' ; break;
			case 'xhtml 1.0' :  $dtd= '"-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"' ; break;
	 		case 'xhtml 1.1' :  $dtd= '"-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"' ; break;
	 		case 'xhtml 2.0' :  $dtd= '"-//W3C//DTD XHTML 2.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml2.dtd"'; break;
	 			
			default : '"-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"' ; break;
	 	
	 	}
	 
	 
	 	$this->output.='<!DOCTYPE '.$doctype.' PUBLIC '.$dtd.'>';
	 	$this->newLine();
	 	
	 
	}
	
	
	function tidy() {
	 	$out=$this->output;
	 	
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,null,$out);
	 	
		// remove template tags
	 	
		$pattern ='/<(com|html):content([^\/]*)\/>/i';
	 	//$replace="<!-- $0 -->";
	 	$replace='';
	 	$out=preg_replace($pattern,$replace,$out);
	 	$out=stripslashes($out);
	 	
	 	 
		$this->output=$out;
	 
	}
	

	function encodeOutput() {
	 
	 	$out=$this->output;	
		
	 	return $out;
	}
	

	 	
	
	
	function out() {
	 	$this->open();
	 	$this->header();
	 	$this->body();
	 	$this->tidy();
	 	$this->close();
	 	
	 	echo $this->encodeOutput();
	}


	// special Elements
	
	function headline($text='',$level=1,$parent='body',$attr=array()) {
	 	$tagname='h'.$level;
	 	$elem=$this->createElement($tagname,$text,$attr);
		$this->appendChild($elem,$parent);
	}
	
	function title($text) {
		$elem=$this->createElement('title',$text);
		$this->appendChild($elem,'head'); 
	}
	
	function stylesheet($url,$scope='all') {
		$tagname='link';
		$attr=array(
				'rel'=>'stylesheet',
				'type'=>'text/css',
				'media'=>$scope,
				'href'=>$url
				);
	 	$elem=$this->createElement($tagname,'',$attr);
		$this->appendChild($elem,'head'); 
	 
	}
	
	function javascript($url) {
		$tagname='script';
		$attr=array(
				'type'=>'text/javascript',
				'src'=>$url
				);
	 	$elem=$this->createElement($tagname,'<!-- -->',$attr);
		$this->appendChild($elem,'head'); 
	 
	}
	
	function meta($name,$content,$httpEquiv=FALSE) {
		$tagname='meta';
		$attr=array(
				'name'=>$name,
				'content'=>$content,
				);
		if ($httpEquiv) {
		 	unset($attr['name']);
			$attr['http-equiv']=$name; 		
		}
	 	$elem=$this->createElement($tagname,'',$attr);
		$this->appendChild($elem,'head'); 
	 
	}
	
	
	
	function callStaticTemplate($path,$file) {
	 
	 	
	 	$out=Loader::loadFile($path,$file);
	 	$this->appendData($out);	
	 	
	 	
	 
	}
	
	function callDynamicTemplate($path,$file) {
	 
	 	
	 	$out=Loader::embedFile($path,$file);
	 	$this->appendData($out);
	 	
	 
	}


}


?>