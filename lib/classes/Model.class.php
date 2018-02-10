<?php

class Model {
 
 	var $Parent				= null;
 	
 	var $DataModel 			= array();
 	var $SectionMap 		= array();
 	var $ColumnMap 			= array();
 	
 	var $DBTables			= array();
 	var $DBTable			= null;
 	
 	var $FormConfigFiles	= array();
 	var $FormConfigFile		= null;
 	var $FormConfigPath		= null;
 	
 	var $FormObject			= null;
 	
 	var $ParentID			= null;
 	var $RecordID			= null;
 	var $RessourceID		= null;
 	
 	var $PrimaryKeys		= array();
 	var $PrimaryKey			= 'id';
 	var $KeyToParent		= 'parentitem';
 	var $RequestItemNames	= array();
 	var $RequestItemName	='itemid';
 	
 	var $DataContext		= null;
 	var $ModelParams		= array();
 	
 	var $isLocal			= FALSE;

 	 	
 	
 	function __construct() {
 	 	$this->init();
 	}
 	
 
 	function init() {
 	 
 	 	
 	}

	function setup($config=array()) {
	 	
	 	if (!is_array($config)) {
	 	 	$config=array();
	 	}
	 
	 	foreach ($config as $key => $val) {
	 	 	$this->ModelParams[$key]=$val;
	 	}
	 
	 	$dataContext=ArrayUtil::getValue($this->ModelParams,'DataContext',array()); 
	 
	 	foreach ($dataContext as $context=>$info) {
	 	 	$dbtable	= ArrayUtil::getValue($info,'dbtable');
	 	 	$pkey		= ArrayUtil::getValue($info,'pkey');
	 	 	$fkeyparent	= ArrayUtil::getValue($info,'fkeyparent');
	 	 	$formconfig	= ArrayUtil::getValue($info,'formconfig');
	 	 	$param		= ArrayUtil::getValue($info,'param');
	 	 	$default	= ArrayUtil::getValue($info,'default',FALSE);
	 	 	
	 	 	$this->DBTables[$context]=$dbtable;
	 	 	$this->FormConfigFiles[$context]=$formconfig;
	 	 	$this->PrimaryKeys[$context]=$pkey;
	 	 	$this->RequestItemNames[$context]=$param;
	 	 	$this->DataModel[$context]=$info;
	 	 	
	 	 	if ($default===TRUE) {
	 	 	 	$this->DBTable			= $dbtable;
	 	 	 	$this->RequestItemName	= $param;
	 	 	 	$this->PrimaryKey		= $pkey;
	 	 	 	$this->KeyToParent		= $fkeyparent;
	 	 	 	$this->FormConfigFile	= $formconfig;
	 	 	 	$this->DataContext		= $context;
	 	 	 	$this->loadFormObject();
	 	 	}	 	
	 	 
	 	}
	 
	 
	}


	function switchContext($context) {
	 	$this->DataContext=$context;
	 	$this->getDBTable($context,TRUE);
	 	$this->getFormConfigFile($context,TRUE);
	 	$this->loadFormObject($context,TRUE);
	 	$this->getPrimaryKey($context,TRUE);
	 	$this->getParentKey($context,TRUE);
	 
	}

	function getDBTable($context='',$register=TRUE) {
	 	if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	$dbtable=ArrayUtil::getValue($this->DBTables,$context,FALSE);
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	
	 	if ($register) {
	 	 	$this->DBTable=$dbtable;
	 	}
 	 	return $dbtable;
 	}
 	
 	function getPrimaryKey($context='',$register=TRUE) {
	 	if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	
	 	$pkey=ArrayUtil::getValue($this->PrimaryKeys,$context,FALSE);
	 	if (!$pkey) {
	 	 	return FALSE;
	 	}
	 	if ($register) {
	 	 	$this->PrimaryKey=$pkey;
	 	}
 	 	return $pkey;
 	}
 	
 	function getParentKey($context='',$register=FALSE) {
 	 	if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	$dataModel	= ArrayUtil::getValue($this->DataModel,$context,array());
	 	$parentKey	= ArrayUtil::getValue($dataModel,'fkeyparent',FALSE);
	 	
	 	if ($register) {
	 	 	$this->KeyToParent=$parentKey;
	 	}
	 	
	 	return $parentKey;
 	}
 	
