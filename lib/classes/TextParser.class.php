<?php

class TextParser {


	static function parse($string) {
	 
	 	return $string;
	} 
	
	
	static function replace($string='',$pattern='',$replace='',$encl='',$callback=FALSE) {
	 
	 	if (empty($string)) {
	 	 	return $string;
	 	}
	 	
	 	$out=$string;
	 
	 	if (!empty($pattern)) {
		 	$rpattern=$pattern; 
		}	
	 
	 	if (!empty($encl)) {
	 	 	if (is_array($rpattern)) {
	 	 	 	for ($i=0;$i<count($rpattern);$i++) {
				    $rpattern[$i]=$encl.$rpattern[$i].$encl;
	 	 	 	 
	 	 	 	}
	 	 	}
	 	 	else {
	 	 	 	$rpattern=$encl.$rpattern.$encl;
	 	 	}
	 	}
	 	
	 

	 	
	 	if (is_array($rpattern)) {
	 	 	$out=str_replace($rpattern,$replace,$out);
	 	 	return $out;
	 	}
	 	
	 	if (!empty($encl) && empty($pattern)) {
	 		$rpattern='/'.$encl.'([^'.substr($encl,1).']*)'.$encl.'/ium'; 
	 	}
	 	
	 	if ($callback) {
	 	 	$out=preg_replace_callback($rpattern,$callback,$out);
	 	 	return $out;
	 	}
	 	else {
	 	 	$out=preg_replace($rpattern,$replace,$out);
	 	 	return $out;
	 	}
	 	
	 	return $out;
	 	
	 
	}
	
	
	static function parseHTMLTextAsHTML($text) {
	 	return $text;
	}
	
	
	
	static function parsePlainTextAsHTML($text,$nl2br=TRUE) {
	 
		 
		 	$out="\n".$text."\n\n";
		 	
		 	$out=str_replace('&#34;','"',$out);
		 	
		 	$out=str_replace("\r",'',$out);
			$out=str_replace("\n\n","\r\n\n",$out);
		 		
		 	// Hyperlinks	
		 	$pattern = "/([\s]|[\n])www.([^\s]*)/i";
			$replacement = "$1http://www.$2";
			$out=preg_replace($pattern,$replacement,$out);
	
			$pattern = "/([\s]|[\n])http:\/\/([^\s]*)/i";
			//$replacement = "$1<a href=\"http://$2\" target=\"_blank\" class=\"_ext\">$2</a>";
			$replacement = "$1<a href=\"http://$2\" class=\"_ext\">$2</a>";
			
			$out=preg_replace($pattern,$replacement,$out);
			
	
			// E-Mail-Adressen		
			$pattern = "/([\s|\n]+)([A-Z0-9\.\_\-]*\@[A-Z0-9\.\_\-]*)([\s|\n|\,]+)/i";
			$replacement = "$1<a href=\"mailto:\$2\" class=\"mailto\">$2</a>$3";
			$out=preg_replace($pattern,$replacement,$out);
			
			
			
			// Fussnoten
			$pattern="/\[([0-9]*)\]/";
			$replacement="<sup><a name=\"FNA_$1\" class=\"fnmarkup\" href=\"#FN_$1\">[$1]</a></sup>";
			$out=preg_replace($pattern,$replacement,$out);
			
	
			// Headlines 
			/** deprecated since PHP 5.6
			$pattern = "/(={1,5}) ([^\=]*) \\1/ie";
			$replacement = "<h".strlen("$1").">$2</h".strlen("$1").">";
			
			$replacement = "'<h'.strlen('\\1').' class=\"\ihdg\">\\2</h'.strlen('\\1').'>'";
			$out=preg_replace($pattern,$replacement,$out);
			**/
			
			$pattern = "/(={1,5}) ([^\=]*) \\1/i";
			$out=preg_replace_callback($pattern,array('TextParser','renderHeadline'),$out);
			//---

			
			// mehrzeilige CRLF nach Überschriften entfernen
			$pattern="/(<\/h[0-9]>)([\n|\s]*)/i";
			$out=preg_replace($pattern,"$1\r",$out);
	
			
			// underline, italic, bold (wikistyle)
			$pattern = "/(\_\_)([^\_]*)(\_\_)/i";
			$replacement = "<u>$2</u>";
			$out=preg_replace($pattern,$replacement,$out);
	
			$out=str_replace('&#39;',"'",$out);
			
			$pattern = "/(\'\'\')([^\']*)(\'\'\')/i";
			$replacement = "<b>$2</b>";
			$out=preg_replace($pattern,$replacement,$out);
			
			$pattern = "/(\'\')([^\']*)(\'\')/i";
			$replacement = "<i>$2</i>";
			$out=preg_replace($pattern,$replacement,$out);
			
			
			// Listen
	
		
			$out=preg_replace("/([\n|\r])([\t|\s]*)([\-|\*|\#]+) /","<rwp:li>",$out);
		
			$pattern='/(<rwp:li>)([^\r]*)([\r])/';
			$replace="\r<rwp:ul>$1$2</rwp:ul>\r";
			$out=preg_replace($pattern,$replace,$out);
			
			$out=str_replace("<rwp:li>","</rwp:li><rwp:li>",$out);
			$out=str_replace("</rwp:ul>","</rwp:li></rwp:ul>",$out);
			$out=str_replace("<rwp:ul></rwp:li>","<rwp:ul>",$out);
			$out=str_replace("<rwp:","\r<rwp:",$out);
			$out=str_replace("\r<rwp:li>","\r\t<rwp:li>",$out);
			$out=preg_replace("/(<\/rwp\:)([^>]*)>/","$1$2>\r",$out);
			
			// cut rwpNamespace
			$out=str_replace("<rwp:","<",$out);
			$out=str_replace("</rwp:","</",$out);
			

			// nl2br
			if ($nl2br) {
				$out=str_replace("\n","\n<br/>",trim($out));
			}
			$out=str_replace("\r","\n",$out);
	
			$out=str_replace("<crlf/>","\n",$out);
		
			
			$out=stripslashes($out);
			$out=str_replace("'",'&#39;',$out);
			$out=stripslashes($out);
	
			$out=TextParser::markupImages($out);
			
			
			return ($out); 
	 
	 
	}
	
