<?php


class ArrayUtil {



	static function hasKey($array=array(),$key='') {
		if (!is_array($array)) {
	 	 	return FALSE;
	 	}	 
	 	return (isset($array[$key])) ? TRUE : FALSE;
	}


	static function getKeys($array=array()) {
	 	if (!is_array($array)) {
	 	 	return FALSE;
	 	}
	 	$out=array();
	 	foreach ($array as $key=>$val) {
	 	 	$out[]=$key;
	 	}
	 	return $out;
	 
	}
	
	static function inArray($array=array(),$key='') {
		if (!is_array($array)) {
	 	 	return FALSE;
	 	}	 
	 	return (in_array($key,$array)) ? TRUE : FALSE;
	}

	static function getValues($array=array()) {
	 	if (!is_array($array)) {
	 	 	return FALSE;
	 	}
	 	$out=array();
	 	foreach ($array as $key=>$val) {
	 	 	$out[]=$val;
	 	}
	 	return $out;
	 
	}


	static function getValue($array=array(),$key='',$default=FALSE) {
	 	if (!is_array($array)) {
	 	 	return $default;
	 	}
	 	if (empty($key)) {
	 	 	return $default;
	 	}
	 	if (is_array($key) || is_object($key)) {
	 	 	return $default;
	 	}

	 	if (!isset($array[$key])) {
	 	 	return $default;
	 	}
	 	return $array[$key];
	 
	}
	
	
	static function setValue(&$array=array(),$key,$value) {
	 	$array[$key]=$value;
	}

	static function appendValue(&$array=array(),$key,$value) {
		if (!isset($array[$key])) {
			$array[$key] = (is_array($value)) ? array() : '';
		}
		if (is_array($value)) {
			$array[$key] = array_merge($value,$array[$key]);
		}
		else {
			$array[$key].=$value;
		}
	}
	
	static function unsetKey(&$array=array(),$key) {
	 	if (isset($array[$key])) {
	 		unset($array[$key]);
	 	}
	}


	
	static function flipKeysValues($array) {
	 	$out=$array;
	 	$out=array_flip($out);
	 	return $out;
	}
	
	static function reverse(&$in=array()) {
	 	if (!is_array($in)) {
	 	 	return FALSE;
	 	}
	 	$out=array();
	 	$count=count($in)-1;
	 	for ($i=$count;$i>=0;$i--) {
	 	 	$out[]=$in[$i];
	 	}
	 	$in=$out;
	 	return TRUE;
	 
	}
	
	static function replace($new=array(),&$dest=array()) {
	 	foreach ($new as $key=>$val) {
	 	 	$dest[$key]=$val;
	 	}
	}
	
	static function insertBefore($new=array(),&$dest=array()) {
	 	$dest=array_merge($new,$dest);
	}
	
	static function insertAfter($new=array(),&$dest=array()) {
	 	$dest=array_merge($old,$dest);
	}
	
	
	static function mergeAssoc($new=array(),&$dest=array(),$override=FALSE) {
	 	foreach ($new as $key=>$value) {
	 	 	if (ArrayUtil::hasKey($key,$dest)) {
	 	 	 	if ($override) {
	 	 	 	 	$current=ArrayUtil::getValue($key);
	 	 	 	 	ArrayUtil::mergeAssoc($value,$current);
	 	 	 	 	$dest[$key]=$current;
	 	 	 	}
	 	 	}
	 		else {
	 		 	$dest[$key]=$value;
	 		}
	 	 
	 	}
	 
	}
	
	
 	static function toHTML($array,$options=array()) 
	{
 	 
 	 
 	 	if (!is_array($array)) {
 	 	 	return FALSE;
 	 	}
 	 	$listTag=ArrayUtil::getValue($options,'listTag','dl');
 	 	$listItemTag=ArrayUtil::getValue($options,'listItemTag','');
		$listItemKeyTag=ArrayUtil::getValue($options,'listItemKeyTag','dt');
		$listItemKeyValueSeparator=ArrayUtil::getValue($options,'listItemKeyValueSeparator','');
		$listItemValueTag=ArrayUtil::getValue($options,'listItemValueTag','dd');
	
		$tL=ArrayUtil::getValue($options,'tagLeft','<');
		$tR=ArrayUtil::getValue($options,'tagRight','>');
		$tC=ArrayUtil::getValue($options,'tagClose','/');
	
		$out='';
	  	$out.=(!empty($listTag)) ? $tL.$listTag.$tR : '';
 	 	
		foreach($array as $key => $value) 
		{
		 	$out.=(!empty($listItemTag)) ? $tL.$listItemTag.$tR : '';
		 	$out.=(!empty($listItemKeyTag)) ? $tL.$listItemKeyTag.$tR : '';
 	 		$out.=$key;
 	 		$out.=(!empty($listItemKeyTag)) ? $tL.$tC.$listItemKeyTag.$tR : '';
 	 		
 	 		$out.=$listItemKeyValueSeparator;
 	 		
 	 		if (is_array($value)) {
 	 		 	$out.=ArrayUtil::toHTML($value,$options);	
 	 		}
 	 		else {
 	 		 	$out.=(!empty($listItemValueTag)) ? $tL.$listItemValueTag.$tR : '';
 	 		 	$out.=$value;
 	 		 	$out.=(!empty($listItemValueTag)) ? $tL.$tC.$listItemValueTag.$tR : '';
 	 		}
 	 		$out.=(!empty($listItemTag)) ? $tL.$tC.$listItemTag.$tR : '';
			
		}
 	 	
 	 	
 	 	$out.=(!empty($listTag)) ? $tL.$tC.$listTag.$tR : '';
 	 	return $out;
 	 
 	}
 	
 	
 	static function toHTMLKeyValTable($array) {
	  	$options=array(
		'listTag'			=>	'table',
 	 	'listItemTag'		=>	'tr',
		'listItemKeyTag'	=>	'td',
		'listItemValueTag'	=>	'td',
		);
		return ArrayUtil::toHTML($array,$options);
	  
	 } 
 	
 	
 	
