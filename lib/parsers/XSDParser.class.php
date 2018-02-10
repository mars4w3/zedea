<?php

class XSDParser {
 
 	var $XSD 			= null;
 	var $XMLParser 		= null;
 	var $xsdNamespace	= 'xsd:';
 	
 	var $xsdAssoc 		= array();
 	var $elements		= array(
	 							'defined'	=> array(),
	 							'reference' => array(),
	 						);
 	
 	function __construct($pathToXSD,$namespace='xsd:') {
	 	$this->XSD			= FileUtil::getContent($pathToXSD);
	 	$this->xsdNamespace	= $namespace;
	 	$this->init();
		 
		
	} 
 
 	function init() {
 	 	Loader::loadClass('XMLParser','lib.parsers');
		$this->XMLParser 	= new XMLParser();  
		$XP					= $this->XMLParser;
		$xml				= $XP->parseString($this->XSD);
		$this->xsdAssoc		= $XP->getAssocTree();  
		

 	}



	function pushItem(&$array=array(),$item=null,$key='',$assoc=TRUE) {
	 	if ($assoc) {
	 	 	$index=ArrayUtil::getValue($item,$key,'undefined');
	 	 	unset($item[$key]);
	 	 	$array[$index]=$item;
	 	}
	 	else {
	 	 	$array[]=$item;
	 	}
	}


	function getElementPath($elemName) {
	 	if (empty($this->elements['defined'])) {
	 	 	$this->findElements();
	 	}
	 	$defined=ArrayUtil::getValue($this->elements,'defined',array());
	 	
	 	
	 	
	 	$elem=ArrayUtil::getValue($defined,$elemName,FALSE);
	 	if (!$elem) {
	 	 	return FALSE;
	 	}
	 	$xpath=ArrayUtil::getValue($elem,'xpath',FALSE);
		return $xpath; 
	 	
	 	
	}

	function registerElement($elem) {
	 	$ref = ArrayUtil::getValue($elem,'ref',FALSE);
	 	if ($ref) {
	 		$this->pushItem($this->elements['reference'],$elem,'ref',TRUE);
	 	}
	 	else {
	 	 	$this->pushItem($this->elements['defined'],$elem,'name',TRUE);
	 	}
	 
	}
	
	function findElements($node=null,$xpath='') {
	 	$XP			= $this->XMLParser;
	 	$xsdNs		= $this->xsdNamespace;
	 	
	 	if (!$node) {
	 	 	$xpath  = $xsdNs.'schema';
	 	 	$node	= $XP->getXMLNode($this->xsdAssoc,$xpath,'node');
	 	}
	 	
	 	$elements 	= $XP->getXMLNode($node,$xsdNs.'element[]','node');
	 	$complex	= $XP->getXMLNode($node,$xsdNs.'complexType','node');
	 	$simple		= $XP->getXMLNode($node,$xsdNs.'simpleType','node');
	 	$sequence	= $XP->getXMLNode($node,$xsdNs.'sequence','node');
	 	$choice		= $XP->getXMLNode($node,$xsdNs.'choice','node');
	 	if ($elements) {
	 	 	$num=0;
	 	 	foreach ($elements as $element) {
	 	 	 	$name		= $XP->getXMLNodeAttr($element,'name');
 	 	 		$ref		= $XP->getXMLNodeAttr($element,'ref');
 	 	 		$xpathChild	= $xsdNs.'element['.$num.']';
 	 	 		if ($name || $ref) {
 	 	 		 	$item=array(
						'name'=>$name,
						//'xpath' => $xpath,
						'xpath'=>$xpath.'.'.$xpathChild,
					);
					if ($ref) {
					 	$item['ref']=$ref;
					 	unset($item['name']);
					}
					$this->registerElement($item);
 	 	 		}
 	 	 		$this->findElements($element,$xpath.'.'.$xpathChild);
 	 	 		$num++;
	 	 	}
	 	}
	 	if ($complex) {
	 	 	$this->findElements($complex,$xpath.'.'.$xsdNs.'complexType');
	 	}
	 	if ($choice) {
	 	 	$this->findElements($choice,$xpath.'.'.$xsdNs.'choice');
	 	}
	 	if ($sequence) {
	 	 	$this->findElements($sequence,$xpath.'.'.$xsdNs.'sequence');
	 	}
	}
	
	
	
	
	
	
	