 	function getRequestItemName($context='',$register=TRUE) {
	 	if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	
	 	$rname=$this->RequestItemNames[$context];
	 	if ($register) {
	 	 	$this->RequestItemName=$rname;
	 	}
 	 	return $rname;
 	}
 	
 	function getFormConfigFile($context='',$register=TRUE) {
 		if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	$formConfigFile=$this->FormConfigFiles[$context];
	 	if ($register) {
	 	 	$this->FormConfigFile=$formConfigFile;
	 	}
 	 	return $formConfigFile;		
	}
	
	function getFormConfigPath($register=TRUE) {
	 	if (empty($this->FormConfigPath)) {
	 	 	$path=$this->Parent->basepath.'.config';
	 	 	if ($register) {
	 	 	 	$this->FormConfigPath=$path;
	 	 	}
	 	}
	 	return $this->FormConfigPath;
	}
	
	
	function loadFormObject($context='',$register=TRUE,$useAsModel=TRUE) {
	 	if (empty($context)) {
	 	 	$context=$this->DataContext;
	 	}
	 	Loader::loadClass('RedaxWebForm','components.com_redax.view');
 	 	$Form=new RedaxWebForm();
 	 	$config=array(
		  	'configPath'	=>$this->getFormConfigPath(),
		  	'configFile'	=>$this->getFormConfigFile($context),
		  	'configLocal'	=>(($this->isLocal) ? TRUE : FALSE),
		);
		$Form->configure($config);
		$Form->make();
		if ($register) {
			$this->FormObject=$Form;
		} 
		
		if ($useAsModel) {
		 	if (!ArrayUtil::hasKey($this->DataModel,$context)) {
				$this->DataModel[$context]=array();
			}
			$this->DataModel[$context]['KEYS']=$Form->getKeys();
			$this->DataModel[$context]['COLUMNS']=$Form->getColumns();
		}
	 
	}

	
	function setParentID($id=null) {
	 	$this->ParentID=$id;
	}
	
	function getParentID() {
	 	$id=($this->ParentID) ? $this->ParentID : 0;
	 	return $id;
	}
	

	function setRecordID($id=null) {
	 	$this->RecordID=$id;
	}
	
	function getRecordID() {
	 	$id=($this->RecordID) ? $this->RecordID : 0;
	 	return $id;
	}

	function setRessourceID($context='') {
	 
	 	if (empty($context)) {
  	 	 	$context=$this->DataContext;
  	 	}
	 	// default:
	 	$out='';
	 	$out.=$this->getDBTable($context);
	 	$out.='.';
	 	$out.=$this->getParentID();
	 	$out.='.';
	 	$out.=$this->getRecordID();
	 	$this->RessourceID=$out;
	 	RequestUtil::setSecureParam('__rid',$out);
	}

	function getRessourceID() {
	 	if (!$this->RessourceID) {
	 	 	$this->setRessourceID();
	 	}
	 	RequestUtil::setSecureParam('__rid',$this->RessourceID);
		return $this->RessourceID;	 
	}
	
	function getItemRessourceID($item=array(),$context='') {
	 	if (empty($context)) {
		  	$context=$this->DataContext;
		}	
		
		$pkey		= $this->PrimaryKey;
		$fkeyparent	= $this->getParentKey($context);	
		$out=''; 
		$out.=$this->getDBTable($context);
	 	$out.='.';
	 	$out.=ArrayUtil::getValue($item,$fkeyparent,0);
	 	$out.='.';
	 	$out.=ArrayUtil::getValue($item,$pkey,0);;
		
		return $out;
	 
	}


