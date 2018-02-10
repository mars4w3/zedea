<?php

class View {
 
 	var $Model		= null;
 	var $Controller = null;
 	
	var $config		= array();
	var $output		= '';
	var $setClean	= FALSE;	
	
	var $Hooks=array();
 
 	function __construct() {	 
 	 
 	}

	function init() {
	 
	}

	function configure($config=array()) {
	 	$this->config=$config;
	}

	function call() {
	 	$this->init();
	 	$this->execute();
	 
	}
	
	function addContent($content) {
	 	$this->output.=$content;
	}
	
	
	function execute() {
	 
	}

	function out() {
	 	if (!$this->setClean) {
	 	 	return $this->output;
	 	}
	 
	}

	function exit_clean() {
	 	$this->output	= '';
	 	$this->setClean	= TRUE;
	}



	function getItem($id=0) {
	 
	 
	 
	}

	function getItemList() {
	 
	 
	}

	function getIntro($return=FALSE,$translate=FALSE) {
 	 	
 	 	$out='';
 	 	
 	 	$viewTitle=ArrayUtil::getValue($this->config,'viewTitle','');
 	 	$viewIntro=ArrayUtil::getValue($this->config,'viewIntro','');
 	 	
 	 	
 	 	if (!empty($viewTitle)) {
 	 	 	$text=$viewTitle;
 	 	 	if ($translate) {
 	 	 	 	$text=Babel::_($text);	
 	 	 	}
 	 		$args=array();
 	 		HTMLFragment::setAttr('class','item-title view-title',$args);
 	 		$fragment=HTMLFragment::renderHeading($text,1,$args);
 	 		$out.=$fragment;
 	 	}
 	 	if (!empty($viewIntro)) {
 	 		$text=$viewIntro;
 	 	 	if ($translate) {
 	 	 	 	$text=Babel::_($text);	
 	 	 	}
 	 		$args=array();
 	 		HTMLFragment::setAttr('class','item-intro view-intro',$args);	
 	 		$fragment=HTMLFragment::renderSection($text,$args);
 	 		$out.=$fragment;
 	 	}
 	 	
 	 	if ($return) {
 	 		return $out;
 	 	}
 	 	$this->addContent($out);
 	 
 	 
 	}
 
 
 	function getInfo($return=FALSE,$translate=FALSE) {
 	 
 	 	$out='';
 	 	
 	 	$infoTitle=ArrayUtil::getValue($this->config,'infoTitle','');
 	 	$infoText=ArrayUtil::getValue($this->config,'infoText','');
 	 	
 		if (empty($infoTitle) && empty($infoText)) {
 		 	return $out;
 		}
 
 		$out.=HTMLFragment::renderWidgetBox($infoTitle,$infoText,'section');
		
		$boxArgs=array();
 	 	HTMLFragment::setAttr('class','info-box',$boxArgs);
 	 	$out=HTMLFragment::renderSection($out,$boxArgs);
 	 	
 	 	if ($return) {
 	 		return $out;
 	 	}
 	 	$this->addContent($out);
 	 	
 	}
 	
 	
 	function setHooks($hooks=array()) {
	 	$this->Hooks=$hooks;
	}

 
 
 
}


?>