	function showElementsTree($elements=array()) {
	 	$out='<ul style="margin-left:15px;">';
	 	foreach($elements as $elementName=>$element) {
	 	 	$out.='<li>';
	 	 	$out.=$elementName;
	 	 	if (is_array($element['childs'])) {
	 	 	 	$out.=$this->showElementsTree($element['childs']);
	 	 	}
	 	 	$out.='</li>';
	 	}
	 	$out.='</ul>';
	 	return $out;
	 
	}

 
 	function getElements($assoc=TRUE) {
 	 	$tree			= $this->xsdAssoc;
 	 	$XP				= $this->XMLParser;
 	 	$xsdNs			= $this->xsdNamespace;
 	 	$elements		= $XP->getXMLNode($tree,$xsdNs.'schema.'.$xsdNs.'element[]','node');
 	 	
		$out			= array();
		
 	 	foreach ($elements as $element) {
 	 	 	$this->getElement($element,$out);
 	 	}
		return $out;   	
 	 	
 	}

	function getChildElements($node,$childs=array()) {
	 	$XP			= $this->XMLParser;
	 	$xsdNs		= $this->xsdNamespace;
	 	
	 	$elements 	= $XP->getXMLNode($node,$xsdNs.'element[]','node');
	 	$complex	= $XP->getXMLNode($node,$xsdNs.'complexType','node');
	 	$simple		= $XP->getXMLNode($node,$xsdNs.'simpleType','node');
	 	$sequence	= $XP->getXMLNode($node,$xsdNs.'sequence','node');
	 	$choice		= $XP->getXMLNode($node,$xsdNs.'choice','node');
	 	if ($elements) {
	 	 	foreach ($elements as $element) {
	 	 	 	$this->getElement($element,$childs);
	 	 	}
	 	}
	 	if ($complex) {
	 	 	$childs=$this->getChildElements($complex,$childs);
	 	}
	 	if ($choice) {
	 	 	$childs=$this->getChildElements($choice,$childs);
	 	}
	 	if ($sequence) {
	 	 	$childs=$this->getChildElements($sequence,$childs);
	 	}
	 	return $childs;
	 
	}


 	
	function getElement($element,&$out=array(),$return=FALSE) {
 	 		$XP				= $this->XMLParser;
 	 		$xsdNs			= $this->xsdNamespace;
	 
	 
	 	 	$name		= $XP->getXMLNodeAttr($element,'name');
	 	 	$ref		= $XP->getXMLNodeAttr($element,'ref');
	 	 	
 	 	 	$type		= $XP->getXMLNodeAttr($element,'type');
 	 	 	$descr		= $XP->getXMLNode($element,$xsdNs.'annotation.'.$xsdNs.'documentation');
 	 	 	$complex	= $XP->getXMLNode($element,$xsdNs.'complexType','node');
 	 	 	$simple		= $XP->getXMLNode($element,$xsdNs.'simpleType','node');
 	 	 	$attr		= array();
 	 	 	$childs		= array();
 	 	 	$seq		= FALSE;
 	 	 	$chc		= FALSE;
 	 	 	$attributes	= FALSE;
 	 	 	
 	 	 	
 	 	 	
 	 	 	if ($complex) {
 	 	 	 	$attributes	= $XP->getXMLNode($complex,$xsdNs.'attribute[]');
 	 	 	 	$childs 	= $this->getChildElements($element,$childs);
 	 	 	}	
 	 	 	
 	 	 	if ($attributes) { 
			    $attr=$this->getAttributes($attributes); 
			}
 	 	
 	 		if ($ref) {
 	 			$item=array(
					'name' => $ref,
					'isref' => TRUE,
				); 
 	 		}
 	 		else {
 	 	 	$item = array(
 	 	 		'name'	=> $name,
 	 	 		'type'	=> $type,
 	 	 		'descr'	=> $descr,
 	 	 		'attr'	=> $attr,
 	 	 		'childs'	=> $childs,
 	 	 	);
 	 	 	}
 	 		if ($return) {
 	 		 	return $item;
 	 		}
 	 	 	$this->pushItem($out,$item,'name',TRUE);
 	 	 	
 	}




