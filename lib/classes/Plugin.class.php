<?php


class Plugin {


	var $name		= "Plugin";
	var $events		= array();
	var $config		= array();
	var $output		= '';
	
	function __construct($args=array()) {
	 	if ($this->listen()) {
	 	 	$this->load();
	 	}
	}
	
	
	function onRegister() {
	 
	}
	
	function listen() 
	{
		$events=$this->events;
		return RequestUtil::hasParams($events);
		
	}
 

	function load() {
		$this->configure();
		$this->execute();
		$this->out();	 
	}
	

	function execute() {
	}
 
 	function out() {
 	 	return $this->output;
 	}
 	
 	function addFilter() {
 	 
 	}

	function addHooks() {
	 	
	 
	}
 	
 
}


?>