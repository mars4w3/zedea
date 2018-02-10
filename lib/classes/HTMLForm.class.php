<?php

class HTMLForm  extends HTMLFragment {
 	
 	var $Submit=FALSE;
	var $Valid=FALSE;
	var $Err=FALSE;
	
	var $Done=FALSE;

	var $FormTitle=''; 
	var $CheckInfo=array();
	
	var $Hooks=array();
	
	var $Declaration=array();
	
	var $DBDriver=null;
	
	var $PrintAll=FALSE;
	
	var $config = null;
 
 
 	function __construct() {
	}


	function createForm($config=array()) {
 	 
 	 	$this->configure($config);
		$this->init();
		$this->build();
		
 	}

	function configure($config=array()) {
	 	$this->config=$config;
	 	
	 	// call hooks to optimize/change/translate etc the loaded config
	 	OverrideUtil::callHooks('HTMLForm','HTMLForm::onLoadConfig',null,$this->config);
	 	
	 	$this->declaration();
	 	$this->onConfig();
	}
	
	
	function declaration() {
	 	$config=$this->config;
	 	
	 	$custom=ArrayUtil::getValue($config,'Declaration',array());
	 	$declaration=array();
	 	
	 	$defaults=array(
		 	'FormConfig' 			=> 'FormConfig',
		 	'FormGroupsConfig' 		=> 'FormConfig',
		 	
		 	'FormGroupID' 			=> 'fg_id',
		 	'FormGroupLegend' 		=> 'fg_legend',
		 	'FormGroupDescr' 		=> 'fg_descr',
		 	'FormGroupPrintable' 	=> 'fg_print',
		 	'FormGroupFields' 		=> 'fg_fields',
		 	'FormGroupHidden' 		=> 'fg_hidden',
		 	'FormGroupCSSClass' 	=> 'fg_cssclass',
		 
		 );
		 
		foreach ($defaults as $key=>$default) { 	
	 		$declaration[$key]=ArrayUtil::getValue($custom,$key,$default);
	 	}
	 	
		$this->Declaration=$declaration;	
	 
	}

	function init() {
	 	
	 	$this->Submit=($this->getRequestParam('ffsubmit')==='ffsubmit') ? TRUE : FALSE;
	}
	
	
	function addHook($event='',$funcname='',$args=array()) {
	
	 
	 	if (empty($event)) {
	 	 	return FALSE;
	 	}
	 	if (empty($funcname)) {
	 	 	return FALSE;
	 	}
		
		$callback=$funcname;
		if (!is_array($funcname)) {
		 	if (method_exists($this,$funcname)) {
		 	 	$callback=array($this,$funcname);
		 	}
		}
		
	
	 	
	 	$this->Hooks[$event][]=array('funcname'=>$callback,'args'=>$args);
	 	
	 	
	 
	}
	
	
	function onConfig() {
	 	$this->callHooks('onConfig');
	}
	
	function onLoad() {
		$this->callHooks('onLoad');
		 
	}
	
	function onDoForm() {
	 
	 	// call global hooks to optimize/change/translate etc the loaded config
	 	OverrideUtil::callHooks('HTMLForm','HTMLForm::onDoForm',null,$this->config);
	 	
		$this->callHooks('onDoForm');
	 
	}
	

	function onValidSubmit() {
		$this->callHooks('onValidSubmit');		
	}
	
	function onDone() {
		$this->callHooks('onDone');	
	}
	
 
 	function callHooks($event) {
 	 	$hooks=ArrayUtil::getValue($this->Hooks,$event,array());
		$args=array();
	 	foreach ($hooks as $hook) {
	 	 	$err=FALSE;
	 	 	$funcname=$hook['funcname'];
	 	 	$userfunc=(!is_array($funcname)) ? array($this,$funcname) : $funcname;
	 	 	$args=$hook['args'];
	 	 	$err=call_user_func($userfunc,$args);
	 	 	if ($err) {
				ErrorHandler::Err('99999','<strong>HTMLForm::callHooks '.$event.'</strong> - no result on callback function <i>'.$userfunc[1].'</i> with args: '.var_export($args,TRUE));
			}
	 	}
 	 
 	}
 

	function isPrintable($default) {
	 	if ($this->PrintAll) {
	 	 	return TRUE;
	 	}
	 	return $default;
	 
	}	
	
	function setPrintable($bool) {
	 	$set=CastUtil::toBoolean($bool);
	 	$this->PrintAll=$set;
	 
	}
	
	
	function hasFormConfig() {
	 	$key=ArrayUtil::getValue($this->Declaration,'FormConfig');
	 	return ArrayUtil::hasKey($this->config,$key);
	}
	
	function getFormGroups() {
	 	$key=ArrayUtil::getValue($this->Declaration,'FormGroupsConfig');
	 	return ArrayUtil::getValue($this->config,$key,array());
	}
	
