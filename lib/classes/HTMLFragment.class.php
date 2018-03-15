<?php

class HTMLFragment extends HTMLDocument {

	var $name='section';
	var $elements=array();
	var $output='';
	
 
 	function __construct($name='') {
	 	if (empty($name)) {
	 	 	$name='section_'.count($this->elements);
	 	}
	 	$this->name=$name;
	 	
	}
	
	
	function open() {
	 	$this->output.='<section id="'.$this->name.'">';
	 	$this->newLine();
	 
	}
	
	function close() {
	 	$this->newLine();
	 	$this->output.='</section>';
	}
 
 
 	function header() {
	
		$this->output.='<div class="header">';
		$this->newLine();	 
	 	$elements=ArrayUtil::getValue($this->elements,'head',array());
		$this->output.=$this->build($elements);
		$this->output.='</div>';
		$this->newLine();	 
	 
	}
	
	
	function body() {
	 	
	 	$this->output.='<div class="body">';
		$this->newLine();
	 	$elements=ArrayUtil::getValue($this->elements,'body',array());
		$this->output.=$this->build($elements);
		$this->output.='</div>';
		$this->newLine();
	 
	}
	
	

	
	function out() {
	 	$this->open();
	 	$this->header();
	 	$this->body();
	 	$this->close();
	 	return $this->output;
	}
	
	
	// staticFunctions
	
	static function indentBlock($string) {
	 
	 	$before="\n\n";
	 	$after="\n\n";
	 	$out=$before.$string.$after;
	 	return $out;
	 
	}
	
	static function setAttr($attr,$value,&$current=array()) {
	 	if (!isset($current['attr'])) {
	 	 	$current['attr']=array();
	 	}
		$current['attr'][$attr]=HTMLFragment::escape($value);
	}
	
	static function renderAttrList($attr) {
	 	return RenderUtil::renderAttrList($attr);
	 
	}
	
	
	static function addAlert($string,$type='item',&$current=array()) {
	 	if (empty($string)) {
	 	 	return;
	 	}
	 	if ($type=='title') {
	 	 	$current['title']=$string;	
	 	}
	 	else {
			$current['items'][]=$string;
		}
	}
	
	static function renderAlerts($alerts,$type='error') {
	 	
	 	$args=array();
	 	$out='';
	 	HTMLFragment::setAttr('class','alert-title',$args);
	 	$title=ArrayUtil::getValue($alerts,'title','');
	 	if (!empty($title)) {
	 		$out.=HTMLFragment::renderHeading(ArrayUtil::getValue($alerts,'title',''),3,$args);
	 	}
	 	$items=ArrayUtil::getValue($alerts,'items',array());
	 	if (!empty($items)) {
	 		$out.=HTMLFragment::renderSimpleList($items);
	 	}
	 	
	 	if (empty($out)) {
	 	 	return '';
	 	}
	 	
	 	$args=array();
	 	HTMLFragment::setAttr('class','alert '.$type,$args);
	 	$out=HTMLFragment::renderSection($out,$args);
	 	$out=HTMLFragment::indentBlock($out);
	 	return $out;
	 
	}
	
	
	static function renderSimpleList($array=array(),$listtag='ul') {
	 	$out='<'.$listtag.'>';
	 	$out.="\n";
	 	foreach ($array as $item) {
	 	 	$out.='<li>'.$item.'</li>';
	 	 	$out.="\n";
	 	}
	 	$out.='</'.$listtag.'>';
	 	$out.="\n";
	 	return $out;
	 	
	 
	}
	