	function getAttributes($attributes=array(),$assoc=TRUE) {
 	 	$XP		= $this->XMLParser;
 	 	$xsdNs	= $this->xsdNamespace;
 	 	$out	= array();
 	 	foreach ($attributes as $attr) {
 	 	 	$name		= $XP->getXMLNodeAttr($attr,'name');
 	 	 	$type		= $XP->getXMLNodeAttr($attr,'type');
 	 	 	$simple		= $XP->getXMLNode($attr,$xsdNs.'simpleType','node');
 	 	 	if ($simple) { $type=$this->getSimpleType($simple,TRUE); }
 	 		
			$item = array(
				'name' => $name,
				'type'	=> $type
			);
			$this->pushItem($out,$item,'name',$assoc);
			
 	 	}
 	 	return $out;
 	}

 	

	function getSimpleType($type,$assoc=TRUE) {
 	 	$XP		= $this->XMLParser;
 	 	$xsdNs	= $this->xsdNamespace;
 	 	$out	= array();
 	 	$enum	= $XP->getXMLNode($type,$xsdNs.'restriction.'.$xsdNs.'enumeration[]');
 	 	if ($enum) {
 	 	 	$enum=$this->getEnumeration($enum);
 	 	 	$out=array( 'type'=>'enumeration','enumeration'=> $enum);
 	 	}
 	 	return $out;
 	 
 	}

	function getEnumeration($enum=array()) {
 	 	$XP		= $this->XMLParser;
 	 	$options	= array();
 	 	foreach ($enum as $option) {
 	 	 	$value	= $XP->getXMLNodeAttr($option,'value');
 	 	 	$this->pushItem($options,$value,'',FALSE);
 	 	}
 	 	return $options;
 	 
 	}




 
 
 	function listElements() {
 	 	$tree		= $this->xsdAssoc;
 	 	$XP			= $this->XMLParser;
 	 	$xsdNs		= $this->xsdNamespace;
 	 	
 	 	$elements=$XP->getXMLNode($tree,$xsdNs.'schema.'.$xsdNs.'element[]','node');
 	 	//var_dump($elements);
 	 	$out='<h1>'.$xsdNs.'Elements</h1>';
 	 	$out.='<table style="width:100%;">';
 	 	$out.='<colgroup><col width="10%"/><col width="20%"/><col width="40%"/><col width="10%"/><col width="20%"/></colgroup>';
 	 	$out.='<tr class="caption"><th>Element</th><th>Beschreibung</th><th>Attribute</th><th>Datentyp</th><th>Unterelemente</th></tr>';
 	 	$row=0;
 	 	foreach ($elements as $element) {
 	 	 	$name		= $XP->getXMLNodeAttr($element,'name');
 	 	 	$type		= $XP->getXMLNodeAttr($element,'type');
 	 	 	$descr		= $XP->getXMLNode($element,$xsdNs.'annotation.'.$xsdNs.'documentation');
 	 	 	
 	 	 	$complex	= $XP->getXMLNode($element,$xsdNs.'complexType','node');
 	 	 	$simple		= $XP->getXMLNode($element,$xsdNs.'simpleType','node');
 	 	 	$attr		= '';
 	 	 	$childs		= '';
 	 	 	
 	 	 	$seq		= FALSE;
 	 	 	$chc		= FALSE;
 	 	 	$attributes	= FALSE;
 	 	 	
 	 	 	if ($complex) {
 	 	 		$seq		= $XP->getXMLNode($complex,$xsdNs.'sequence.'.$xsdNs.'element[]');
 	 	 		$chc		= $XP->getXMLNode($complex,$xsdNs.'choice.'.$xsdNs.'element[]');
 	 	 		$attributes	= $XP->getXMLNode($complex,$xsdNs.'attribute[]');
 	 	 	}
 	 	 	
 	 	 	if ($attributes) { 
			    $attr=$this->listAttributes($attributes); 
			}
 	 	 	
 	 	 	if ($seq || $chc) { 
			    $childs=$this->listComplexType($complex); 
			} 
 	 	 	
 	 	
 	 	 	
			$trcls=($row%2==0) ? 'even' : 'odd';
 	 	 	$out.='<tr class="'.$trcls.'"><th><a name="xsdelem-'.$name.'">'.$name.'</a></th><td>'.$descr.'</td><td>'.$attr.'</td><td>'.$type.'</td><td>'.$childs.'</td></tr>'."\n";
 	 	 	$row++;
 	 	}
 	 	$out.='</table>';
 	 	return $out;
 	}
 	