	static function renderHeadline($matches) {
		$level = strlen($matches[1]);
		$tag = 'h'.$level;
		$text = $matches[2];
		$out = '<'.$tag.'>'.$text.'</'.$tag.'>';
		return $out;


	}
	
	static function parseFNotes($string) {
	 	$pattern="/\[([0-9]*)\]/";
		$replace="<sup><a name=\"FN_$1\" class=\"fnanchor\" href=\"#FNA_$1\">$1</a></sup>";
		$out=preg_replace($pattern,$replace,$string);
		return $out;
	 
	}
	
	
	static function markupImages($text) {
	 
	 	$matches=array();
	 	preg_match_all('/<img([^\>]*)>/im',$text,$matches);
	 	for ($m=0;$m<count($matches[0]);$m++) {
				$orgImgTag=$matches[0][$m];
				$newImgTag=$orgImgTag;
				OverrideUtil::callHooks(__CLASS__,__METHOD__,$this,$newImgTag);
				
				
				if ($newImgTag!=$orgImgTag) {
				 	$text=str_replace($orgImgTag,$newImgTag,$text);
				}
		}
		
		return $text;
	}
	
	
	static function getCut($string,$tolength=255,$args=array()) {
	 	
	 	$tolength	= intval($tolength);
	 		 	
		$out		= $string;
	 	$out		= strip_tags($out);
	
	 	$fromlength	= strlen($out);
	 	
	 	if ($fromlength<=$tolength) {
	 	 	return $out; 	
	 	}
	
		// CUT:
	
		// boundaries to avoid hard cuts inside a word

	 	$boundaries	= array("\n",'.','?','!',':',' ');
	 	$boundaries	= ArrayUtil::getValue($args,'boudaries',$boundaries);
	 	
		// replacement for the cut edges (to place before/after the cut)		 	
	 	$cutedge 	= ArrayUtil::getValue($args,'cutedge',' ... ');
	
	 	// available cutting modes 
	 	//			'M' 	: out of the middle (keeps left and right half-cuts)
	 	// 			'R'		: from right
	 	//			default	: from left
	 	
	 	$mode		= ArrayUtil::getValue($args,'mode','');
	 	
	 	if ($mode == 'M') {		
	 	 	$callArgs			= $args;
	 	 	$callArgs['cutedge']= '';	
	 	 	$callArgs['mode']	= '';
		 	$left	= TextParser::getCut($string,($tolength/2),$callArgs);
		 	$callArgs['mode']	= 'R';
		 	$right	= TextParser::getCut($string,($tolength/2),$callArgs);
		 	
		 	$out=$left.$cutedge.$right;
		 	return $out;
		}
	

	
	 	$cutlength		= $tolength;
	 	
	 	$cut			= ($mode=='R') ?  substr($out,0-$cutlength) : substr($out,0,$cutlength);
	 	$tolerance		= intval($tolength/10);
	 	$tolerance		= ($tolerance<10) ? 10 : $tolerance;
	 	
	 	$cutextend		= ($mode=='R') ? -1 : 1;
	 	
	 	foreach ($boundaries as $delim) {
	 	 	$cutlength=($mode=='R') ? ($tolength-strpos($cut,$delim)) : strrpos($cut,$delim);
	 	 	if ($tolength-$cutlength<$tolerance) {	 
			   $cutlength=$cutlength+$cutextend;    
			   break; 
			}
	 	}
	 	
	 	$out= ($mode=='R') ? substr($cut,0-$cutlength) : substr($cut,0,$cutlength);
	 	
	 	if (strlen($out)<$fromlength) {
	 	 	$out= ($mode=='R') ? $cutedge.$out : $out.$cutedge;
	 	} 
	 	
	 	return $out;
	 
	 
	}
	
	
	static function parseWikiLinks($string) {
	 
	 	$out=$string;
	 	
	 	
	 	$pattern='/\[(http)([^\s|^\]]*)[\s]([^\]]*)\]/i';
	 	$replace=" <a class=\"wikilink\" target=\"_blank\" href=\"$1$2\">$3</a>";
	 	$out=preg_replace($pattern,$replace,$out);
	 	
	 	$pattern='/\[(http)([^\]]*)\]/i';
	 	$replace=" <a class=\"wikilink\" target=\"_blank\" href=\"$1$2\">$1$2</a>";
	 	$out=preg_replace($pattern,$replace,$out);

		$pattern='/\[\[([^\]|\|]*)\|([^\]]*)\]\]/i';
	 	$replace="<a class=\"wiki\" href=\"?wiki=$1\">$2</a>";
	 	$out=preg_replace($pattern,$replace,$out);

	 	$pattern='/\[\[([^\]]*)\]\]/i';
	 	$replace="<a class=\"wiki\" href=\"?wiki=$1\">$1</a>";
	 	$out=preg_replace($pattern,$replace,$out);
	 	
	 	return $out;
	 
	}
	
	
	
