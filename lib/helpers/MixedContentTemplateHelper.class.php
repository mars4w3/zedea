<?php


class MixedContentTemplateHelper {
 
 
 	static function extractImage($item) {
 	 
 	 	$img='';
 	 	
 	 	$articleText=MixedContentTemplateHelper::clean($item['itemtext']);
 	
	
		preg_match_all('/<img([^\>]*)>/im',$articleText,$matches);
		if (isset($matches[0][0])) {
			$img=$matches[0][0];
		}

		
		$img=preg_replace('/(width|height|style)=\"([^\"]*)\"/im','',$img);
		
		$img=str_replace('style','title',$img);
		
		OverrideUtil::callHooks(__CLASS__,__METHOD__,null,$img);
		 	 	
 	 	return $img;
 	 
 	}

	static function stripImageAttr($img) {
	 	$img=preg_replace('/(width|height|style)=\"([^\"]*)\"/im','',$img);
	 	return $img;
	}


	static function formatArticle($articleText='',$asArray=FALSE) {
	 
	 	$out=array();
	 	$articleText=MixedContentTemplateHelper::clean($articleText);
	 	
	 	if ($asArray) {
	 		$out=MixedContentTemplateHelper::getImageContainer($articleText,TRUE);
	 		$articleText=$out['itemtext'];
	 	}
	 	else {
	 	 	$articleText=MixedContentTemplateHelper::getImageContainer($articleText);
	 	}
		
		$articleText=MixedContentTemplateHelper::formatHeadings($articleText);
	 	$articleText=MixedContentTemplateHelper::trimArticle($articleText);
	 	

		if ($asArray) {
		 	$out['itemtext']=$articleText;
		 	$out['images']=$out['images'];
		 	return $out;
		 	
		}
	 	
	 	return $articleText;
	 
	}


	static function trimArticle($articleText='') {
	 
	 	$articleText=MixedContentTemplateHelper::clean($articleText);
	 	$out=$articleText;
	 	
	 	$useles_elem=array('<p></p>','<p>&nbsp;</p>');
	 	$out=str_replace($useles_elem,"\n",$out);
	 	
	 	$out=trim($out);
	 	
	 	return $out;
	 
	}




	static function formatHeadings($articleText='') {
	 	$articleText=MixedContentTemplateHelper::clean($articleText);
	 	$out=$articleText;
	 	
	 	$headings=array();
	 	preg_match_all('/<h(\d)(.*)<\/h(\d)>/mui',$out,$headings);
	 	
	 	for ($h=0;$h<count($headings[0]);$h++) {
	 	 
	 	 	$hdg=$headings[0][$h];
	 	 	$hdg_replace=strip_tags($hdg,'<h1><h2><h3><h4><h5><a><br>');
	 	 	$out=str_replace($hdg,$hdg_replace,$out);
	 	 
	 	}
	 	
	 	return $out;
	 	
	 
	}


	static function pregEscape($string) {
	 
	 	$out=str_replace(
	 		array('(',')','?'),
	 		array('\\(','\\)','\\?'),
	 		$string)
			;
		return $out;	
	 
	}


