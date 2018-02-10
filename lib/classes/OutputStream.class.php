<?php

class OutputStream  {
 
 	var $output='';
 	var $destination='';
 	var $device=null;
 
	var $outputEncoding='utf-8';
	var $inputEncoding='utf-8';



	function __construct() {
	 	$this->OutputStream;
	} 
	
	function OutputStream() {
	 	
	}

 

	function append($data) {
	 
	 	$this->output.=CastUtil::toString($data);
	} 
 
 
 	function encode() {
 	 	
 	}
 
	function out() {
	
	 	echo $this->output;
	}

 
}


?>