	function getFormGroupData($config=array(),$count=0) {
	 	$defaults=array(
		 	'ID'		=> 'fg_'.$count,
			'Legend'	=> '',
			'Descr'		=> '',
			'Fields'	=> array(),
			'Printable'	=> TRUE,
			'Hidden'	=> FALSE,
			'CSSClass'	=> '',
		 
		);
		
		$info=array();
		$prefix='FormGroup';
		foreach ($defaults as $key=>$default) {
		 	$keyname=ArrayUtil::getValue($this->Declaration,$prefix.$key);
	 		$info[$key]=ArrayUtil::getValue($config,$keyname,$default);
	 	}
	 	return $info;
	 
	}
	
	
	function build($elements=array()) {
	 
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
		 
	 	$this->output='';
	 
	 	if ($this->Submit) {	
	 		$this->checkForm();	
	 	}
	 	if ($this->Valid) {
	 	 	RequestUtil::unsetParam('ffsubmit');
	 		$this->onValidSubmit();
	 		$this->Done=TRUE;
	 		$this->onDone();
	 		
	 		return;
	 	}
	 	else {
	 	 	if (!$this->Submit) {
	 	 	 	$this->onLoad();
	 	 	}
	 	 	$this->buildForm();
	 	}
	 	
	 	 
	}
	
	
	


	function out() {
	 	$out=$this->output;
	 	$out=stripslashes($out);
	 	return $out;
	}
 




	function getRequestParam($key='',$default='') {
	 
	 	$out=RequestUtil::getParam($key,$default);
	 	return $out;
	}
 

	

	function thanks($args=array()) {
		
	 	$default='Thank you.';
	 	$message=ArrayUtil::getValue($args,'msg',$default);
	 	$this->output=$message;
	}

	
	



	function getMailFormBody() {
	 
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 
	 	$this->onDoForm(); 
	 
		$out=''; 	
		$ffConfig=$this->getFormGroups();
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {
 		 
 		 	
 		 
 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
	 		$fg_id=$fginfo['ID'];
	 		$fg_legend=$fginfo['Legend'];
	 		$fg_descr=$fginfo['Descr'];
		 	$fg_ff=$fginfo['Fields'];
		 	
		 	$print=($fginfo['FormGroupPrintable']===FALSE) ? FALSE : TRUE;
	 
	 		if ($print) {
	 			for ($ff=0;$ff<count($fg_ff);$ff++) {
	 
	 				$ffi=$fg_ff[$ff]; 
	 				$out.='';
	  				$out.=$this->ffOutput($ffi);
	  				$out.="\n";
			  
				}
			}
 		} 
 	
 		return $out;
	 
	}
	
	
	
	function printForm() {
	 
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 
	 	$this->onDoForm(); 
	 
		$out=''; 	
		$ffConfig=$this->getFormGroups();
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {
 		 
 		 	$fgrp=$ffConfig[$f];
	 		$fginfo=$this->getFormGroupData($fgrp,$f); 
	 		$fg_id=$fginfo['ID'];
	 		$fg_legend=$fginfo['Legend'];
	 		$fg_descr=$fginfo['Descr'];
		 	$fg_ff=$fginfo['Fields'];
		 	
		 	$print=(CastUtil::toBoolean($fginfo['Printable'])===FALSE) ? FALSE : TRUE;
		 	$print=$this->isPrintable($print);
		 	$hidden=ArrayUtil::getValue($fginfo,'Hidden',FALSE);
		 	$style=(!$print || $hidden) ? ' style="display:none;"' : '';
	 		
	 
	 /*
	 		if ($print) {
	 			for ($ff=0;$ff<count($fg_ff);$ff++) {
	 
	 				$ffi=$fg_ff[$ff]; 
	 				$out.='';
	  				$out.=$this->ffOutput($ffi);
	  				$out.="\n";
			  
				}
			}
				*/
			if ($print) {
				$out.="\n";
			 	$out.='<div class="fieldset-print">';
			 	if (!empty($fg_legend)) {
			 		$out.='<div class="legend-print">'.$fg_legend.'</div>';
			 	}
			 	
			 	if (!empty($fg_descr)) {
			 	 	$out.='<div class="descr">'.$fg_descr.'</div>';
			 	}
			 	
			 
			 	for ($ff=0;$ff<count($fg_ff);$ff++) {
			 
			 			$ffi=$fg_ff[$ff]; 
		 				$out.='';
		  				$out.=$this->ffOutput($ffi);
		  				$out.="\n";
					  
				}
				
				$out.='</div>';
				
			}
			
			

 		} 
 	
 		$this->output=$out;
	 
	}
	
	
	function collect($withvalues=TRUE) {
	 
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 
	 
		$out=array(); 	
		$ffConfig=$this->getFormGroups();
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {
 		 
 		 	$fgrp		= $ffConfig[$f];
 		 	$fginfo		= $this->getFormGroupData($fgrp,$f); 
	 		$fg_id		= $fginfo['ID'];
	 		$fg_legend	= $fginfo['Legend'];
	 		$fg_descr	= $fginfo['Descr'];
		 	$fg_ff		= $fginfo['Fields'];
		 	
		 	$fg_collect	= ArrayUtil::getValue($fginfo,'Collectable',TRUE);
		 	
		 	$collect	=($fg_collect===FALSE) ? FALSE : TRUE;
	 
	 		$collect=TRUE;
	 		if ($collect) {
	 			for ($ff=0;$ff<count($fg_ff);$ff++) {
	 
	 				$ffi=$fg_ff[$ff];
					$name=$ffi['name']; 
					if ($withvalues) { 
	 					$out[$name]=$this->ffOutputValue($ffi);	
					} 
					else {
					 	$out[$name]=TRUE;
					}		  
				}
			}
 		} 
 	
 		return $out;
	 
	}
	
	
	

	
	
	
	function getKeys() {
	 
	 	$data=$this->collect(FALSE);
	 	return ArrayUtil::getKeys($data);

	}
	function getValues() {
	 
	 	$data=$this->collect();
	 	return ArrayUtil::getValues($data);
	}