 	static function toPHP($array,$level=1) {
 
	 	$indent=str_pad('',(($level*2)-1),'.');
	 	$indent=str_replace('.',"\t",$indent);
	 	$innerindent="\n".$indent."\t";
	 	
	 	$out='';
	 	
	 	$out.=' array( ';
	 
	 	foreach ($array as $key=>$val) {
	 	 	
	 	 	$out.=$innerindent;
			  
			if (is_string($key)) {
			 	$out.="'".addslashes($key)."' => ";
			}
			/*  
			else {
			 	$out.=$key;
			}
			$out.=' => ';
			*/
			if (is_array($val)) {
			 	$out.=ArrayUtil::toPHP($val,$level+1);
			}
			else if (is_string($val)) {
			 	$out.="'".addslashes($val)."' ,";
			}
			else if (empty($val) && $val!==0) {
			 	$out.="'' ,";
			}
			else {
			 	$out.="".$val." ,";
			}
			$out.="";   
	 	 
	 	}
	 	
	 	$out.="\n".$indent.' )  ';
	 	$out.=($level==1) ? '; ' : ',' ;
	 	$out.="\n";
	 	return $out;
	 
	 
	}  
	
	
	
		
 	static function toXML($array,$options=array(),$level=1) 
	{
 	 
 	 
 	 	if (!is_array($array)) {
 	 	 	return FALSE;
 	 	}
 	 	
 	 	$indent=str_pad('',(($level*2)-1),'.');
	 	$indent=str_replace('.',"\t",$indent);
	 	$innerindent="\n".$indent."\t";
 	 	
 	 	$out='';
		//$out.='<child>';
		//$out.="\n";
		
 	 	
		foreach($array as $key => $value) 
		{
		 
		 	//$tagname=RenderUtil::escape($key,'QNAME');
		 	$tagname=$key;
		 	$tagname=(empty($key)) ? 'node' : $key;
		 
		 	$out.=$innerindent;
		 	$out.='<';
		 	$out.=$tagname;
 	 		$out.='>';
 	 		
 	 		if (is_array($value)) {
 	 		 	$out.=ArrayUtil::toXML($value,$options,$level+1);	
 	 		 	$out.=$innerindent;
 	 		}
 	 		else {
 	 		 	$out.=$value;
 	 		}
 	 		
 	 		//$out.=$innerindent;
 	 		$out.='</';
		 	$out.=$tagname;
 	 		$out.='>';
			
		}
		
 	 	//$out.="\n";
 	 	//$out.='</child>';
 	 	//$out.="\n";
 	 	
 	 	return $out;
 	 
 	}
	
	
	