 	function listAttributes($attributes=array()) {
 	 	$XP		= $this->XMLParser;
 	 	$xsdNs	= $this->xsdNamespace;
 	 	$row	= 0;
 	 	$out	= '<table style="width:100%"><colgroup><col width="50%"/><col width="50%"/></colgroup>';
 	 	foreach ($attributes as $attr) {
 	 	 	$name		= $XP->getXMLNodeAttr($attr,'name');
 	 	 	$type		= $XP->getXMLNodeAttr($attr,'type');
 	 	 	$extType	= $XP->getXMLNode($attr,$xsdNs.'simpleType','node');
 	 	 	if ($extType) { $type=$this->listSimpleType($extType); }
 	 	 	
			$trcls		= ($row%2==0) ? 'even' : 'odd';
 	 	 	$out.='<tr><td>'.$name.'</td><td>'.$type.'</td></tr>'."\n";
 	 	 	$row++;
 	 	}
 	 	$out.='</table>';
 	 	return $out;
 	}
 	
 	function listComplexType($type) {
 	 	$XP		= $this->XMLParser;
 	 	$xsdNs	= $this->xsdNamespace;
 	 	$out	= '';
 	 	$seq	= $XP->getXMLNode($type,$xsdNs.'sequence.'.$xsdNs.'element[]');
 	 	$chc	= $XP->getXMLNode($type,$xsdNs.'choice.'.$xsdNs.'element[]');
 	 	if ($seq) {
 	 	 	$out.=$this->listSequence($seq);
 	 	}
 	 	if ($chc) {
 	 	 	$out.=$this->listChoice($chc);
 	 	}
 	 	return $out;
 	 
 	}
 	
 	
 	function listSequence($elements=array()) {
 	 	$XP		= $this->XMLParser;
 	 	$out	= 'SEQUENCE<ul>';
 	 	foreach ($elements as $element) {
 	 	 	$name=$XP->getXMLNodeAttr($element,'ref');
 	 	 	$out.='<li><a href="#xsdelem-'.$name.'">'.$name.'</a></li>'."\n";
 	 	}
 	 	$out.='</ul>';
 	 	return $out;
 	}
 	
 	function listChoice($elements=array()) {
 	 	$XP		= $this->XMLParser;
 	 	$out	= 'CHOICE<ul>';
 	 	foreach ($elements as $element) {
 	 	 	$name=$XP->getXMLNodeAttr($element,'ref');
 	 	 	$out.='<li>[<a href="#xsdelem-'.$name.'">'.$name.'</a>]</li>'."\n";
 	 	}
 	 	$out.='</ul>';
 	 	return $out;
 	}
 
 	function listEnumeration($enum=array()) {
 	 	$XP		= $this->XMLParser;
 	 	$out	= '<ul>';
 	 	foreach ($enum as $option) {
 	 	 	$value	= $XP->getXMLNodeAttr($option,'value');
 	 	 	$out.='<li>'.$value.'</li>'."\n";
 	 	}
 	 	$out.='</ul>';
 	 	return $out;
 	 
 	}
 	
 	function listSimpleType($type) {
 	 	$XP		= $this->XMLParser;
 	 	$xsdNs	= $this->xsdNamespace;
 	 	$out	= '';
 	 	$enum	= $XP->getXMLNode($type,$xsdNs.'restriction.'.$xsdNs.'enumeration[]');
 	 	if ($enum) {
 	 	 	$out.=$this->listEnumeration($enum);
 	 	}
 	 	return $out;
 	 
 	}
 	
 	


	
 	