	function getColumns() {
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 	 
		$out=array(); 	
		$ffConfig=$this->getFormGroups();
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {
 		 
 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
 		 	$fg_ff=$fginfo['Fields'];
	 
 			for ($ff=0;$ff<count($fg_ff);$ff++) {
 			 	$ff_conf=$fg_ff[$ff];
 			 	$isdbcolumn=ArrayUtil::getValue($ff_conf,'isdbcolumn',TRUE);
 			 	if ($isdbcolumn) {
 					$out[]=$ff_conf; 
 				}
			}
 		} 
	 	return $out;
	 
	} 


	function checkForm() {
	
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 
	 	$this->onDoForm(); 
	 
		$out=''; 	
		$ffConfig	= $this->getFormGroups();
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {
 		 
 		 	$fgrp		= $ffConfig[$f];
 		 	$fginfo		= $this->getFormGroupData($fgrp,$f); 
	 		$fg_id		= $fginfo['ID'];
	 		$fg_legend	= $fginfo['Legend'];
	 		$fg_descr	= $fginfo['Descr'];
		 	$fg_ff		= $fginfo['Fields'];
		 	
		 	$print=($fginfo['Printable']===FALSE) ? FALSE : TRUE;
		 	
	 
 			for ($ff=0;$ff<count($fg_ff);$ff++) {
 
 				$ffi=$fg_ff[$ff]; 
  				$out.=$this->ffCheck($ffi);
		  
			}
 		} 
 		
 		if ($this->Err) {

			$formMessages	= ArrayUtil::getValue($this->config,'Messages',array());
			$errorHeader	= ArrayUtil::getValue($formMessages,'FormError','');
 		 
 		 	$this->output.='<div class="alert error">';
 		 	$this->output.=$errorHeader;
 		 
 		 	$this->output.= '<ul>';
 		 	$this->output.= $out;	
 		 	$this->output.= '</ul>';
 		 	$this->output.='</div>';
 		 
 		}
 		else {
 		 	$this->Valid=TRUE;
 		}
	 	
	}


	function buildForm($args=array()) {
	 	
		if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 
	 	$this->onDoForm(); 


	 	$this->config['Form']['method']=ArrayUtil::getValue($args,'method','post');
	 	$this->config['Form']['action']=ArrayUtil::getValue($args,'action','');
	 	$this->config['Form']['accept-charset']=ArrayUtil::getValue($args,'accept-charset','utf-8');
	 	$this->config['Form']['enctype']=ArrayUtil::getValue($args,'enctype','multipart/form-data');
	 	
	 	$out='';
	 	
	 	$out.='<form ';
	 	$out.=RenderUtil::renderAttrList($this->config['Form']);
	 	$out.='>';
	 	
	 	$out.=$this->afterFormOpen();
		
	 	$out.="\n";
	 
	 	$out.='<h3 class="form-title">'.$this->FormTitle.'</h3>'; 

		$out.="\n";
		
	 
	 	$ffConfig=$this->getFormGroups();
	 	
	 	
	 	for ($f=0;$f<count($ffConfig);$f++) {
	 	
 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
	 		$fg_id=$fginfo['ID'];
	 		$fg_id_disp=(!empty($fg_id)) ? $fg_id : substr('0000'.($f+1),-4) ;
	 		$fg_legend=trim($fginfo['Legend']);
	 		$fg_descr=trim($fginfo['Descr']);
		 	$fg_ff=$fginfo['Fields'];
		 	
		 	$fgCssClass=ArrayUtil::getValue($fginfo,'CSSClass','');
		 	$fg_classattr= (!empty($fgCssClass)) ? ' class="'.$fgCssClass.'" ' :'';
		 	
		 	$print=(CastUtil::toBoolean($fginfo['Printable'])===FALSE) ? FALSE : TRUE;
		 	$print=$this->isPrintable($print);
		 	$hidden=ArrayUtil::getValue($fginfo,'Hidden',FALSE);
		 	$style=(!$print || $hidden) ? ' style="display:none;"' : '';
	 		
	 		$out.="\n";
		 	$out.='<fieldset '.$style.' '.$fg_classattr.' id="ffgrp-'.$fg_id_disp.'">';
		 	if (!empty($fg_legend)) {
		 		$out.='<legend>'.$fg_legend.'</legend>';
		 	}
		 	
		 	if (!empty($fg_descr)) {
		 	 	$out.='<div class="descr">'.$fg_descr.'</div>';
		 	}
		 	
		 
		 	for ($ff=0;$ff<count($fg_ff);$ff++) {
		 
		 		$ffi=$fg_ff[$ff]; 
		  		$out.=$this->ffInput($ffi);
				  
			}
			
			$out.='</fieldset>';	 
			$out.="\n";		
	 	 
	 	}
	
	
		$out.='<div style="display:none"><input type="hidden" name="ffsubmit" value="ffsubmit"/></div>';
	
		$out.=$this->beforeFormClose();
	
		$out.='</form>';
		$out.="\n";

	 	$this->output.=$out;
	 
	 
	}


