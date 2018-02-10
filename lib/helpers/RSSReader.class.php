<?php

class RSSReader {
 
 	var $RSS;
	var $parser=null; 
 	
 	function __construct() {
 		$this->init();
 	}
 	
 	
 	function init() {
 	 	Loader::loadClass('XMLParser','lib.parsers');
 	 	$this->parser=new XMLParser();
 	}
 	
 	function getRSS($url) {
 		$this->parser->parseURL($url);
		$this->RSS=$this->parser->getAssocTree();  
 	}

	function getItems() {
	 	$channel=$this->parser->getXMLNode($this->RSS,'channel','node');
	 	$items=ArrayUtil::getValue($channel,'item');
	 	return $items;
	 
	}
	
	function renderAsHTML() {
	 	$items=$this->getItems();
	 	$feed=array();
	 	foreach($items as $num=>$item) {
	 	 	$title	= $this->parser->getXMLNode($item,'title','content');
	 	 	$descr	= $this->parser->getXMLNode($item,'description','content');
	 	 	$feed[]=array(
			  	'title'	=> $title,
			  	'descr'	=> $descr,
			  	'item'=>$item,
			);
	 	}
	 	$tmpl=$this->getTemplate();
	 	$out=RenderUtil::renderByTemplate($feed,$tmpl);
	 	return $out;
	}
	

	
	function getTemplate() {
	 	$out='
	 	<div class="rss-item">
		<h3>%%title%%</h3>
		<div>%%descr%%</div>
		</div>	 	
	 	';
	 	return $out;
	 
	}


}
?>