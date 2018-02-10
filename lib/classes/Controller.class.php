<?php

class Controller {
 
 	var $Parent			= null;
 	var $Model			= null;
 	var $Component		= null;
 	var $Module			= null;
 	
 	var $Content='';
 	
 	var $RecordID		= null;
 	var $RecordStatus	= 'UPDATE';
 	
	var $Data			= array();
 	var $DataObjAccess  = array();
 	
 	 
 	function __construct() {	 
 	 
 	}
 
 	function listen() {
 	 
 	 
 	}

	function load() {
	 
	}

	function init() {
	 
	}

	function run() {

		$this->init();	 
	 	$this->dispatch();
	 
	}


	function callView($classname,$args=array(),$loadlocal=FALSE) {
	 
	 	$classpath='';
	 	$parent=$this;
	 	if ($this->Component) {
	 	 	$parent=$this->Component;		
	 	}
	 	if ($this->Module) {
	 	 	$parent=$this->Module;		
	 	}
	 	if ($this->Parent) {
	 	 	$parent=$this->Parent;		
	 	}
		$classpath=$parent->basepath.'.view';
	 	
		$loadpath=ArrayUtil::getValue($args,'classpath',$classpath);
	 	
 	 	Loader::loadClass($classname,$loadpath,$loadlocal);
 	 	$view=new $classname;
 	 	if (is_array($args)) {
 	 	 	$view->configure($args);
 	 	}
 	 	
 	 	$view->Controller=$this;
 	 	$view->call();
 	 	
 	 	$view->output.=$this->afterView();
 	 	
 	 	
 	 	return $view->out();
	 
	}


	/* Dispatcher */

	function dispatch() {
	 
	}