	function afterFormOpen() {
	 	
		$out='';
	 	$out.=OverrideUtil::getCallbackResult(__METHOD__);
	 	return $out;
	 
	}
	
	function beforeFormClose() {
	 	
		$out='';
	 	$out.=OverrideUtil::getCallbackResult(__METHOD__);
	 	return $out;
	 
	}


	function ffCheck($conf=array()) {
			
			$out='';
	
			$f_name			= ArrayUtil::getValue($conf,'name','');
			$f_title		= ArrayUtil::getValue($conf,'label','');
			$f_type			= ArrayUtil::getValue($conf,'checktype','');
			$checkpattern	= ArrayUtil::getValue($conf,'checkpattern','');
	 	 	
			$f_value		= $this->getRequestParam($f_name,'');
	 	 	
			$mandatory		= ArrayUtil::getValue($conf,'mandatory',FALSE);
	 	 	$mand			= (CastUtil::toBoolean($mandatory)===TRUE) ? TRUE : FALSE;
	 	 	
	 		$this->CheckInfo[$f_name]=TRUE;
	 		
	 		// csutom check by formfield class
	 		if ($f_type=='ffcheck') {
	 		 	$f_inputtype=$conf['inputtype'];
	 		 	$fField=HTMLFormField::factory('FF_'.$f_inputtype) ; 
				$checkresult=$fField->checkInputValue($conf);
				if (!$checkresult) {
					$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 		$out='<li>';
					$out.=Babel::_('FormCheck::Invalid value at field %%FormColumn%%',$babelRepl);
					$errMsg=$fField->getErrMsg();
					if(!empty($errMsg)) {
					 	$out.='<div>'.$errMsg.'</div>';
					}
					$out.='</li>'; 
								
					$this->CheckInfo[$f_name]=FALSE;
					$this->Err=TRUE;
				}
	 		}
	 		
	 		
	 		if ($f_type=='fileupload') {
			 	
			 	if (RequestUtil::hasUploads()) {
			 	 
			 	 	$isUpload=( RequestUtil::hasUpload($f_name) ||  RequestUtil::hasUpload($f_name.'_upload') );
			 	 	if ($isUpload) {
			 			$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 			$out='<li>';
						$out.=Babel::_('FormCheck::FileUpload. Please confirm at %%FormColumn%%',$babelRepl);
						$out.='</li>';
						$this->CheckInfo[$f_name]=FALSE;
						$this->Err=TRUE;
					}
				}
			 
			} 
	 		
	 		
	 		if ($mand && empty($f_value)) {
			 	$this->Err=TRUE;
				//$out='<li>Bitte das Feld <em>'.$f_title.'</em> ausfüllen.</li>';  
				
				$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 	$out='<li>';
				$out.=Babel::_('FormCheck::NoValue at mandatory field %%FormColumn%%',$babelRepl);
				$out.='</li>'; 
								
				
				$this->CheckInfo[$f_name]=FALSE;
			}

			else if (!empty($f_value)) {
			 	switch ($f_type) {
			 	 	case  'email' :
			 	 			//$valid=(!(eregi('^[\.a-zA-Z0-9_-]+@[\.a-zA-Z0-9-]+$', $f_value))) ? FALSE : TRUE;
			 	 			$valid=(!(preg_match('/^[\.a-zA-Z0-9_-]+@[\.a-zA-Z0-9-]+$/', $f_value))) ? FALSE : TRUE;
			 	 			if (!$valid) {
			 	 			 	$this->Err=TRUE;
			 	 			 	
			 	 			 	$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 	 			 	$out='<li>';
								$out.=Babel::_('FormCheck::InvalidValue at %%FormColumn%%',$babelRepl);
								$out.='</li>'; 
								
								$this->CheckInfo[$f_name]=FALSE; 
			 	 			}
			 	 			break;
			 	 	case  'equal' :
			 	 			$valid=($f_value===$checkpattern) ? TRUE : FALSE;
			 	 			if (!$valid) {
			 	 			 	$this->Err=TRUE;
			 	 			 	
			 	 			 	$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 	 			 	$out='<li>';
								$out.=Babel::_('FormCheck::ValueNotEqual at %%FormColumn%%',$babelRepl);
								$out.='</li>'; 
								
								$this->CheckInfo[$f_name]=FALSE; 
			 	 			}
			 	 			break;
			 	 	case  'pattern' :
			 	 			$valid=(preg_match($checkpattern,$f_value)) ? TRUE : FALSE;
			 	 			if (!$valid) {
			 	 			 	$this->Err=TRUE;
			 	 			 	
			 	 			 	$babelRepl=array('FormColumn'=>'<em>'.$f_title.'</em>');
			 	 			 	$out='<li>';
								$out.=Babel::_('FormCheck::InvalidPattern at %%FormColumn%%',$babelRepl);
								$out.='</li>'; 
								
								$this->CheckInfo[$f_name]=FALSE; 
			 	 			}
			 	 			break;
			 	 
			 	}
			 
			 
			}
			
			
			
			return $out;	
	 		
	}

 

