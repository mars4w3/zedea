<?php
/**
class: 		XMLParser
descr: 		Implementation of PHP XML parser for zedea-framework 
date:		2013/03/01
author: 	M.Stiewe <mars@4w3.de>
revision:	2013/03/02
version:	1.0.1 	
**/

class XMLParser {

	var $xml_parser 	= null;
	var $data 			= array();
	var $char_set 		= 'UTF-8';
	
	var	$index 			= array();
	var $structure 		= array();

  
   	function __construct() {
	  
	
	}
	
	function init() {
		$this->data = array();  
		$this->xml_parser = xml_parser_create($this->char_set);
		xml_set_object($this->xml_parser,$this);
		
		// disable the uppercase folding (PHPs default)
		xml_parser_set_option($this->xml_parser,XML_OPTION_CASE_FOLDING,0);
		
		// set handlers
		xml_set_character_data_handler($this->xml_parser, 'dataHandler');   
		xml_set_element_handler($this->xml_parser, "startHandler", "endHandler");
	
	}
	
	function parseStructure($xmlstring) {
	 	$values	=array();
	 	$index	= array();
	 	$this->init();
	 	if (!xml_parse_into_struct($this->xml_parser,$xmlstring,$values,$index)) {
		 	$errmsg=sprintf(
			   		"XML error: %s at line %d",
	           		xml_error_string(xml_get_error_code($this->xml_parser)),
	           		xml_get_current_line_number($this->xml_parser)
				);
			ErrorHandler::err(__CLASS__,__METHOD__,$errmsg);
	        xml_parser_free($this->xml_parser);
	        return FALSE;
		}
	 	$this->index=$index;
	 	$this->structure=$values;
	}
	
	function parseURL($url) {
	 	$xmlstring=FileUtil::getContentFromURL($url);
	 	if ($xmlstring) {
	 	 	$this->parseString($xmlstring);
	 	}
	}
	
	function parseFile($path) {
		if (!($fp = fopen($path, "r"))) {
	       ErrorHandler::err(__CLASS__,__METHOD,"Cannot open XML data file: $path");
	       return false;
	   }
	   $this->init();	
	   while ($data = fread($fp, 4096)) {
			if (!xml_parse($this->xml_parser, $data, feof($fp))) {
				$errmsg=sprintf(
			   		"XML error: %s at line %d",
	           		xml_error_string(xml_get_error_code($this->xml_parser)),
	           		xml_get_current_line_number($this->xml_parser)
				);
				ErrorHandler::err(__CLASS__,__METHOD__,$errmsg);
	           	xml_parser_free($this->xml_parser);
	           	return FALSE;
	       }
	   }
	   return $this->data; 
	}
	
			
  
	function parseString($xmlstring){
	 	$this->init();
		if (!xml_parse($this->xml_parser, $xmlstring)) {
				$errmsg=sprintf(
			   		"XML error: %s at line %d",
	           		xml_error_string(xml_get_error_code($this->xml_parser)),
	           		xml_get_current_line_number($this->xml_parser)
				);
				ErrorHandler::err(__CLASS__,__METHOD__,$errmsg);
	           xml_parser_free($this->xml_parser);
	           return FALSE;
	   	} 
	   	return $this->data;
	}
	
	
	function dataHandler($parser, $data){
	   if(!empty($data)) {
	       $_data_idx = count($this->data) - 1;
	       $this->data[$_data_idx]['content'] .= $data;
	   }
	}
	
	
	function startHandler($parser, $name, $attribs){
	   $_content = array('name' => $name);
	   if(!empty($attribs)) {
	     $_content['attrs'] = $attribs;
	   }  
	   array_push($this->data, $_content);
	}
		