	/* Loader */
	
	 	
 
	
	function onLoadData($data) {
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,null,$data);
	 	$this->registerData($data);
	}
	
	function registerData($data) {
	 	$this->Data=$data;
	 	$this->getDataObjAccess();
	 	
	 	$pkey		= $this->Parent->Model->PrimaryKey;
	 	$id 		= ArrayUtil::getValue($data,$pkey,0);
	 	
	 	$fkeyparent	= $this->Parent->Model->KeyToParent;
	 	$parentid	= ArrayUtil::getValue($data,$fkeyparent,0);
	 	
	 	$this->Parent->Model->setRecordID($id);
	 	$this->Parent->Model->setParentID($parentid);
	 	$this->Parent->Model->setRessourceID();
	 	
	 	if (empty($this->Data)) {
	 	 	$this->onEmptyData();
	 	}
	}

	function onEmptyData() {
	 	$this->DataObjAccess['deny']=array('EXEC','VIEW','EDIT','UPDATE','DELETE');
	}
	
	function getDataObjAccess($data=array()) {
	 		
	 	if (!$data) {	
	 		$data=$this->Data;
	 	}
		$objAccess		= ArrayUtil::getValue($data,'accessrights','0000');
		$objOwner		= ArrayUtil::getValue($data,'author','');
		$objGroups		= ArrayUtil::getValue($data,'accessgroups','');
		
		$denyFromAll	= array();
		$allowFromAll	= array();
		
		$out=	array(
		 			'objAccess'	=>$objAccess,
					'objOwner'	=>$objOwner,
					'objGroups'	=>$objGroups,
					'deny'		=>$denyFromAll,
					'allow'		=>$allowFromAll,
					
				);
		$this->DataObjAccess=$out;
		return $out;
	 
	}
	
	
	function denyFromAll($array=array()) {
	 	if ($this->DataObjAccess) {
	 	 	$this->DataObjAccess['deny']=array();
	 	}
	}
	
	function allowFromAll($array=array()) {
	 	if ($this->DataObjAccess) {
	 	 	$this->DataObjAccess['allow']=array();
	 	}
	}
	
	
	
	function accessControlOnCommand($cmd) {
	 
	 	$dataObjAccess=$this->getDataObjAccess();
		switch (strtolower($cmd)) {
	 	 	case 'new' : 
			  			$perm=ModAccess::checkObjPermission($dataObjAccess,'CREATE');
						if (!$perm) { $cmd='view'; }
						break;   
	 	 	
	 	 	case 'edit' : 
			  			$perm=ModAccess::checkObjPermission($dataObjAccess,'UPDATE');
						if (!$perm) { $cmd='view'; }
						break;
	 	 
	 	}
		return $cmd;
	 
	}
	
	
	function getSelection() {
	 	$selection=array();
	 
	 	return $selection;
	 
	}	
	
	
	function getLoop($conf=array()) {
	 	$data=array();
	 	$context=$this->Parent->Model->DataContext;
	 
	 	if (empty($context)) { return $data; }
	 	
	 	$dbtable=$this->Parent->Model->getDBTable($context,FALSE);
	 	if (!$dbtable) {
	 	 	return  $data;
	 	}
	 
	 	
	 		
	 	$dbDriver=$this->Parent->DBDriver;
	 	if (!$dbDriver) {
	 	 	return $data;
	 	}
	 	$args=array(
	 				'dbtable'	=> $dbtable,
	 				'columns'	=> array('*'),
	 				'limit'		=> 100,
	 				);
	 				
	 	$overrideArgs=array('sortOrder'=>FALSE,'sortBy'=>FALSE,'limit'=>FALSE,'filter'=>FALSE,'columns'=>FALSE);
		foreach ($overrideArgs as $key=>$value) { 			
	 		$newArg=ArrayUtil::getValue($conf,$key,$value);
			if ($newArg) {
		 		$args[$key]=$newArg;
			}
		} 			
		
	 	$loop=$dbDriver->select($args);
	 	return $loop;
	 
	}



	/* CRUD */
		


	function getRecord($context='') {
	 	if (empty($context)) {
  	 	 	$context=$this->Parent->Model->DataContext;
  	 	}
	 		
	 	$dbDriver			= $this->Parent->DBDriver;
	 	$dbTable			= $this->Parent->Model->getDBTable($context);
	 	$pkey				= $this->Parent->Model->getPrimaryKey($context);
	 	$requestParamName	= $this->Parent->Model->getRequestItemName($context);
	 	$recordID			= RequestUtil::getParam($requestParamName);
	 	
	 	if ($this->RecordID) { $recordID=$this->RecordID; }
	 	
	 	$args=array(
		 	'dbtable'=>$dbTable,
		 	'column'=>$pkey,
		 );
		// ErrorHandler::dump(__CLASS__,__METHOD__,$args);
		// ErrorHandler::dump(__CLASS__,__METHOD__,$recordID);
		 
	 	$data=$dbDriver->getRecord($recordID,$args);
	 	
	 	// ErrorHandler::dump(__CLASS__,__METHOD__,$data);
	 	$this->onLoadData($data);
	 	
	 	if (!$data) {
	 	 	$this->RecordStatus='CREATE';
	 	 	return array();
	 	}
	 	
	 	return $data;
	}

	function storeRecord($context='') {
	 	if (empty($context)) {
  	 	 	$context=$this->Parent->Model->DataContext;
  	 	}
	 
	 	$storedData		= $this->Data;
	 	$newData		= $this->getRequestData($context);
		$dbDriver		= $this->Parent->DBDriver;
		$dbtable		= $this->Parent->Model->getDBTable($context);
		
		$this->Parent->Model->createTable($context);
		
		if ($this->RecordStatus=='CREATE') {
			$result=$this->createRecord($newData,$dbtable,$dbDriver,$context);
			$this->updateRecordID($result);
			$this->onCreateRecord($result);
			$this->onStoreData($result);
		}
		else {	
			$result=$this->updateRecord($newData,$storedData,$dbtable,$dbDriver,$context);
			$this->updateRecordID($result);
			$this->onUpdateRecord($result);
			$this->onStoreData($result);
		}
	 	
	 
	}
	 
	 
	function createRecord($newdata=array(),$dbtable='',$dbDriver=null,$context='') {
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (!$dbDriver) {
	 	 	return FALSE;
	 	}
	 	$insertID=$dbDriver->insert($newdata,array('dbtable'=>$dbtable,'DBDriver'=>$dbDriver));
	 	$result=array(
	 		'insertData'	=> $newdata,
	 		'newData'		=> $newdata,
	 		'oldData'		=> array(),
	 		'insertID'		=> $insertID,
	 		'dbtable'		=> $dbtable,
	 		'dbDriver' 		=> $dbDriver,
	 		'context'		=> $context,
	 	);
	 	return $result;
	}
	
	function onCreateRecord($result=array()) {
	 	if (!$result || !is_array($result)) {
	 	 	return;
	 	}
	}

	function updateRecord($newdata=array(),$olddata=array(),$dbtable='',$dbDriver=null,$context='') {
	
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (!$dbDriver) {
	 	 	return FALSE;
	 	}
	 	
	 	
	 	$success=$dbDriver->update($newdata,$olddata,array('dbtable'=>$dbtable,'DBDriver'=>$dbDriver));
		
	 	$result=array(
	 		'oldData'		=> $olddata,
	 		'newData'		=> $newdata,
	 		'dbtable'		=> $dbtable,
	 		'dbDriver' 		=> $dbDriver,
	 		'success'		=> $success,
	 		'context'		=> $context,
	 	);
	 	
	 
	 	
	 	return $result;
	} 

	function onUpdateRecord($result=array()) {
	 	if (!$result || !is_array($result)) {
	 	 	return;
	 	}
	}

	function updateRecordID($fromResult=array()) {
	 	$pkey		= $this->Parent->Model->getPrimaryKey($context);
	 	$context	= ArrayUtil::getValue($fromResult,'context');
	 	$newData	= ArrayUtil::getValue($fromResult,'newData');
	 	$insertID	= ArrayUtil::getValue($fromResult,'insertID',FALSE);
	 	
		$recordID	= ArrayUtil::getValue($newData,$pkey);
	 	if ($insertID && $pkey=='id') {
	 	 	$recordID = $insertID;
	 	}
	 	$this->RecordID=$recordID;
	 
	}


	function onStoreData($result) {
		if (!$result || !is_array($result)) {
	 	 	return;
	 	}
	 	$stored=ArrayUtil::getValue($result,'newData',FALSE);
		if (is_array($stored)) {
		 	$this->Parent->Model->processStoredData($stored);
		} 	
	}




	function deleteRecord($data=array()) {
	 
	 
	 
	}

	function checkLock($setLock=TRUE) {
	 
		$lockInfo	= $this->lockRecord($setLock);
	 	$isLocked	= ArrayUtil::getValue($lockInfo,'isLocked',FALSE);
	 	$lockMsg	= ArrayUtil::getValue($lockInfo,'lockMsg',FALSE);
	 	
		if ($isLocked) {
		  	$alerts	= array();
		  	HTMLFragment::addAlert($lockMsg,'item',$alerts);
	 	 	$out	= HTMLFragment::renderAlerts($alerts); 	
	 	}
	 	else {
	 	 	$alerts = array();
	 	 	HTMLFragment::addAlert($lockMsg,'item',$alerts);
	 	 	$out	= HTMLFragment::renderAlerts($alerts,'warn-friendly');
	 	}
	 	
	 	return $out;
	}
	
	function lockRecord($setLock=TRUE) {
	 	$stat		= ModAccess::getLockedStateValue();
	 	$isLocked   = ModAccess::isLocked($this->Data);
	 	
		if (!$isLocked && $setLock) {
	 		$this->setRecordAttr('accessstate',$stat);
	 		$this->Data['accessstate']=$stat;
	 		$this->getRecord();
	 	}
	 	
	 	$lockMsg	= ModAccess::getLockMsg($this->Data,$setLock);
	 	$info		= array(
	 				'isLocked' 	=> $isLocked,
	 				'lockMsg'	=> $lockMsg,
	 				);

	 	
	 	return $info;
	 
	}


	function setRecordAttr($key,$val) {
	 	$DBDriver	= $this->Parent->DBDriver;
	 	$dbtable	= $this->Model->getDBTable();
	 	$args		= array('DBDriver'=>$DBDriver,'dbtable'=>$dbtable);
	 	$data		= array($key=>$val);
	 	$compare	= array('id'=>ArrayUtil::getValue($this->Data,'id'));
	 	
		$DBDriver->update($data,$compare,$args);
	 	
	}




	function addContent($content) {
	 	$this->Content.=$content;
	}
	
	function clearContent() {
	 	$this->Content='';
	}
	
	function addContentCallback($method,$args=array()) {
	 	$this->Content.=OverrideUtil::getCallbackResult($method,$args);
	}
	
	function getContent() {
	 	return $this->Content;
	}
	
	
	function beforeContent() {
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	}
	
	function afterContent() {
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	}
	
	function afterView() {	
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	 	
	}
	
	function afterCommentable() {
	 	$data=$this->Data;
	 	$enableComments = ArrayUtil::getValue($data,'enableComments',FALSE);
	 	if (intval($enableComments)==1) {
	 		OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	 	}
	}
 
 
 	function afterUpdate() {
 		OverrideUtil::callHooks(__CLASS__,__METHOD__,$this); 
 	}
 
 
 
 	function getRequestData() {
	 	$model=$this->Parent->Model;
	 	$keys=$model->getKeys();
	 	
	 	$data=array();
	 	
	 	if (!is_array($keys)) {
	 	 	return $data;	
	 	}
	 	
	 	foreach($keys as $key) {
	 	 	$data[$key]=RequestUtil::getParam($key);
	 	}
	 	return $data;
	 
	}
	
	function clearRequestData() {
	 	$model=$this->Parent->Model;
	 	$keys=$model->getKeys();
	 	foreach($keys as $key) {
	 	 	RequestUtil::unsetParam($key);
	 	}
	 	RequestUtil::unsetParam('ffsubmit');
	}
 
 
 
 	function loadTemplateByName($tmplName) {
 	 	if ($this->Parent) {
 	 	 	$tmpl=array(
			   'loadfile'=>$tmplName.'.php',
			);
			$args=array('template'=>$tmpl);
			$out= $this->Parent->loadTemplate($args);
			return $out;
 	 	}
 	 	return FALSE;
 	 
 	}
 	
 	
 	
 	function getDefaultHooks($data=array()) {
 	 	if (!$data || empty($data)) {
 	 		$data=$this->Data;
 	 	}
 	 	
 	 	$hooks=array(
			 			array(
				 			'event'=>'onConfig',
				 			'callback'=>'setDBDriver',
				 			'args'=>array('DBDriver'=>$this->Parent->DBDriver),
				 		),
						array(
			 				'event'=>'onLoad',
			 				'callback'=>'loadFormValues',
			 				'args'=>array('data'=>$data),
			 			),
			 			
			 			array(
 							'event'=>'onValidSubmit',
 							'callback'=>'prepareToStore',
 						),
			 			
			 			array(
			 				'event'=>'onValidSubmit',
			 				'callback'=>array($this,'storeRecord'),
			 			),
			 			array(
			 				'event'=>'onValidSubmit',
			 				'callback'=>array($this,'afterUpdate'),
			 			),
			 		);
 	 
 	 	return $hooks;
 	  
 	}
 	
 	
 	
 	
 	
 	
 
}


?>