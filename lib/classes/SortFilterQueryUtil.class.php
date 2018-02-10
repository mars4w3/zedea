<?php

class SortFilterQueryUtil {
 
 	
 	
 	static function getSortAndFilter(&$args=array(),$allow=array()) {
 	 
 	 	$allowFilter=ArrayUtil::getValue($allow,'filter',array());
 	 	$allowSort=ArrayUtil::getValue($allow,'sortable',array());
 	 	$allowSearch=ArrayUtil::getValue($allow,'searchable',array());
 	 	
 	 	
 	 	$sortParam=RequestUtil::getParam('_sBO',FALSE);
 	 	if ($sortParam) {
 	 	 	$info=explode('_',$sortParam);
 	 	 	$sortIndex=intval($info[0]);
 	 	 	if (isset($allowSort[$sortIndex])) { $args['sortBy']=$allowSort[$sortIndex]; }
 	 	 	$args['sortOrder']=(intval($info[1])==1) ? 'DESC' : 'ASC';
 	 	}
 	 	$filter=ArrayUtil::getValue($args,'filter','1');
 	 	for ($f=0;$f<count($allowFilter);$f++) {
 	 	 	$filterParam=$allowFilter[$f];
			$filterValue=RequestUtil::getParam($filterParam,FALSE);
			if ($filterValue!==FALSE && $filterValue!=='') {
			  $addFilter=' ( '.$filterParam.'='.intval($filterValue).' ) ';
			  $filter.=(!empty($filter)) ? ' AND ' : '';
			  $filter.=$addFilter;
			}
 	 	}
 	 	
 	 	// test if _qW 
 	 	$queryWord=RequestUtil::getParam('_qW',FALSE);
 	 	if ($queryWord) {
 	 	 	$qList='';
 	 	 	$qFilter='';
 	 	 	foreach ($allowSearch as $searchCol) {
 	 	 	 	$qList.=(!empty($qList)) ?  ",'|'," : '';
 	 	 	 	$qList.=$searchCol;
 	 	 	}
 	 	 	if (!empty($qList)) {
 	 	 	 	$qFilter=" (CONCAT(".$qList.") LIKE '%".$queryWord."%') "; 
 	 	 	 	$filter.=(!empty($filter)) ? ' AND ' : '';
				$filter.=$qFilter;
 	 	 	} 	
 	 	}
 	
 	 	$args['filter']=$filter;
 	 
 	}
 	
 	static function remapSortable(&$sortable=array(),$map=array()) {
 	 	for ($s=0;$s<count($sortable);$s++) {
 	 	 	$defSort=$sortable[$s];
 	 	 	$sortable[$s]=ArrayUtil::getValue($map,$defSort,$defSort);
 	 	}
 	 	reset($sortable);
 	}
 	
 	
 	static function markupSortableColHeader(&$th='',$colname='',$sortable=array()) {
 	 	
 	 	if (empty($th)) {
 	 	 	return;
 	 	}
 	 	if (empty($colname)) {
 	 	 	return;
 	 	}
 	 	if (!is_array($sortable)) {
 	 	 	return;
 	 	}
 	 	if (!in_array($colname,$sortable)) {
 	 	 	return;
 	 	}
 	 	
 	 	$sortIndex=0;
 	 	$sortOrder=0;
 	 	
 	 	
 	 	$sortParam=RequestUtil::getParam('_sBO',FALSE);
 	 	if ($sortParam) {
 	 	 	$info=explode('_',$sortParam);
 	 	 	$sortIndex=intval($info[0]);
 	 	 	$sortOrder=intval($info[1]);
 	 	}
 	 	
 	 	for ($s=0;$s<count($sortable);$s++) {
 	 	 	
 	 	 	$sortcol=$sortable[$s];
 	 	 	if ($sortcol==$colname) {
 	 	 		$newSortVal=$s.'_';
	 	 	 	
	 	 	 	$newSortOrder='1';
				$newSortDisp='down';
						
	 	 	 	if ($sortIndex==$s) { 
	 	 	 	 	$newSortOrder=($sortOrder==0) ? '1' : '0';
					$newSortDisp=($sortOrder==0) ? 'down' : 'up'; 
				}
				
				$newSortVal.=$newSortOrder;
				
				$sortURI=RequestUtil::getRequestURI();
				$sortURI.=RequestUtil::getQueryString(array('_sBO'=>$newSortVal));
				$th='<a class="sortable sort-'.$newSortDisp.' " href="'.$sortURI.'" title="'.$newSortDisp.'">'.$th.'</a>';
			}
			
 	 	}
 	}
 	
 	
 	
 	static function getSelector($args=array()) {
 	 
 	 	$dbtable=ArrayUtil::getValue($args,'dbtable',FALSE);
 	 	if (!$dbtable) {
 	 	 	return '';
 	 	}
 	 
 	 	$options=SortFilterQueryUtil::getDBOptions($args);
 	 	

 	 	$args['options']=$options;
 	 	$out=SortFilterQueryUtil::buildSelector($args);
 	 	
 	 	return $out;
 	 	
 	 
 	}
 	
 	
 	 	