	function getSchemaTranslated($elements=null,$nested=TRUE) {
	
		if (!$elements) {
	 		$elements 	= $this->getElements(TRUE);
	 	}
	 	$schema		= array();
	 	
	 	$index	=array('isChildOf'=>array(),'isParentOf'=>array());
	 	foreach ($elements as $name=>$element) {
	 	 	$item=$this->makeSubsetItem($name,$element);
	 	 	$this->pushItem($schema,$item,'name',TRUE);
			
			if (($element['childs'])) {
				// if elements defined 	
					 		
			 	foreach($element['childs'] as $childName=>$child) {
			 	 	if (!isset($index['isChildOf'][$childName])) {
			 	 	 	$index['isChildOf'][$childName]=array();
			 	 	}
			 	 	$index['isChildOf'][$childName][$name]=$name;
			 	 	if (!isset($index['isParentOf'][$name])) {
			 	 	 	$index['isParentOf'][$name]=array();
			 	 	}
			 	 	$index['isParentOf'][$name][$childName]=$childName; 
			 	}
			}
			
	 	}
		if ($nested) {
		 	$schema=$this->replaceReferences($schema,$index);
		}
		return $schema;
	}


	function makeSubset($childs=array()) {
	 		$subset=array();
	 		if (!is_array($childs)) {
	 		 	return FALSE;
	 		}
	 		foreach ($childs as $childName=>$childElem) {
	 		 	$subset[$childName]=$this->makeSubsetItem($childName,$childElem);
	 		}
	 		return $subset;
	}
	
	function makeSubsetItem($name,$element) {
	 		
			$item = array('name'=>$name);
			if (!empty($element['attr'])) {
			 	$attr=$element['attr'];
			 	foreach($attr as $aname=>$adata) {
			 	 	$atype=ArrayUtil::getValue($adata,'type');
			 	 	$enum =ArrayUtil::getValue($atype,'enumeration',FALSE);
			 	 	if ($enum) {
			 	 	 	$attr[$aname]['type']='enumeration';
			 	 	 	$attr[$aname]['options']=$enum;
			 	 	}
			 	 	else {
			 	 	 	$attr[$aname]['type']=$atype;
			 	 	}
			 	}
			 	//$attr=$atype;
			 	$item['attr']=$attr;
			}
			if (!empty($element['type'])) {
			 	$item['type']=$element['type'];
			}
			if (isset($element['isref'])) {
			 	$item['isref']=TRUE;
			}
			if (!empty($element['descr'])) {
			 	$item['descr']=$element['descr'];
			}
			if (isset($element['childs'])) {
			 	$item['subset']=$this->makeSubset($element['childs']);
			}
			return $item;
	}