	function ffInput($conf=array()) {
			
			$out			= '';
	
			$f_name			= ArrayUtil::getValue($conf,'name','');
	 	 	$f_title		= ArrayUtil::getValue($conf,'label','');
	 	 	
	 	 	$f_alttitle		= ArrayUtil::getValue($conf,'label_title',$f_title);
	 	 	
			$f_tooltip		= '';
	 	 	$f_tooltiptext	= ArrayUtil::getValue($conf,'tooltip','');
	 	 	if (!empty($f_tooltiptext)) {
	 	 	 	$f_tooltip	= '<div class="input-tooltip"><a class="info">[?]</a><div class="bubble"><h5>Eingabefeld: '.$f_title.'</h5><p>'.$f_tooltiptext.'</p></div></div>';
	 	 	}
	 	 	
	 	 	$f_type			= ArrayUtil::getValue($conf,'inputtype','text');
	 	 	
			$f_options		= ArrayUtil::getValue($conf,'options',FALSE);
	 	 	$f_optDef		= ArrayUtil::getValue($conf,'optionsDefined','');
	 	 	
			if (!empty($f_optDef)) {
	 	 		$optConfig	= ArrayUtil::getValue($this->config,'Options',FALSE);
				if ($optConfig) {
					$f_options 			= ArrayUtil::getValue($optConfig,$f_optDef,array());
					$conf['options'] 	= $f_options;
				}
			}
			
			
			$f_optDB		= ArrayUtil::getValue($conf,'optionsDB',FALSE);;
			if ($f_optDB) {
			 	$conf['options']	= $this->getDBOptions($f_optDB);
			}
			
			$f_default=ArrayUtil::getValue($conf,'default','');
	 	 	
			$conf['value']	= $this->getRequestParam($f_name,$f_default);
	 	 	
	 	 	$mandatory		= ArrayUtil::getValue($conf,'mandatory',FALSE);
	 	 	$mand			= (CastUtil::toBoolean($mandatory)===TRUE) ? TRUE : FALSE;
	 		
			$f_cssclass		= ($mand) ? 'ffset mandatory' : 'ffset';
	 	 	
	 	 	$disabled		= ArrayUtil::getValue($conf,'disabled',FALSE);
	 	 	if (CastUtil::toBoolean($disabled)===TRUE) {
	 	 	 	HTMLFragment::setAttr('disabled','disabled',$conf);
	 	 	 	$f_cssclass.=' disabled'; 
	 	 	}
	 	 	$readonly=ArrayUtil::getValue($conf,'readonly',FALSE);
	 		if (CastUtil::toBoolean($readonly)===TRUE) {
	 	 	 	HTMLFragment::setAttr('readonly','readonly',$conf);
	 	 	 	$f_cssclass.=' readonly'; 
	 	 	}
	
			$printable		= ArrayUtil::getValue($conf,'printable',TRUE);
			if (CastUtil::toBoolean($printable)===FALSE) {
			 	return '';
			} 	
	 		
	 		$valid			= ArrayUtil::getValue($this->CheckInfo,$f_name,TRUE);
	 		if (!$valid) {
	 			$f_cssclass.=' invalid'; 
	 		}
	 		
	 		$f_descr		= trim(ArrayUtil::getValue($conf,'description'));
	 				
	 		$f_marker		= ($mand) ? '<span class="marker-mandatory">*</span>' : '';
	 
	 		$f_id			= $f_name;
	 		
	 		// add custom css class
	 		$css_ext 		= ArrayUtil::getValue($conf,'outerCSSClass','');
	 		$f_cssclass.=' '.$css_ext;
	 
	 
	 
	 		// rendering output
	 		$out.="\n";
	 		$out.='<div id="ffset_'.$f_id.'" class="'.$f_cssclass.'">';
	 		
	 		$label='';
	 		$label.="\n";
	 		$label.='<label for="'.$f_id.'" title="'.$f_alttitle.'">'.$f_title.$f_marker.'</label>';
	 		$label.="\n";
	 		
	 		$descr	= '';
	 		if (!empty($f_descr)) {
	 		 	$descr.='<div class="descr">'.$f_descr.'</div>';
	 		}
	 		
	 		$input	= '';
	 		 		
	 		switch ($f_type) { 
	 		 		
				  case 'submit' : 
	 		 					$label='';
				  				$conf['value']=$f_title; 
								//$input=$this->ffInputSubmit($conf); break;
								$fField=HTMLFormField::factory('FF_'.$f_type) ; 
								$input.=$fField->renderInput($conf); break;
				
	 		 	
	 		 	default 		: 		
				  				$fField=HTMLFormField::factory('FF_'.$f_type) ; 
								$input.=$fField->renderInput($conf); break;
	 		 
	 		}
	 		
	 		
	 		$addSubmitter	=ArrayUtil::getValue($conf,'addSubmitter',FALSE);
	 		if ($addSubmitter) {
	 		 	$input.=$this->getInlineSubmitter($f_type);
	 		}
	 		
	 		
	 		$out.="\n";
	 		$out.='<div class="label-wrap">';
	 		$out.=$label;	 
			$out.=$f_tooltip;
			$out.='</div>'; 		
			$out.="\n";
			
			$out.="\n";
	 		$out.='<div class="input-wrap">';
	 		$out.=$descr;
	 		$out.=$input;	 				
			$out.='</div>';
			$out.="\n";
			
			
			
	 		$out.='<span class="eos"></span>';
			$out.='</div>';
	 		$out.="\n";
	 		
	 		
	 		
	 		return $out;
	 
	}
	
	
	function ffOutput($conf=array()) {
	 
	 		$out='';
	 		
	 		$f_name			= ArrayUtil::getValue($conf,'name','');
	 	 	$f_type			= ArrayUtil::getValue($conf,'inputtype','text');
	 	 
			$f_options		= ArrayUtil::getValue($conf,'options',FALSE);
	 	 	$f_optDef		= ArrayUtil::getValue($conf,'optionsDefined','');
	 	 	
			if (!empty($f_optDef)) {
	 	 		$optConfig=ArrayUtil::getValue($this->config,'Options',FALSE);
				if ($optConfig) {
					$f_options=ArrayUtil::getValue($optConfig,$f_optDef,array());
					$conf['options']=$f_options;
				}
			}
			
			
			$f_optDB		= ArrayUtil::getValue($conf,'optionsDB',FALSE);
			if ($f_optDB) {
			 	$conf['options']=$this->getDBOptions($f_optDB);
			}
			
			$f_title		= ArrayUtil::getValue($conf,'label');
			
			$value			= ArrayUtil::getValue($conf,'value');
	 	 	$conf['value']	= $this->getRequestParam($f_name,$value);
	 	
	 	
	 		// rendering output
	 		$out.='<div class="ffset-print">';
		 	$out.='<div class="label-wrap-print">'; 	
	 	 	$out.='<span class="label-print">'.$f_title.'</span>';
	 	 	$out.='</div>';
	 	 	$out.='<div class="input-wrap-print">';
	 	 	
	 	 	switch ($f_type) { 
			   				  			
	 		 	case 'text' : $out.=ArrayUtil::getValue($conf,'value',''); break;
	 		 	case 'submit' : break;
	 		 	
	 		 	default : $fField=HTMLFormField::factory('FF_'.$f_type) ; $out.=$fField->renderOutput($conf); break;
	 		 
	 		}
	 	 	
	 	
	 		$out.='</div>';
	 		$out.='</div>';
		  	
	 	 	return $out;
	 	 	
	} 
	
	
	function ffOutputValue($conf=array()) {
	 
	 		$out='';

			$f_name=$conf['name'];
	 	 	$f_type=ArrayUtil::getValue($conf,'inputtype','text');
	 	 	$f_options=ArrayUtil::getValue($conf,'options');
	 	 	$f_optDef=ArrayUtil::getValue($conf,'optionsDefined');
	 	 	
			if (!empty($f_optDef)) {
	 	 		$optConfig=ArrayUtil::getValue($this->config,'Options',FALSE);
				if ($optConfig) {
					$f_options=ArrayUtil::getValue($optConfig,$optDef,array());
					$conf['options']=$f_options;
				}
			}
			
	 	 	$conf['value']=$this->getRequestParam($f_name,'');
	 	 	
	 	 	switch ($f_type) { 
	 			  			
				case 'text' : $out.=$conf['value']; break;
	 		 	//case 'textarea' : $out.=$conf['value']; break;
	 		 	case 'submit' : break;
	 		 	
	 		 	default : $fField=HTMLFormField::factory('FF_'.$f_type) ; $out.=$fField->renderOutputValue($conf); break;
	 		 	//default : $out.=$f_value; break;
	 		 
	 		}
	 	
	 	//var_dump($out);
	 	 	return $out;
	 	 	
	} 
	
	
	function ffStorageValue($conf=array()) {
	 	$f_name=$conf['name'];
	 	$f_type=ArrayUtil::getValue($conf,'inputtype','text');
	 	$f_options=$conf['options'];
	 	$f_optDef=$conf['optionsDefined'];
	 	 	
		if (!empty($f_optDef)) {
 	 		$optConfig=ArrayUtil::getValue($this->config,'Options',FALSE);
			if ($optConfig) {
				$f_options=ArrayUtil::getValue($optConfig,$optDef,array());
				$conf['options']=$f_options;
			}
		}
			
	 	$conf['value']=$this->getRequestParam($f_name,'');
	 	$fField=HTMLFormField::factory('FF_'.$f_type) ;
		$out=$fField->renderStorageValue($conf);
		return $out;
	}
	
	
	function ffInputText($conf=array()) {
	
		$name=$conf['name'];
		$value=$conf['value'];
		$out='';
		$out.='<input class="input" type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'"/>';
		return $out;
	 
	}
	
	
	