	static function toSeparatedString($array=array(),$sep=',',$encl='"',$args=array()) {
	 	
	 	$valEscape=ArrayUtil::getValue($args,'valEscapeCallback','');
	 	
	 	if (!is_array($array)) {
	 	 	return FALSE;
	 	}
	 	
	 	$out='';
	 	foreach($array as $val) {
	 	 	
	 	 	if (is_array($val)) {
	 	 	 	$val=CastUtil::toString($val);
	 	 	}
	 	 	
	 	 	$outval=$val;
	 	 	if (!empty($valEscape)) {
	 	 	 	$outval=call_user_func($valEscape,$val);
	 	 	}
	 	 
	 	 	$out.=(!empty($out)) ? $sep : '';
	 	 	$out.=$encl;
	 	 	$out.=$outval;
	 	 	$out.=$encl;
	 	}
	 
	 	return $out;	
	 
	}
	
	
	static function toKeyValString($array=array(),$sep=',',$encl='"',$args=array()) {
	 	$out='';
	 	
	 	$keyencl=ArrayUtil::getValue($args,'keyEnclosure',$encl);
	 	$valencl=ArrayUtil::getValue($args,'valEnclosure',$encl);
	 	$valEscape=ArrayUtil::getValue($args,'valEscapeCallback','');
	 	
	 	
	 	foreach($array as $key=>$val) {
	 	 	$out.=(!empty($out)) ? $sep : '';
	 	 	
	 	 	if (is_array($val)) {
	 	 	 	$val=CastUtil::toString($val);
	 	 	}
	 	 	
	 	 	$outval=$val;
	 	 	
	 	 	if (!empty($valEscape)) {
	 	 	 	$outval=call_user_func($valEscape,$val);
	 	 	}
	 	 	
	 	 	$out.=$keyencl;
	 	 	$out.=$key;
	 	 	$out.=$keyencl;
	 	 	$out.='=';
	 	 	$out.=$valencl;
	 	 	$out.=$outval;
	 	 	$out.=$valencl;
	 	}
	 
	 	return $out;
	 
	}
	
	
	
	
	 	
 	static function sortByKey($array=array(),$sortBy='',$sortOrder='ASC',$keepIndex=FALSE) {
	 
	 	if (empty($sortBy)) {
	 	 	return $array;
	 	}
	 
	 	global $_ArrayUtilVars;
	 	if (!is_array($_ArrayUtilVars)) {
	 		$_ArrayUtilVars=array();
	 	}
	 	
		$swapSortKey=ArrayUtil::getValue($_ArrayUtilVars,'SortKey');
	 	$swapSortOrder=ArrayUtil::getValue($_ArrayUtilVars,'SortOrder');
	
		ArrayUtil::setValue($_ArrayUtilVars,'SortKey',$sortBy);
		ArrayUtil::setValue($_ArrayUtilVars,'SortOrder',$sortOrder);
		
		if ($keepIndex) {
			uasort($array,array('ArrayUtil',"sortArray"));
		}
		else {
		 	usort($array,array('ArrayUtil',"sortArray"));
		}	
	 	
		ArrayUtil::setValue($_ArrayUtilVars,'SortKey',$swapSortKey);
		ArrayUtil::setValue($_ArrayUtilVars,'SortOrder',$swapSortOrder);
	
		return $array;
	 
	}
	


	static function sortArray($a,$b) {
	 	global $_ArrayUtilVars;
	 	if (!is_array($_ArrayUtilVars)) {
	 		$_ArrayUtilVars=array();
	 	}
	 	$key=ArrayUtil::getValue($_ArrayUtilVars,'SortKey');
	 	$order=ArrayUtil::getValue($_ArrayUtilVars,'SortOrder');;
	 	if ($order=='DESC') {
	 	 	$ac=1;
	 		$bc=-1;
	 	}
	 	else {
	 		$ac=-1;
	 		$bc=1;
	 	}
	 	
	 	if (is_array($key)) {
	 	 	$fkey=$key[0];
	 	 	$skey=$key[1];
	 	 	if ($a[$fkey]==$b[$fkey]) {
	 	 	 	return ($a[$skey]<=$b[$skey]) ?  $ac : $bc;
	 	 	}
	 	 	else {
	 	 	 	return ($a[$fkey]<=$b[$fkey]) ?  $ac : $bc;
	 	 	}
	 	 
	 	}
		return ($a[$key]<=$b[$key]) ?  $ac : $bc;
 	}
	
	
	
	static function toHash($array=array(),$newIndexKey='') {
	 	if (empty($newIndexKey)) {
	 	 	return $array;
	 	}
	 	if (!is_array($array)) {
	 	 	return $array;
	 	}
	 	$hash=array();
	 	foreach ($array as $key=>$data) {
	 	 	$newKey=ArrayUtil::getValue($data,$newIndexKey,$key);
			$hash[$newKey]=$data;  
	 	}
		return $hash;
	 	
	 
	}
	
	
	static function toDataMap($array=array(),$mapconf=array()) {
	 	if (empty($mapconf)) {
	 	 	return $array;
	 	}
	 	if (!is_array($array)) {
	 	 	return $array;
	 	}
	 	$map=array();
	 	foreach ($array as $index=>$row) {
	 	 	$mapItem=array();
	 		foreach ($mapconf as $oldkey=>$newkey) {
	 		 	$mapItem[$newkey]=$row[$oldkey]; 
	 		}
	 		$map[$index]=$mapItem;
		}
		return $map;
	 
	}
	
	
	
	
	
	
 
}

?>