	function endHandler($parser, $name){
	   if(count($this->data) > 1) {
	       $_data = array_pop($this->data);
	       $_data_idx = count($this->data) - 1;
	       $this->data[$_data_idx]['childs'][] = $_data;
	   }
	}
	
	
	function getAssocTree() {
	 	$raw=$this->data;
	 	$tree=array();
	 	$this->assoc=$this->toAssoc($raw,$tree);
	 	return $tree;
	}
	
	function toAssoc($data,&$tree) {
	 	foreach ($data as $num=>$node) {
	 	 	$name		= ArrayUtil::getValue($node,'name',FALSE);
	 	 	$content	= ArrayUtil::getValue($node,'content',FALSE);
	 	 	$content	= trim($content);
	 	 	$attr		= ArrayUtil::getValue($node,'attrs',FALSE);
	 	 	$childs		= ArrayUtil::getValue($node,'childs',FALSE);
	 	 	
	 	 	$item		= array();
			if (!empty($content)) {
			 	$item['content']=$content;
			}
			if ($attr) {
			 	$item['attr']=$attr;
			}
			if (is_array($childs)) {
			 	$this->toAssoc($childs,$item);
			}
			if (!isset($tree[$name])) {
			 	$tree[$name]=array();
			}
	 	 	$tree[$name][]=$item;	
	 	}
	 
	}
	
	
	function getXMLNode($node,$path,$return='content') {
	 	$elements	= explode('.',$path);
	 	$walker		= $node;
	 	$pattern	= '/([^\[]*)\[([\d]*)\]/';
	 	$matches	= array();
		 	 
		// walk through given path	  	
	 	for($e=0;$e<count($elements);$e++) {
	 	 	$isLast		=(($e+1)==count($elements)) ? TRUE : FALSE;
	 		$key		= $elements[$e];
	 		$child		= 0;
	 		$getAttr 	= FALSE;
	 		if (strstr($key,'@')) {
	 		 	$keyParts	= explode('@',$key);
	 		 	$key		= $keyParts[0];
	 		 	$getAttr	= $keyParts[1];
	 		}
	 		if (strstr($key,'[')) {
	 		 	preg_match($pattern,$key,$matches);
	 		 	$key=$matches[1];
	 		 	$child=($matches[2]=='') ? '-1' : intval($matches[2]);
	 		}
	 		
	 		if (!isset($walker[$key]) && isset($walker[0])) {
	 		 	$walker=$walker[0];
	 		}
			if (isset($walker[$key])) {
			 	$walker=$walker[$key];
			 	if ($child<0) {
			 	 	return $walker;
			 	}
			 	if (isset($walker[$child])) {
			 	 	$walker=$walker[$child];
			 	}
			}
			else {
			 	// no child or named key found
			 	return FALSE;
			}	
						
			if ($getAttr) {
			 	
	 		 	if (!isset($walker['attr'])) {
	 		 	 	return FALSE;
	 		 	}
	 		 	$attr=ArrayUtil::getValue($walker,'attr',array());
	 		 	if ($getAttr=='' || $getAttr=='*') {
	 		 	 	return $attr;
	 		 	}
	 		 	return ArrayUtil::getValue($attr,$getAttr,FALSE);
	 		}
					  
	 	}
	 	
	 	// walk ends 
	 	// return requested part of current node 
		switch($return) {
		 	case 'content' :  return ArrayUtil::getValue($walker,'content',FALSE);
		 	case 'attr'		: return ArrayUtil::getValue($walker,'attr',FALSE);
		 	case 'node'		: return $walker;
		 	default			: return FALSE;
		}
	}
	
	function getXMLNodeContent($node,$key='content',$default='') {
	 	$content= ArrayUtil::getValue($node,$key,array());
	 	$out=ArrayUtil::getValue($content,0,FALSE);
	 	return $out;
	}
	
	function getXMLNodeAttr($node,$attrName) {
	 	$attr= ArrayUtil::getValue($node,'attr',array());
	 	$out=ArrayUtil::getValue($attr,$attrName,FALSE);
	 	return $out;
	}
}


?>