	function ffInputSubmit($conf=array()) {
	
		$name=$conf['name'];
		$value=$conf['value'];
		$out='';
		$out.='<input class="button" type="submit" name="'.$name.'" id="'.$name.'" value="'.$value.'"/>';
		return $out;
	 
	}
	
	
	
	function mailFormValues($args=array()) {
	 	$body=$this->getMailFormBody();
	 
	 	$mailsubject=ArrayUtil::getValue($args,'subject','');
	 	if (empty($mailsubject)) {
	 	 	return FALSE;
	 	}
	 	$mailto=ArrayUtil::getValue($args,'mailto','');
	 	if (empty($mailto)) {
	 	 	return FALSE;
	 	}
		$mailfrom=ArrayUtil::getValue($args,'mailfrom','');
	 	$mailtemplate=ArrayUtil::getValue($args,'template','');
	 	
	 	$search=$this->getKeys();
	 	$replace=$this->getValues();
	 	$search[]='MailBody';
	 	$replace[]=$body;
	 	$mailbody=TextParser::replace($mailtemplate,$search,$replace,'%%');
	 	
	 	//$mailbody=str_replace('%%MailBody%%',$body,$mailtemplate);
	 	
	 	$customMailUtil = ArrayUtil::getValue($args,'MailUtil',null);
	 	if (is_object($customMailUtil)) {
	 	 	$Mailer = $customMailUtil;
	 	}
		else {
	 		$Mailer=new MailUtil();
	 	}
	 	return $Mailer->sendMail($mailto,$mailsubject,$mailbody,$mailfrom); 
	 	
	 	
	 
	}
	
	
	function storeFormValues($args) {
	 	$data=$this->collect();
	 	$mode=ArrayUtil::getValue($args,'mode','flat');
	 
		$prefix=Application::getAppPrefix();
	 	$uniqueFilename=md5(uniqid($prefix,TRUE));
	 	$filename=ArrayUtil::getValue($args,'filename',$uniqueFilename);
	 	
	 	$extension=ArrayUtil::getValue($args,'extension','dat.php');
	 	
	 	$defaultpathname='protected.store.userdata';
	 	$pathname=ArrayUtil::getValue($args,'path',$defaultpathname);
	 	$path=Loader::getPath($pathname);
	 	
		$out=FALSE;
		$writeFile=TRUE;
		$append=FALSE;
	 	switch (strtolower($mode)) {
	 	 	case 'db' : $writeFile=FALSE; break;
	 	 	case 'csv' : $extension='csv'; $content=RenderUtil::toCSV($data); $append=TRUE; break;
	 	 	case 'xml' : $extension='xml'; $content=RenderUtil::toXML($data); break;
	 	 	case 'php' : $extension='php'; $content=RenderUtil::toPHP($data); break;
	 	 	default :  $content=$data; break; 
	 	}
	 	//var_dump($content);
	 	if ($writeFile) {
	 	 	$uri=$path.'/'.$filename.'.'.$extension;
			if ($append) {  	
	 			$out=FileUtil::appendFile($uri,$content,TRUE); 
			} 
			else {
			 	$out=FileUtil::writeFile($uri,$content,TRUE);
			}
	 	 
	 	}
	 	
	 	return $out;
	}
	
	
	