	static function getImageContainer($articleText='',$asArray=FALSE) {
	 
		//highlight_string($articleText);
		$articleText=MixedContentTemplateHelper::clean($articleText);
		$imageContainer='';
		$placeHolder='[template-imagecontainer]';
		
		
		preg_match_all('/<img([^\>]*)>/im',$articleText,$matches);
		//var_dump($matches);
		
		$imgCount=count($matches[0]);
		
		switch ($imgCount) {
		 
			case 0 : 
					$imageContainer='';  break;	
			 
			case 1:  
			 		$imageContainer='<div class="article-image">';
			 		$placeHolder='[template-imagecontainer]';
			 		preg_match('/src="([^\"]*)"/i',$matches[0][0],$src);
					$imageContainer.='<a href="'.$src[1].'" rel="lightbox" class="zlb" title="my caption">';
					$imageContainer.=MixedContentTemplateHelper::stripImageAttr($matches[0][0]);
					$imageContainer.='</a>';
					$articleText=preg_replace('#'.MixedContentTemplateHelper::pregEscape($matches[0][0]).'#',$placeHolder,$articleText,1);
					$imageContainer.='</div>';
					break;
							
			
			default: 
			
					$imageContainer='<div class="article-images" id="article-images-container">';
					$imageContainer.='<ul>';
					$placeHolder='[template-imagecontainer]';
					
					for ($m=0;$m<count($matches[0]);$m++) {
						$replace=($m==0) ? $placeHolder : ''; 
						//$imageContainer.='<li id="article-image-'.$m.'">'.$matches[0][$m].'</li>';
						
						$imageContainer.='<li id="article-image-'.$m.'">';
						preg_match('/src="([^\"]*)"/i',$matches[0][$m],$src);
						preg_match('/title="([^\"]*)"/i',$matches[0][$m],$capt);
						$caption=(isset($capt[1])) ? $capt[1] : '';
						$imageContainer.='<a href="'.$src[1].'" rel="lightbox" class="zlb" title="'.$caption.'">';
						$imageContainer.=MixedContentTemplateHelper::stripImageAttr($matches[0][$m]);
						$imageContainer.='</a>';
						$imageContainer.='</li>';
						
						//$articleText=str_replace($matches[0][$m],$replace,$articleText); 
						$articleText=preg_replace('#'.$matches[0][$m].'#',$replace,$articleText,1);
					 
					}
					$imageContainer.='</ul>';
					$imageContainer.='<div class="eos"></div>';					
					
					$imageContainer.='</div>';	
					
					
					/*
					
					if ($imgCount>3) {
						$imageContainer.=	'<ul class="element-pager gallery-pager">';
						$imageContainer.=   '<li class="first"><a class="button-prev" onclick="icImgSlider.prev();">zur&uuml;ck</a></li>';
						$imageContainer.=   '<li class="last"><a class="button-next" onclick="icImgSlider.next();">weiter</a></li>';
						$imageContainer.=   '</ul>';
						$imageContainer.=	'<div class="eos"></div>';
					}
					*/
					
					break;
	
		}
	
	
		if ($asArray) {
		 	$out['itemtext']=str_replace($placeHolder,'',$articleText);
		 	$out['images']=$imageContainer;
		 	return $out;
		}
		
		
		if (!empty($imageContainer)) {
			$articleText=str_replace($placeHolder,$imageContainer,$articleText);
		}
		
		return $articleText;
	 
	 
	 
	}






	static function extractTitle($item) {
 	 
 	 	//$title=$item['itemtitle'];
 	 	
 	 	//$articleText=$item['itemtext'];
 	 	
 	 	
 	 	$title			= ArrayUtil::getValue($item,'itemtitle','');
 	 	$articleText	= ArrayUtil::getValue($item,'itemtext',FALSE);
 	 	
 	 	if ($articleText) {
 	 		$articleText=MixedContentTemplateHelper::clean($articleText); 		 	
			preg_match_all('/<h1([^\>]*)>(.*)<\/h1>/im',$articleText,$matches);
			//var_dump($matches);
			if (isset($matches[2][0])) {
				$title='<h1>'.$matches[2][0].'</h1>';
			}
			$title=strip_tags($title,'<h1><br>');
		}
	
		 	 	
 	 	return $title;
 	 
 	}

