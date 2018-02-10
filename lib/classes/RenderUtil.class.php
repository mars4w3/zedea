<?php

class RenderUtil {
 
 	/*
 	function toUTF8($val) {
 	 	$out=$val;
		if (is_string($out)) {
 	 	 	if(mb_detect_encoding($str, 'UTF-8, ISO-8859-1') === 'UTF-8'){
 				 return $out;
			}
			return $out;
 	 	}
 	 	return $out;
 	}
 	*/
 
 
 	static function renderAsHTML($string) {
 	 
 	 	$out=$string;
 	 	$out=nl2br($out);
 	 	return $out;
 	 
 	}
 	
 	
 	static function renderAsTable($array=array(),$args=array(),$captions=array(),$sortable=array()) {
		
		if (!is_array($array)) {
		 	return '';
		}
		
		if (empty($array)) {
		 	return '';
		}
 	 	
 	 	$tableAttr=ArrayUtil::getValue($args,'tableAttr',FALSE);
 	 	$tableAttrList='';
 	 	
 	 	if ($tableAttr) {
 	 	 	$attr=ArrayUtil::getValue($tableAttr,'attr',array());
 	 	 	$tableAttrList=HTMLFragment::renderAttrlist($attr);
 	 	}
 	 	
 	 	$out="\n".'<table '.$tableAttrList.'>';
 	 	
 	 	$colWidths	= ArrayUtil::getValue($args,'colWidths',FALSE);
 	 	if ($colWidths) {
 	 	 	$out.="\n".'<colgroup>';
 	 	 	$colGroups=(is_array($colWidths)) ? $colWidths: explode(',',$colWidths);
 	 	 	foreach($colGroups as $colWidth) {
 	 	 	 	$out.='<col width="'.$colWidth.'"/>';
 	 	 	}
			$out.="\n".'</colgroup>';   	
 	 	}
 	 	
 	 	
 	 	$showHeaders		= ArrayUtil::getValue($args,'showHeaders',TRUE);
 	 	$callBackOnValue	= ArrayUtil::getValue($args,'callbackOnValue',FALSE);
 	 	
 	 	
 	 
 	 
 	 	if ($showHeaders) {
 	 
	 	 	$line=$array[0];
	 	 	$out.="\n".'<tr>';
	 	 	$cc=0;
	 	 	foreach($line as $key => $value)  
			{
				$out.="\n".'<th class="th-'.$cc.'">';
			 	$th	=ArrayUtil::getValue($captions,'col_'.$key,$key);
				if (is_array($sortable)) {
				 	SortFilterQueryUtil::markupSortableColHeader($th,$key,$sortable);
				}
				$out.=$th;	
			 	$out.='</th>';
			 	$cc++;
			}
			$out.='</tr>';
		
		}
		
 	 	reset($array);
 	 	$rowcount=0;
 	 
 		foreach($array as $index => $row) 
		{
		 	$trclass=(($rowcount%2)==0) ? 'even' : 'odd';
 	 		$out.="\n".'<tr class="'.$trclass.'">';
 	 		$cc=0;
			foreach($row as $cell => $value) {
			 	$out.="\n".'<td class="td-'.$cc.'">';
			 	if (is_callable($callBackOnValue)) {
			 	 	$out.=call_user_func($callBackOnValue,$value);
			 	}
				else {
			 		$out.=$value;
				}	
			 	$out.='</td>';
			 	$cc++;
			}	  
			$out.='</tr>';  
			$rowcount++;	
 	 	}
 	 	
 	 	$out.="\n".'</table>';
 	 	
 	 	return $out;
 	 
 	}
 	