	function replaceReferences($schema,$index,$level=0) {
	 	
	 	$childIndex=$index['isChildOf'];
	 	
	 	foreach($childIndex as $childName=>$parents) {
	 	 	foreach($parents as $parent) {
	 	 		$childIsParent = (isset($index['isParentOf'][$childName])) ? TRUE : FALSE;
	 	 		if (!$childIsParent) {
	 	 		 	if (!isset($schema[$parent]['subset'][$childName])) {
	 	 	 			$schema[$parent]['subset'][$childName]=$schema[$childName];
	 	 	 		}
	 	 	 		elseif (isset($schema[$parent]['subset'][$childName]['isref'])) {
	 	 	 			$schema[$parent]['subset'][$childName]=$schema[$childName];
	 	 	 		}
	 	 	 		unset($index['isChildOf'][$childName][$parent]);
	 	 	 		unset($index['isParentOf'][$parent][$childName]);
	 	 	 		
	 	 	 		if (empty($index['isParentOf'][$parent])) { 
				    	unset($index['isParentOf'][$parent]) ;
	 	 	 		}
	 	 	 		if (empty($index['isChildOf'][$childName])) { 
	 	 	 			unset($schema[$childName]);
	 	 	 		}
	 	 	 	}
	 	 	}
	 	}
	 	
	 	$childIndex=$index['isChildOf'];
	 	
	
	 	if (!empty($childIndex) && $level<15) {
	 	 	$level++;
	 	 	$schema= $this->replaceReferences($schema,$index,$level);
	 	}
	 	
	 	return $schema;
	}
	
	
	function renderAsForm($schema=array(),$prefix='',$level=0,&$toc='',$markups=array()) {
	 
	 	$xsdNs	= $this->xsdNamespace;
	 	
	 	$textInput=HTMLFormField::factory('FF_text');
	 	$selectInput=HTMLFormField::factory('FF_select');
	 
	 	if (!is_array($schema)) {
		 	$out='<div><i>'.$schema.'</i></div>';
			return $out;  
		} 
	 
	 	$fcls=($level%2==0) ? 'even-level' : 'odd-level';
	 	$toc.='<ul>';
	 	$out='<fieldset class="'.$fcls.'">';
	 	foreach($schema as $name=>$element) {
	 	 	$type=ArrayUtil::getValue($element,'type');
	 	 	$descr=ArrayUtil::getValue($element,'descr',FALSE);
	 	 	$attr=ArrayUtil::getValue($element,'attr');
	 	 	$subset=ArrayUtil::getValue($element,'subset',array());
	 	 	$inputName=$prefix.".".$name;
	 	 	$elementPath=$prefix.'.'.$name;
	 	 	$markCls=(in_array($elementPath,$markups)) ? 'xsd xsd-markup' : 'xsd';
	 	 	$out.='<div class="field '.$markCls.'">';
	 	 	$anchor=md5($inputName);
	 	 	$toc.='<li class="'.$markCls.'"><a href="#toc'.$anchor.'" title="'.$elementPath.'">'.$name.'</a>';
	 	 	$out.='<label><a name="toc'.$anchor.'">'.$name.'</a></label>';
	 	 	if ($descr) {
	 	 	 	$out.='<div class="descr">'.$descr.'</div>';
	 	 	}
	 	 	
	 	 	
	 	 	// attr
	 	 	if ($attr) {
	 	 	 	$out.='<ul>';
	 	 	 	foreach($attr as $aname=>$adata) {
	 	 	 	 	$out.='<li>';
	 	 	 	 	$out.='<label>'.$aname.'</label>';
	 	 	 	 	$atype=ArrayUtil::getValue($adata,'type',FALSE);
	 	 	 	 	$aoptions=ArrayUtil::getValue($adata,'options',FALSE);
	 	 	 	 	
	 	 	 	 	if (is_array($aoptions)) {
	 	 	 	 	 	$out.=$selectInput->renderInput(array('name'=>$inputName.'_ATTR_'.$aname,'options'=>$aoptions));
	 	 	 	 	}
	 	 	 	 	else {
	 	 	 	 	 	switch($atype) {
	 	 	 	 	 	 case $xsdNs.'boolean' :
	 	 	 	 	 			$out.=$selectInput->renderInput(array('name'=>$inputName.'_ATTR'.$aname,'options'=>array('0'=>'nein','1'=>'ja')));
	 	 	 	 	 			break;
	 	 	 	 	 	 default: 
	 	 	 	 	 	 		$out.=$textInput->renderInput(array('name'=>$inputName.'_ATTR'.$aname));
	 	 	 	 	 	 }
	 	 	 	 	}
	 	 	 	 	$out.='</li>'."\n";
	 	 	 	}
	 	 	 	$out.='</ul>'."\n";
	 	 	 	$out.='<div class="eol eos">&nbsp;</div>'."\n";
	 	 	}
	 	 	if (!empty($subset)) {
	 	 	 	$out.=$this->renderAsForm($subset,$inputName,$level+1,$toc,$markups);
	 	 	}
	 	 	if (!empty($type)){
	 	 	 	$out.=$textInput->renderInput(array('name'=>$inputName));
	 	 	}
	
	 	 	$out.='</div>';
	 	 	$toc.='</li>'."\n";
	 	}
	 	$toc.='</ul>'."\n";
	 	$out.='</fieldset>'."\n";
	 	return $out;
	 
	}
	
	
 	
 	
 	
 
 
}