	static function parseCSV($string,$sep=';',$encl='"',$esc='\\') {
   		$data=array();
   		$string=trim($string);
   		
   		// replace inner or invalid linebreaks
   		
   		$pattern="/([\n])([\w|\s|\'|\-])/";
   		$string=preg_replace($pattern,"\r$2",$string);
   		
		$string=str_replace("\n".$encl.$sep,"\r".$encl.$sep,$string);
   		
		//$pattern="/([\n])([\\'.$encl.']*)([^\\'.$sep.'])/";
   		//$string=preg_replace($pattern,"\r$2$3",$string);
   		
   		
   		// define replace markers
   		$_e1	= '<encl/>'; 	// single enclosure
	 	$_e2	= '<dblq/>'; 	// double enclosure
	 	$_s1	= '<sep/>';  	// separator
	 	$_q1	= '<esq/>';		// escaped enclosure
   		
   		
   		$rows=explode("\n",$string);
   		
		if (function_exists('str_getcsv')) {
   		 	foreach ($rows as $row) {
				$cols =str_getcsv($row,$sep,$encl,$esc);
   		 	 	$data[]=$cols;
   		 	}
   		}
   		else {
   			foreach ($rows as $row) {
   			 
   			 	$row=str_replace($esc.$encl		, $_q1				,$row);		// mark escaped
   			 	
				$row=str_replace($encl.$encl	, $_e2				,$row);  	// mark dblquotes
				$row=str_replace($encl			, $_e1				,$row);		// mark singlequotes	
				$row=str_replace($_e2.$sep		, $_e1.$_e1.$_s1	,$row); 	// dblq+sep maybe empty cell
				$row=str_replace($sep.$_e2		, $_s1.$_e1.$_e1	,$row); 	// sep+dblq maybe empty cell
				
				// mark separators
						 	
   			 	$row=str_replace($_e1.$sep		, $_e1.$_s1			,$row);
   			 	$row=str_replace($sep.$_e1		, $_s1.$_e1			,$row);
   			 	$row=str_replace($sep.$sep		, $_s1.$_s1			,$row);
   			 	$row=str_replace($_s1.$sep		, $_s1.$_s1			,$row);
   			 	$row=str_replace($sep.$_s1		, $_s1.$_s1			,$row);
			
				// any encl triples left?			
   				//$row=str_replace($_e1.$_e1		, $_s1.$_s1			,$row);	
   			 	

				$cols =explode($_s1,$row); 
   		 	 	foreach ($cols as $index=>$value) {
   		 	 	 	$csvval=$value;
						   
					$csvval=str_replace($_e1,'',$csvval);
					$csvval=str_replace($_e2,$encl,$csvval); // remap dblquotes
					$csvval=str_replace($_q1,$encl,$csvval); // remap escaped
					
					$csvval=trim($csvval);	  	
   		 	 	 
					$cols[$index]=$csvval;
				}
				$data[]=$cols;
   		 	}
				 	
  	
   		}
   		
   		
   		return $data;
	  
	}
	
 
 
}

?>