	function loadFormValues($args=array()) {
	 	
		$loaddata=ArrayUtil::getValue($args,'data',FALSE);
		if (!$loaddata) {
		 	return FALSE;
		} 
		$keys=$this->getKeys();
		foreach ($keys as $key) {
		 	if ($value=ArrayUtil::getValue($loaddata,$key,FALSE)) {
		 	 	RequestUtil::setParam($key,$value);
		 	}
		} 	
	 
	}
	
	
	function clearFormValues() {
	 	$keys=$this->getKeys();
		foreach ($keys as $key) {
		 	RequestUtil::unsetParam($key);
		 	
		} 	
	 
	}
	
	
			
	function prepareToStore() {
	 	if (!$this->hasFormConfig()) {
	 	 	return FALSE;
	 	}
	 	$ffConfig=$this->getFormGroups();
 		for ($f=0;$f<count($ffConfig);$f++) { 		 
 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
		 	$fg_ff=$fginfo['Fields'];

	 		for ($ff=0;$ff<count($fg_ff);$ff++) {
	 			$ffi=$fg_ff[$ff];
				$fname=$ffi['name'];
				if (RequestUtil::hasParam($fname)) {
					RequestUtil::setParam($fname,$this->ffStorageValue($ffi)); 
				}
			}
 		} 
	}
	
	
	
	function filterFormGroups($args=array()) {
	 
	 	$groupsOnly=ArrayUtil::getValue($args,'groupsOnly',FALSE);
	 	if (!$groupsOnly) {
	 	 	return TRUE;
	 	}
	 	$key=ArrayUtil::getValue($this->Declaration,'FormGroupsConfig');
	 	$printable_key=ArrayUtil::getValue($this->Declaration,'FormGroupPrintable');
	 	$ffConfig=$this->getFormGroups();
	 	$wildcard=(in_array('*',$groupsOnly)) ? TRUE : FALSE;
	 	
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {

 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
	 		$fg_id=$fginfo['ID'];
	 		if (!in_array($fg_id,$groupsOnly) && !$wildcard) {
	 	 	 	$ffConfig[$f]=array($printable_key=>0);
	 	 	}
	 		
		}
			
	 	$this->config[$key]=$ffConfig;
	 
	}
	
	
	function hideFormGroups($args=array()) {
	 
	 	$groupsOnly=ArrayUtil::getValue($args,'groupsOnly',FALSE);
	 	if (!$groupsOnly) {
	 	 	return TRUE;
	 	}
	 	$key=ArrayUtil::getValue($this->Declaration,'FormGroupsConfig');
	 	$printable_key=ArrayUtil::getValue($this->Declaration,'FormGroupPrintable');
	 	$ffConfig=$this->getFormGroups();
	 	$wildcard=(in_array('*',$groupsOnly)) ? TRUE : FALSE;
	 	
	 	
 		for ($f=0;$f<count($ffConfig);$f++) {

 		 	$fgrp=$ffConfig[$f];
 		 	$fginfo=$this->getFormGroupData($fgrp,$f); 
	 		$fg_id=$fginfo['ID'];
	 		if (!in_array($fg_id,$groupsOnly) && !$wildcard) {
	 	 	 	$ffConfig[$f]['fg_hidden']=TRUE;
	 	 	}
	 		
		}
			
	 	$this->config[$key]=$ffConfig;
	 
	}
	
	
	
	

	
	