 	static function buildQueryForm($conf=array()) {
 	 
 	 	$form='';
 	 	$labelArgs=array('tagname'=>'label');
 	 	$fname='_qW';
 		$ff_conf=
			array( 
				'name' 		=> $fname ,
				'label' 	=> ArrayUtil::getValue($conf,'flabel',FALSE), 
				'inputtype' => 'text' ,
				'attr'		=> array('onchange'=>'this.form.submit();'),
				'value'		=> RequestUtil::getParam($fname,''),
				);
				
		$FF=HTMLFormField::factory('FF_text');
		$form.=HTMLFragment::renderSection($ff_conf['label'],$labelArgs);
		$form.=$FF->renderInput($ff_conf);
		
		$asHidden=RequestUtil::filterParams('GET');
		$exclude=array('_pg');
		$hidden='';
		foreach ($asHidden as $key=>$val) {
		 	if ($key!=$fname && !in_array($key,$exclude)) {
		 		$hidden.='<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
		 	}
		}
		$form.=$hidden;
		$form=HTMLFragment::renderSection($form);
		
		$form=HTMLFragment::renderForm($form,array('method'=>'get'));
		
		HTMLFragment::setAttr('class','content-toolbar',$sectArgs);
		$out=HTMLFragment::renderSection($form,$sectArgs);
		
		return $out;
 	 
 	}
 	
 	
 	
 	static function buildSelector($conf=array()) {
 	 
 	 	$form='';
 	 	$labelArgs=array('tagname'=>'label');
 	 	$fname=ArrayUtil::getValue($conf,'fname',FALSE);
 		$ff_conf=
			array( 
				'name' 		=> $fname ,
				'label' 	=> ArrayUtil::getValue($conf,'flabel',FALSE), 
				'inputtype' => 'select' ,
				'options'	=> ArrayUtil::getValue($conf,'options',FALSE),
				'attr'		=> array('onchange'=>'this.form.submit();'),
				'value'		=> RequestUtil::getParam($fname,0),
				);
				
		$FF=HTMLFormField::factory('FF_select');
		$form.=HTMLFragment::renderSection($ff_conf['label'],$labelArgs);
		$form.=$FF->renderInput($ff_conf);
		
		$asHidden=RequestUtil::filterParams('GET');
		$hidden='';
		foreach ($asHidden as $key=>$val) {
		 	if ($key!=$fname) {
		 		$hidden.='<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
		 	}
		}
		$form.=$hidden;
		$form=HTMLFragment::renderSection($form);
		
		$form=HTMLFragment::renderForm($form,array('method'=>'get'));
		
		HTMLFragment::setAttr('class','content-toolbar',$sectArgs);
		$out=HTMLFragment::renderSection($form,$sectArgs);
		
		return $out;
 	 
 	}


 	
 	static function buildMultiFilterSelector($selectors=array()) {
 	 
 	 	$fnames=array();
 	 	$form='';
	  	$fsetArgs=array();
	  	HTMLFragment::setAttr('class','ffset',$fsetArgs);
 	 	foreach ($selectors as $fname=>$ff_conf) {
 	 	 	$ftype=ArrayUtil::getValue($ff_conf,'inputtype','select');
 	 	 	$FF=HTMLFormField::factory('FF_'.$ftype);
 	 	 	$ff_conf['attr']=array('onchange'=>'this.form.submit();');
 	 	 	$ff_conf['value']=RequestUtil::getParam($fname,0);
 	 	 	$ffInput=$FF->renderInput($ff_conf);
 	 	 	$flabel=ArrayUtil::getValue($ff_conf,'label','');
 	 	 	if (!empty($flabel)) { $ffInput='<label for="'.$fname.'">'.$flabel.'</label>'.$ffInput; }
 	 	 	$form.=HTMLFragment::renderSection($ffInput,$fsetArgs);
 	 		$fnames[$fname]=$fname; 	
 	 	}
 	 
 	 	
		$asHidden=RequestUtil::filterParams('GET');
		$hidden='';
		foreach ($asHidden as $key=>$val) {
		 	if (!in_array($key,$fnames)) {
		 		$hidden.='<input type="hidden" name="'.$key.'" value="'.$val.'"/>';
		 	}
		}
		$form.=$hidden;
		$form=HTMLFragment::renderSection($form);
		
		$form=HTMLFragment::renderForm($form,array('method'=>'get'));
		
		HTMLFragment::setAttr('class','content-toolbar',$sectArgs);
		$out=HTMLFragment::renderSection($form,$sectArgs);
		
		return $out;
 	 
 	}


	static function getDBOptions($conf=array()) {
	 
	 	$DBDriver=Application::getAppDBDriver();
	 	$args=array(
	 		'dbtable'	=> ArrayUtil::getValue($conf,'dbtable',FALSE),
			'columns'	=> ArrayUtil::getValue($conf,'columns',array('*')),
			'filter'	=> ArrayUtil::getValue($conf,'filter','1'),
			'sortBy'	=> ArrayUtil::getValue($conf,'sortBy','id'),
			'limit'		=> ArrayUtil::getValue($conf,'limit',100),
			);
		
		$data=$DBDriver->select($args);

		$options=array(0=>'Filter:');
		
		$valCol			=		ArrayUtil::getValue($conf,'valCol','id');
		$dispCol		=		ArrayUtil::getValue($conf,'dispCol','id');
		
		foreach ($data as $num=>$row) {
		 	$oid=$row[$valCol];
		 	$olabel=$row[$dispCol];
		 	$options[$oid]=$olabel;
		}	
	 	
	 	return $options;
	 
	}
 
 
 
 
}