	static function getTeaser($item,$asArray=FALSE) {
	 

	 	$isCategory=FALSE;
	 	$out='';
	 	$text='';
	 
	 	$text=ArrayUtil::getValue($item,'itemtext','');
	 	$text=MixedContentTemplateHelper::clean($text);
	 	 	
	 	
	 	$teaserImgWidth=200;
	 	$teaserTextLength=255;
	 	$linkMoreText='__MORE__'.
	 	
	 	$link='?item='.ArrayUtil::getValue($item,'id','');
	 	//var_dump($link);
	 	
	

	 	$teaserTitle=MixedContentTemplateHelper::extractTitle($item);
	 	$teaserImg=MixedContentTemplateHelper::extractImage($item);
	 	
	 	$text=str_replace($teaserTitle,'',$text);	
	 	
	 	$text=MixedContentTemplateHelper::findTeaserSection($text);
	 	
	 	$text=strip_tags($text,'<br><h1><h2><h3><hr>');
	 	$text=strip_tags($text,'<br><hr>');
	 	$text=MixedContentTemplateHelper::cutText($text,$teaserTextLength);
	 	
	 
	 	$teaserTitle=strip_tags($teaserTitle,'<br>');
	 	//$teaserTitle=strip_tags($teaserTitle,'<br><h1>');
	 	//$teaserTitle=str_replace('h1>','h3>',$teaserTitle);
	 	
	 	
	 	if ($asArray) {
	 	 	$arrayOut=array(
	 	 		'teaserTitle'	=>	$teaserTitle,
	 	 		'teaserImage'	=>	strip_tags($teaserImg,'<img>'),
	 	 		'teaserText'	=>	$text,
	 	 		'teaserLink'	=>  $link,
			);	
	 	 	return $arrayOut;
	 	}
	 	
	 	
	 	$out='<div class="item-teaser">';
	 	
		if (!empty($teaserImg)) {
	 		$out.='<div class="item-teaser-img">';
	 		$out.='<a href="'.$link.'">'.$teaserImg.'</a>';
	 		$out.='</div>';
	 	}
	 	
		$out.='<div class="item-teaser-text">';
	 	
		if (!empty($teaserTitle)) {
	 		$out.='<a href="'.$link.'">'.$teaserTitle.'</a>';
	 	}
	 	
	 	$out.=$text;
	 	//$out.='<div class="read-more"><a class="link-read-more" href="'.$link.'">'.$linkMoreText.'</a></div>';
	 	$out.='</div>';
	 	$out.='<div class="eos"></div>';
	 	$out.='</div>';
	 	
	 	

	 	
	 	return $out;
	 
	 
	}
	
	
	static function findTeaserSection($text,$replace=FALSE) {
	 	$text=MixedContentTemplateHelper::clean($text);
	 	$out=$text;
	 	if (!strstr($out,'ExklusiverTeaserText') && !strstr($out,'InklusiverTeaserText')) {
	 	 	return $out;
	 	}
	 	$sections=array();
	 	if (strstr($out,'ExklusiverTeaserText')) {
	 	 	preg_match_all('/<(div|p) class=\"ExklusiverTeaserText\">(.*)<\/\1>/mu',$out,$sections);
	 	 	if (!empty($sections))	{
			 	$out=implode(' ',$sections[2]);  
			 	return $out;
			} 
	 	 
	 	}
		if (strstr($out,'InklusiverTeaserText')) {
	 	 	preg_match_all('/<(div|p) class=\"InklusiverTeaserText\">(.*)<\/\1>/mu',$out,$sections);
	 	 	if (!empty($sections))	{
			 	$out=implode(' ',$sections[2]);  
			 	return $out;
			} 
	 	 
	 	}
	
		return $out;
	 
	}
	
	
	static function cutText($string='',$maxlength=255) {
	 
	 	$out=$string;
	 	if (strlen($out)<$maxlength) {
		 	return $out; 
		} 
		
		$out=substr($out,0,$maxlength);
		
		$offset=$maxlength;
		$offsets=array(
			strrpos($out,'.'),
			strrpos($out,"\n"),
			strrpos($out,' '),
			strrpos($out,'<h'),
		);
		
		$offset=($offsets[2]>$offset*0.9) ? $offsets[2] : $offset;
		$offset=($offsets[1]>$offset*0.9) ? $offsets[1] : $offset;
		$offset=($offsets[0]>$offset*0.9) ? $offsets[0] : $offset;
		
		// clip before heading
		$offset=($offsets[3]>$maxlength*0.6) ? $offsets[3] : $offset;
	 
	 	//var_dump($offsets);
	 	//var_dump($offset);
	 	
	 	$out=substr($out,0,$offset);
	 	//var_dump($out);
	 
	 	return $out;	
	 	
	 	
	 
	}
	
	
	static function stripImages($articleText) {
	 	$articleText=MixedContentTemplateHelper::clean($articleText);
	 	$out=$articleText;
	 	$out=preg_replace('/<img([^>]*)>/','',$out);
	 	return $out;
	 
	}
	
	
	static function clean($string) {
	 	$out=stripslashes($string);
	 	return trim($out);
	 
	}
  
 
 
}

?>