	static function renderLink($url,$title,$args=array()) {
	 
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,null,$url);
	 	$attr=ArrayUtil::getValue($args,'attr',array());
	 	$attrList=RenderUtil::renderAttrList($attr);
	 	$out='<a '.$attrList.' href="'.$url.'"><span>'.$title.'</span></a>';
	 	return $out;
	}
	
	static function renderMailLink($email,$title,$args=array()) {
	 	$url='mailto:'.$email;
	 	return HTMLFragment::renderLink($url,$title,$args);
	}
	
	static function renderURLLink($url,$args=array()) {
	 	$urlparts=FileUtil::getURLInfo($url);
	 	$urltitle=ArrayUtil::getValue($urlparts,'host',$url);
	 	return HTMLFragment::renderLink($url,$urltitle,$args);
	}
	
	static function renderAnchor($anchor,$title,$args=array()) {
	 	$url='#'.$anchor;
	 	HTMLFragment::setAttr('name',$anchor,$args);
	 	HTMLFragment::setAttr('class','anchor',$args);
	 	return HTMLFragment::renderLink($url,$title,$args);
	}
	
	static function renderHeading($content,$level=1,$args=array()) {
	 	$attr=ArrayUtil::getValue($args,'attr',array());
	 	$attrList=RenderUtil::renderAttrList($attr);
	 	
	 	$out='<h'.$level.' '.$attrList.'>'.$content.'</h'.$level.'>';
	 	return $out;
	}

	static function renderSection($content,$args=array()) {
	 	$tagname=ArrayUtil::getValue($args,'tagname','div');
	 
	 	$attr=ArrayUtil::getValue($args,'attr',array());
	 	$attrList=RenderUtil::renderAttrList($attr);
	 	
	 	$out='<'.$tagname.' '.$attrList.'>'.$content.'</'.$tagname.'>';
	 	$out=HTMLFragment::indentBlock($out);
	 	return $out;
	}	
	
	static function renderEOS($tagname='div') {
	 	return '<'.$tagname.' class="eos"></'.$tagname.'>';
	}
	
	static function renderForm($content,$args=array()) {
	 	$method=ArrayUtil::getValue($args,'method','post');
	 	$action=ArrayUtil::getValue($args,'action','');
	 	
	 	$attr=ArrayUtil::getValue($args,'attr',array());
	 	$attrList=RenderUtil::renderAttrList($attr);
	 	
	 	$out='<form '.$attrList.' method="'.$method.'" action="'.$action.'">';
	 	$out.=$content;
	 	$out.='</form>';
	 	return $out;
	}
	
	
	static function renderFieldset($content,$fieldset=TRUE) {
	 	$tagname=($fieldset) ? 'fieldset' : 'div';
	 	$args=array('tagname'=>$tagname);
	 	if (!$fieldset) {
	 	 	HTMLFragment::setAttr('class','ffset',$args);
	 	}
	 	return HTMLFragment::renderSection($content,$args);
	}
	
	static function renderDropdownBox($name,$options=array(),$default='',$attr=array(),$addSubmitter=FALSE) {
	 
	 	$ffconf=array(
			'name'=>$name,
			'default'=>$default,
			'inputtype'=>'select',
			'options'=>$options, 
			'attr'=>$attr, 
			'addSubmitter'=>$addSubmitter,
		);
		$Form=new HTMLForm();
 	 	$selector=$Form->ffInput($ffconf);
 	 	
 	 	return $selector;
	 
	}
	
	
	
	static function renderScript($content,$compress=FALSE) {
	 
	 	if ($compress) {
	 	 	$content=str_replace(array("\n","\r","\t","  "),' ',$content);
	 	 	$content=trim($content);
	 	 	//$content=chunk_split($content,80);
	 	}
	 
	 	$args=array('tagname'=>'script');
	 	HTMLFragment::setAttr('type','text/javascript',$args);
	 	return HTMLFragment::renderSection($content,$args);
	 
	}
	
	
	static function renderWidgetBox($heading,$body,$cssprefix='widget',$args=array()) {
	 
 		$hdgArgs=array();
 		HTMLFragment::setAttr('class',$cssprefix.'-title',$hdgArgs);
		$title=HTMLFragment::renderHeading($heading,3,$hdgArgs);
 
 		$sectArgs=array('tagname'=>'div');
 		$bodyID=ArrayUtil::getValue($args,'body_id',FALSE);
		if ($bodyID) {
		 	HTMLFragment::setAttr('id',$bodyID,$sectArgs);
		}
		HTMLFragment::setAttr('class',$cssprefix.'-body',$sectArgs);
		$section=HTMLFragment::renderSection($body,$sectArgs);
	
		$boxArgs=array('tagname'=>'div');
		HTMLFragment::setAttr('class',$cssprefix.'-box',$boxArgs);
		$box=HTMLFragment::renderSection($title.$section,$boxArgs);
		
		$wrapArgs=array('tagname'=>'div');
		$wrapID=ArrayUtil::getValue($args,'wrapper_id',FALSE);
		if ($wrapID) {
		 	HTMLFragment::setAttr('id',$wrapID,$wrapArgs);
		}
		HTMLFragment::setAttr('class',$cssprefix.'-wrap',$wrapArgs);
		$wrapper=HTMLFragment::renderSection($box,$wrapArgs);
		
		return $wrapper;
	 

	}
	
	
	static function renderPagination($items=0,$maxperpage=1,$args=array()) {
	 	$items=intval($items);
	 	$maxperpage=intval($maxperpage);
		if ($items==0) {
	 	 	return '';
	 	}
		
		if ($maxperpage==0) {
	 	 	return '';
	 	}
		$pages=intval($items/$maxperpage);
		$rest=$items-($pages*$maxperpage);
		$pages+=($rest>0) ? 1 : 0;
		if ($pages==1) {
		 	return '';
		}
		
		$maxpages=10;
		$pgParam=ArrayUtil::getValue($args,'pgParam','_pg');
		$maxinterval=(intval($pages/$maxpages/10)+1)*10;
		$currentPage=RequestUtil::getParam($pgParam,1);
		
		$out='<ul class="pagination">';
		
		$newparams=ArrayUtil::getValue($args,'params',array());
		$linkclass=ArrayUtil::getValue($args,'linkclass','');
		
		$dots=-1;
		
		for ($p=1;$p<$pages+1;$p++) {
		 
		 	$class=$linkclass;
		 
		 	$newparams[$pgParam]=$p;
		 	$show=TRUE;
		 	if ($pages>$maxpages) {
		 	 	$show=FALSE;
		 	 	$dots++;
		 	 	if ($p==1 || $p==$pages) {
		 	 	 	$show=TRUE;
		 	 	}
		 	 	if ($p>$currentPage-2 && $p<$currentPage+2) {
		 	 	 	$show=TRUE;
		 	 	 	$class.=' nearby';
		 	 	}
		 	 	if ($p%$maxinterval==0) {
		 	 	 	$show=TRUE;
		 	 	}
		 	}
			 
			if ($show) {
			 	$dots=-1;
				$out.='<li>';
	
				$uri=RequestUtil::getRequestUri();
				$uri.=RequestUtil::getQueryString($newparams);
		
				$title=$p;
				$class.=($p==$currentPage) ? ' current active' : '';
				$args=array('attr'=>array('class'=>$class));
		
				$out.=HTMLFragment::renderLink($uri,$p,$args);
				$out.='</li>';
			}
		
			if ($dots==0) {
			 	$out.='<li>';
				$title='...';
				$uri=RequestUtil::getRequestUri();
				$uri.=RequestUtil::getQueryString($newparams);
				$class.=' inactive';
				$out.=HTMLFragment::renderLink($uri,$title,$args);
				$out.='</li>';
			}
		}
		$out.='</ul>';
		
		return $out;	
	 
	 
	}
	
	
	static function renderFile($file='',$args=array(),$useMimeType='') {
	 	$out='';
	 	
	 	if ($callbackResult=OverrideUtil::getCallbackResult(__METHOD__,$file)) {
	 	 	return $callbackResult;
	 	}
	 	
		$attr=ArrayUtil::getValue($args,'attr',array());
	 	$attrList=RenderUtil::renderAttrList($attr);
	 	
	 	
	 	if (!empty($useMimeType)) {
	 	 	$mime=$useMimeType;
	 	}
	 	else {
	 	 	$mime=FileUtil::getMimeType($file);
	 	
	 	}
		 
		if (stristr($mime,'image')) {
		 	//$out.='<div class="file-display" style="overflow:auto;">';
	 	 	$out.=HTMLFragment::renderImage($file,$args);
	 	 	//$out.='</div>';
	 	 	$out.=HTMLFragment::renderDownloadFile($file);
	 	}
	 	
	 	else {
	 	 	$out=HTMLFragment::renderDownloadFile($file);
	 	 
	 	}
	
	 	return $out;
	 
	}
	
	
	static function renderImage($file='',$args=array()) {
	
		if (empty($file)) {
		 	return '';
		}
		
		if ($callbackResult=OverrideUtil::getCallbackResult(__METHOD__,$file)) {
	 	 	return $callbackResult;
	 	}
		
		$attr=ArrayUtil::getValue($args,'attr',array());
	 	$tag=ArrayUtil::getValue($args,'tag','figure');
	 	$imgwidth=ArrayUtil::getValue($args,'imgwidth',150);
	 	
	 	$httppath=trim(FileUtil::getHTTPPath($file));
	 	
	 	if (empty($httppath)) {
	 	 	return '';
	 	}
 	 	
	 	$attrlist=RenderUtil::renderAttrList($attr);
	 	$out='<'.$tag.' '.$attrlist.' class="file-display image-display">';
	 	$imgTag='<img width="'.$imgwidth.'" style="max-width:'.$imgwidth.'px;height:auto;" src="'.$httppath.'" alt="image"/>';
	 	
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,null,$imgTag);
	 	$out.=$imgTag;
	 	$out.='</'.$tag.'>';
	 	return $out;
	 
	}
	
	static function renderDownloadFile($file) {
	 	
	 	//$filepath=$file;
	 	$filepath=$file;
	 	//die($filepath);
	 
		$info=FileUtil::describeFile($filepath);
 	 	$fname=ArrayUtil::getValue($info,'BaseName','');
 	 	$fnamedisp=chunk_split($fname,25);
 	 	$fext=ArrayUtil::getValue($info,'Extension','');
 	 	$linkclass='download '.$fext;
 	 	$linkArgs=array();
 	 	
 	 	$dispinfo='<span class="link-descr">';
		$dispinfo.=FileUtil::displaySize(ArrayUtil::getValue($info,'FileSize',0));
		$dispinfo.=' - ';
		$dispinfo.=DateTimeUtil::formatGivenDate(ArrayUtil::getValue($info,'FileCTime',0),'%d.%m.%Y %H:%M').'';
		$dispinfo.='</span>';
		
		//$out.=$dispinfo;

		$linktitle='Download';
		$linktitle.=$dispinfo;
 	 	
 	 	HTMLFragment::setAttr('class',$linkclass,$linkArgs);
 	 	HTMLFragment::setAttr('target','_blank',$linkArgs);

		$httppath=trim(FileUtil::getHTTPPath($file));
	 	
	 	if (empty($httppath)) {
	 	 	return '';
	 	}

 	 	$link=HTMLFragment::renderLink($httppath,$linktitle,$linkArgs);
 	 	
		$out='';
 	 	//$out.='<div>';
 	 	//$out.='<span title="'.$fname.'">'.$fnamedisp.'</span>';
 	 	//$out.='</div>';
		//$out.='<div>'.$link.'</div>';

		$out.=$link;
		
		$sectArgs=array();
		HTMLFragment::setAttr('class','file-display file-download',$sectArgs);
		$out=HTMLFragment::renderSection($out,$sectArgs);
		
		return $out;
	}

	static function escape($string,$mode='Attribute') {
	 
	 	$out=$string;
	 	switch($mode) {
	 	 	case 'Qname' : 
	 	 					$out=preg_replace('/([^\w]*)/','',$out);
	 	 					$out=stripslashes($out);
	 	 					break;
	 	 	case 'Attribute' : 
			  				$out=str_replace('"','&#34;',$out);
							$out=stripslashes($out);
							break;  					
	 	 
	 	}
	 	return $out;
	 
	}
	
 
}


?>