	static function renderByTemplate($array=array(),$tmpl='',$attr=array(),$args=array()) {
	 
	 	
		if (!is_array($array)) {
		 	return '';
		}
		
		if (empty($array)) {
		 	return '';
		}
		
		if (empty($tmpl)) {
		 	return '';
		}

		$ulclass=ArrayUtil::getValue($attr,'ulclass','item-list');
 	 	$liclass=ArrayUtil::getValue($attr,'liclass','list-item');
 	 	
 	 	$listTag=ArrayUtil::getValue($args,'listtag','ul');
		$listItemTag=ArrayUtil::getValue($args,'listitemtag','li');
		
		$addCSS=(ArrayUtil::getValue($args,'addCSS',TRUE)) ? TRUE : FALSE;
 	 	
 	 	$ulCSSExt=($addCSS) ? ' class="'.$ulclass.'"' : '';
 	 	$out='<'.$listTag.$ulCSSExt.'>';
 	 	$r=0;
 	 	
		foreach($array as $index => $row) 
		{
		 	$r++;
			$OddEven=($r%2==0) ? 'even' : 'odd'; 
			
		
			$liCSSExt=($addCSS) ? ' class="'.$liclass.' '.$OddEven.'"' : '';	
				
		 	$out.="\n".'<'.$listItemTag.$liCSSExt.'>';
		 	$itemOut=$tmpl;
			
			foreach($row as $cell => $value) {
			 	$repl=is_array($value) ? CastUtil::toString($value) : $value;
				$itemOut=str_replace('%%'.$cell.'%%',$repl,$itemOut);  		
			}
			$itemOut=preg_replace('/\%\%([^\%]*)\%\%/imu','',$itemOut);
			$out.=$itemOut;
			$out.='</'.$listItemTag.'>';	    	
 	 	} 	 
	 
	 	$out.='</'.$listTag.'>';
	 
	 	return $out;
	 
	 
	} 
 


 	
 	static function renderAttrList($attr=array()) {
 	 
 	 	 	$options=array(
				'listTag'=>'',
				'listItemTag'=>' ',
				'listItemKeyTag'=>'',
				'listItemValueTag'=>'"', 
				'listItemKeyValueSeparator'=>'=',
				'tagLeft'=>'',
				'tagRight'=>'',
				'tagClose'=>'',  
			);
	 	 	$attlist=ArrayUtil::toHTML($attr,$options);
	 	 	return $attlist;
	 	 	
 	 
 	}
 

	static function toCSV($array,$sep=';',$encl='"',$toUTF=FALSE) {
	 	$out='';
	 	foreach ($array as $index=>$row) {
	 	 	$lineout='';
		 	foreach ($row as $key=>$val) {
		 	 	$lineout.=(!empty($lineout)) ? $sep : '';
		 	 	$lineout.=$encl;
		 	 	
		 	 	$csvval=$val;
		 	 	$csvval=strip_tags($csvval);
		 	 	$csvval=stripslashes($csvval);
		 	 	
		 	 	if ($toUTF) {
		 	 	 	$csvval=utf8_encode($csvval);
		 	 	}
		 	 	
		 	 	$csvval=str_replace($encl,$encl.$encl,$csvval);
		 	 	
		 	 	$lineout.=$csvval;
			  	
			  	$lineout.=$encl;
		 	}
		 	$out.=$lineout."\n";
	 	}
	 	
	 	return $out;
	 
	}
	
	
	static function toXML($array,$encoding='utf-8') {
	 	
	 	$roottag='dataset';
		$out='';
	 	
	 	$out.='<'.'?'.'xml version="1.0" encoding="'.$encoding.'" ?>';
	 	$out.="\n";
	 	$out.='<!DOCTYPE '.$roottag.'>';
	 	$out.="\n";
	 	$out.='<'.$roottag.'>';
	 	$out.="\n";
	 	$out.=ArrayUtil::toXML($array);
	 	$out.="\n";
	 	$out.='</'.$roottag.'>';
	 	$out.="\n";
	 	return $out;
	 
	}
	
	
	
	static function toPHP($array,$varname='config') {
	 
	 	$out='<'.'?'.'php';
	 	$out.="\n";
	 	$out.='$'.$varname.' = ';
		$out.=ArrayUtil::toPHP($array);
		$out.="\n";
		$out.='?'.'>';
		
		return $out;  
	 
	 
	}
	
	
	

	
	

 
 
}

?>