	// to be hooked
	function setDBDriver($args=array()) {
	 
	 	$dbdriver=ArrayUtil::getValue($args,'DBDriver',FALSE);

	 	if ($dbdriver) {
	 		$this->DBDriver=$dbdriver;
	 	}
	 	
	 	OverrideUtil::registerCallback('HTMLForm::GetDBDriver',array($this,'getDBDriver'));
	 
	}
	
	
	function getDBDriver() {
	 	return $this->DBDriver;
	}
	
	function getDBOptions($conf) {
	 
	 
	 	$callback_get					= ArrayUtil::getValue($conf,'callback_get',FALSE);
	 	if ($callback_get) {
	 	 	$conf['DBDriver']			= $this->DBDriver;
	 	 	$data						= call_user_func($callback_get,$conf);
	 	 	return $data;
	 	}
	 	
		$withNull						= ArrayUtil::getValue($conf,'NULL',TRUE);
	 
	 	if ($withNull) {
	 		$options=array(''=>'');
	 	}
	 	$dbtable						= ArrayUtil::getValue($conf,'dbtable',FALSE);
	 	$valCol							= ArrayUtil::getValue($conf,'value_column',FALSE);
	 	$dispCol						= ArrayUtil::getValue($conf,'display_column',FALSE);
	 	$infoCol						= ArrayUtil::getValue($conf,'info_columns',FALSE);
	 	$infoColSep						= ArrayUtil::getValue($conf,'info_column_separator',',');
	 	$conf['sortBy']					= ArrayUtil::getValue($conf,'sortBy',$valCol);
	 	$conf['sortOrder']				= ArrayUtil::getValue($conf,'sortOrder','ASC');
	 	//$conf['columns']=array('*');
	 	$columns=array();
	 	if ($valCol) 	{ $columns[] 	= $valCol; }
	 	if ($dispCol) 	{ $columns[] 	= $dispCol; }
	 	if ($infoCol)  	{ $columns		= array_merge($columns,$infoCol); }
	 	$conf['columns']				= $columns;
	 	
	 	$conf['limit']					= ArrayUtil::getValue($conf,'limit',100);
	 	$filter							= ArrayUtil::getValue($conf,'filter',FALSE);
	 	$conf['filter']					= $this->replaceVars($filter);
	 	
	 	$data							= $this->DBDriver->select($conf);
	 	
	 	$callback						= ArrayUtil::getValue($conf,'callback_after',FALSE);
	 	if ($callback) {
	 	 	$data						= call_user_func($callback,$data);
	 	}
	 	
	 	for ($d=0;$d<count($data);$d++) {
	 	 	$item=$data[$d];
	 	 	$indent=ArrayUtil::getValue($item,'_indent','');
	 	 	$indent.=ArrayUtil::getValue($item,'_sort','');
	 	 	
	 	 	$info='';
	 	 	if ($infoCol) {
	 	 	 	foreach ($infoCol as $iCol) {
	 	 	 	 	if (isset($item[$iCol])) {
	 	 	 	 		$info.=(!empty($info)) ? $infoColSep : '';
	 	 	 	 		$info.=$item[$iCol];
	 	 	 	 	}
	 	 	 	}
	 	 	 	if (!empty($info)) {
	 	 	 	 	$info=' ( '.$info.' )';
	 	 	 	}
	 	 	}
	 	 	
	 	 	$options[($item[$valCol])]=$indent.$item[$dispCol].$info;
	 	}
	 	
	 	
	 	
	 	return $options;
	 	
	 
	} 
	
	function replaceVars($expr) {
	 	$out=$expr;
	 	
	 	if (strstr($expr,'%%Param::')) {
	 	 	$data=RequestUtil::collect();
	 	 	if (is_array($data)) {
		 	 	foreach ($data as $key=>$val) {
		 	 	 	$out=str_replace('%%Param::'.$key.'%%',$val,$out);
		 	 	}
	 	 	}
	 	}
	 	
	 	if (strstr($expr,'%%')) {
	 	 	$data=$this->collect();
	 	 	if (is_array($data)) {
		 	 	foreach ($data as $key=>$val) {
		 	 	 	$val=(empty($val)) ? RequestUtil::getParam($key,'') : $val;
		 	 	 	$out=str_replace('%%'.$key.'%%',$val,$out);
		 	 	}
	 	 	}
	 	}

	 	return $out;
	 
	}

	
	
	function getInlineSubmitter($prcType='') {
	 
	 	$cssCls='btnSubmit';
	 	/*
	 	if (!empty($prcType)) {
	 	 	$cssCls.=' succ-'.$prcType;
	 	}
	 	*/
	 	$out='<input type="submit" class="'.$cssCls.'" value="" title="'.(Babel::_('HTMLForm::BtnInlineSubmit')).'"/>';
	 	return $out;
	}
	
}

?>