	function registerTags($args) {
	 	$data=ArrayUtil::getValue($args,'data',FALSE);
	 	if (!$data) { return; }

	 	$tags	= ArrayUtil::getValue($data,'tags',FALSE);
	 	$taxid	= ArrayUtil::getValue($data,'taxonomy_id',1);
	 	$author	= ArrayUtil::getValue($data,'author',FALSE);
	 	$relid	= $this->getRessourceID();
	 	$user	= SessionUtil::getUID();
	 	if ($tags) {
	 	 	$module=Application::getModule('Taxonomy',FALSE);
	 	 	if ($module) {
	 	 	 	$module->registerTags($tags,$taxid,$relid,$author,$user);
	 	 	}
	 	 	else {
	 	 	 	Application::cache('preSetTags',array($tags,$taxid,$relid,$author,$user));
	 	 	}
	 	}
	 
	}

  	function getDataModel($context='') {
  	 	if (empty($context)) {
  	 	 	$context=$this->DataContext;
  	 	}
  	 	$dataModel=ArrayUtil::getValue($this->DataModel,$context,FALSE);
  	 	if (!$dataModel) {
  	 	 	$this->loadFormObject($context,FALSE,TRUE); 
  	 	}
  	 	return $this->DataModel[$context];
  	 
  	}
  	
  	function getKeys($context='') {
  	 	if (empty($context)) {
  	 	 	$context=$this->DataContext;
  	 	}
  	 	$dataModel=ArrayUtil::getValue($this->DataModel,$context,FALSE);
  	 	if ($dataModel) {
  	 	 	$keys=ArrayUtil::getValue($dataModel,'KEYS',FALSE);
  	 	 	if ($keys) {
  	 	 	 	return $keys;
  	 	 	}
  	 	}
  	 	if ($this->FormObject) {
  	 	 	return $this->FormObject->getKeys();
  	 	}
  	 	return array();
 	 
 	}

 	function getColumns($context='') {
 	 
  	 	if (empty($context)) {
  	 	 	$context=$this->DataContext;
  	 	}
  	 	$dataModel=ArrayUtil::getValue($this->DataModel,$context,FALSE);
  	 	if ($dataModel) {
  	 	 	$cols=ArrayUtil::getValue($dataModel,'COLUMNS',FALSE);
  	 	 	if ($cols) {
  	 	 	 	return $cols;
  	 	 	}
  	 	}
  	 	if ($this->FormObject) {
  	 	 	return $this->FormObject->getColumns();
  	 	}
  	 	return array();
 	 
 	}

  	
  	function defineRequiredCols() { 	 	
 	 	return array();
 	}


	function mapToColumns($array=array()) {
	 	
		 foreach($array as $in=>$out) {
	 	 	$this->ColumnMap[$out]=$in;
	 	}
	 
	}

	function defineRequiredSections() {
 	 	return array();
 	}


	function mapToSections($array=array()) {
	 
	 	foreach($array as $in=>$out) {
	 	 	$this->SectionMap[$out]=$in;
	 	}
	 
	}
 	
 	function getSectionName($name) {
 	 	return ArrayUtil::getValue($this->SectionMap,$name);
 	}
 	
 	function getColName($name) {
 	 
 	 	return ArrayUtil::getValue($this->ColumnMap,$name);
 	}
 	
 	function createTable($context='') {
 	 	if (empty($context)) {
  	 	 	$context=$this->DataContext;
  	 	}
  	 	if (!ArrayUtil::hasKey($this->DataModel,$context,FALSE)) {
  	 	 	$this->getDataModel($context);
  	 	}
  	 	$dbtable=$this->getDBTable($context,FALSE);
  	 	
		$columns=$this->getColumns($context);
 	 	
 	 	if ($this->Parent->DBDriver) {
 	 		$DBDriver= $this->Parent->DBDriver;
 	 	}
 		else {
 		 	$DBDriver= new DBDriver();
 		}
 	 	$DBDriver->createTable($dbtable,$columns);
 	}
 	
 	
	function processStoredData($data) {
	 	$this->registerTags(array('data'=>$data));
	 
	} 	



	function collectItemRelatedInfo(&$item=array(),$context='') {
	 	$item['__rid']=$this->getItemRessourceID($item);
	 	if (!ArrayUtil::hasKey($item,'__relatedInfo')) {
	 		$item['__relatedInfo']=array(); 
	 	}
	 	OverrideUtil::callHooks('Model','Model::collectItemRelatedInfo',$this,$item);
 		 	
 	}


 
 
}


?>