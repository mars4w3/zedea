<?php


class DateTimeUtil {
 
 
	static function timeMilli($time) {
	 	list($millisec,$timesec)=explode(" ",$time);
	 	return strftime('%H:%M:%S '.$millisec,$timesec);
	 
	} 
	
	static function timeMilliSec($time) {
	 	list($millisec,$timesec)=explode(" ",$time);
	 	return doubleval($timesec+$millisec);
	 
	} 
	
	
	
	static function formatGivenDate($input,$format='%Y-%m-%d',$default='',$real=TRUE) {
	
		$out=$default;	
	
		if (empty($input) || $input===0 || $input==='' || $input===FALSE || strstr($input,'00-00')) {
		 	return $out;
		} 
		
		
		
		$datakeys=array('m','d','Y','H','M','S');
		$in=array();
		
		$patterns=array(
			'mysql--datetime'=>array('/([\d]+)-([\d]+)-([\d]+) ([\d]+):([\d]+):([\d]+)/', array('raw','Y','m','d','H','M','S')),
			'mysql--date'=>array('/([\d]+)-([\d]+)-([\d]+)/', array('raw','Y','m','d')),
			
			'de_de--date'=>array('/([\d]+).([\d]+).([\d]+)/', array('raw','d','m','Y')),
			'en_en--date'=>array('/([\d]+)\/([\d]+)\/([\d]+)/', array('raw','m','d','Y')),
			
		
		);
		
		
		if (is_int($input)) {
		 	$timestamp=$input;
		}
		else {
		
			foreach ($datakeys as $key) {
			 	$in[$key]=0;
			}
		
			if (is_array($input)) {
			 	foreach ($datakeys as $key) {
			 	 	$in[$key]=ArrayUtil::getValue($input,$key,0);
			 	}
			}
			else {
			 	foreach($patterns as $type=>$decl) {
			 	 	$pattern=$decl[0];
			 	 	$map=$decl[1];
			 	 	$matches=array();
			 	 	if (preg_match($pattern,$input,$matches)) {
			 	 	 	
			 	 	 	for ($m=0;$m<count($map);$m++) {
			 	 	 	 	$in[($map[$m])]=$matches[$m];
			 	 	 	}
			 	 	 	break;
			 	 	 	
			 	 	}
			 	}
			 
			}
			
			//$timestamp=mktime($in['H'],$in['M'],$in['S'],$in['m'],$in['d'],$in['Y'],$in['dst']);
			$timestamp=mktime($in['H'],$in['M'],$in['S'],$in['m'],$in['d'],$in['Y'],-1);
		}
		
		if ($format=='TIMESTAMP') {
		 	return $timestamp;
		}
		
		
		$month=intval(date('m',$timestamp));
		$day=intval(date('d',$timestamp));
		$year=intval(date('Y',$timestamp));
		$hour=intval(date('H',$timestamp));
		$min=intval(date('M',$timestamp));
		$sec=intval(date('S',$timestamp));
	 	
	 	
	 	$out=strftime($format,$timestamp);
	 	

	 	
	 	if ($real) {
	 	 	if (checkdate($month,$day,$year)) {
				return $out; 	 	 
	 	 	}
	 	 	else {
	 	 	 	return $default;
	 	 	}
	 	 
	 	}
	 	
	 	return $out;
	 
	 
	 }
	 
	 
	static function getTimeDiff($from='',$to='',$interval='') {
	
		if (empty($to)) {
		 	$to=time();
		}
		$fromTime=DateTimeUtil::formatGivenDate($from,'TIMESTAMP'); 
		$toTime=DateTimeUtil::formatGivenDate($to,'TIMESTAMP');
		
		$sec=$toTime-$fromTime;
		
		$min=intval($sec/60);
		$hours=intval($min/60);
		$days=intval($hours/24);
		$months=intval($days/30);
		$years=intval($days/365);
		
		$out=($sec>0) ? Babel::_('DateTime::BEFORE','','DateTime') : Babel::_('DateTime::IN','','DateTime');
		$out.=' ';
		
		switch ($interval) {
		 	case 'S' : $out.=$sec.(Babel::_('DateTime::SECONDS','','DateTime')); break;
		 	case 'M' : $out.=$min.(Babel::_('DateTime::MINUTES','','DateTime')); break;
		 	case 'H' : $out.=$hours.(Babel::_('DateTime::HOURS','','DateTime')); break;
		 	case 'd' : $out.=$days.(Babel::_('DateTime::DAYS','','DateTime')); break;
		 	case 'm' : $out.=$months.(Babel::_('DateTime::MONTHS','','DateTime')); break;
		 	
		 	default: 
		 			$out.=($years>1) ? $years.' '.(Babel::_('DateTime::YEARS','','DateTime')).', ' : '';
		 			$out.=($years==1) ? $years.' '.(Babel::_('DateTime::YEAR','','DateTime')).', ' : '';
		 			$out.=($days>1) ? ($days%365).' '.(Babel::_('DateTime::DAYS','','DateTime')).', ' : '';
		 			$out.=($days==1) ? ($days%365).' '.(Babel::_('DateTime::DAY','','DateTime')).', ' : '';
		 			$out.=($hours>1) ? ($hours%24).' '.(Babel::_('DateTime::HOURS','','DateTime')).', ' : '';
		 			$out.=($hours==1) ? ($hours%24).' '.(Babel::_('DateTime::HOUR','','DateTime')).', ' : '';
		 			$out.=($min>1) ? ($min%60).' '.(Babel::_('DateTime::MINUTES','','DateTime')).', ' : '';
		 			$out.=($min==1) ? ($min%60).' '.(Babel::_('DateTime::MINUTE','','DateTime')).', ' : '';
		 			$out.=($sec>0) ? ($sec%60).' '.(Babel::_('DateTime::SECONDS','','DateTime')).' ' : '';
		}	
		
		return $out;
			 
	  
	}
 
 
 	static function getDateInfo($time) {
	 
	 	$wd=intval(date('w',$time));
	 	$w=($wd==0) ? 7 : $wd;
	 
	 	$out=array(
			'd'=>intval(date('d',$time)),		
			'm'=>intval(date('m',$time)),
			'Y'=>intval(date('Y',$time)),
			'w'=>$w,	
			't'=>$time,
	 	);
	 	return $out;
	 
	}
 
 	static function getFirstOfMonth($month=0,$year=0) {
 	 
 	 	$month=($month==0) ? intval(date('m')) : $month;
	 	$year=($year==0) ? intval(date('Y')) : $year;
		$time=mktime(0,0,0,
					intval($month),
					intval(1),
					intval($year));
		
	 	
	 	return DateTimeUtil::getDateInfo($time);
	}
	
	static function getLastOfMonth($month=0,$year=0) {
	 
	 	$month=($month==0) ? intval(date('m')) : $month;
	 	$year=($year==0) ? intval(date('Y')) : $year;
	
		$time=mktime(0,0,0,
					intval(intval($month)+1),
					intval(0),
					intval($year));
		
	 	
	 	return DateTimeUtil::getDateInfo($time);
	}
	
	
	static function getMonthMap($month=0,$year=0,$startMonday=FALSE) {

		$colIndex=($startMonday) ?  array(1,2,3,4,5,6,0) : array(0,1,2,3,4,5,6);
		$map=array();
		
		$last=DateTimeUtil::getLastOfMonth($month,$year);
		$end=$last['d'];
		$first=DateTimeUtil::getFirstOfMonth($month,$year);
		$offset=intval($first['w']);
		$offset=($startMonday) ? $offset-1 : $offset;
		
		$cy=intval($first['Y']);		// current year
		$py=$cy;						// prev year
		$ny=$cy;						// next year

		$cm=intval($first['m']);		// current month	
		$pm=$cm-1;						// prev month
		if ($pm<1) { $pm=12; $py=$cy-1; }
		
		$nm=$cm+1;						// next month
		if ($nm>12) { $nm=1; $ny=$cy+1; }
		
		$pt=mktime(0,0,0,$pm,1,$py);
		$nt=mktime(0,0,0,$nm,1,$ny);
		
		$row=array();
		$col=$offset;
		
		for ($p=0;$p<$col;$p++) {
		 	$row[($colIndex[$p])]='';
		}
		
		for ($d=1;$d<=$end;$d++) {
		 	if (($col>6)) {
		 	 	$map[]=$row;
			 	$row=array();
			 	$col=0;
			}
			$row[($colIndex[$col])]=''.$d;
			$col++;
		}
		for ($p=$col;$p<7;$p++) {
		 	$row[($colIndex[$p])]='';
		}
		$map[]=$row;
		return $map;
	}
	
	static function translateDatePart($value=0,$format='',$isTimeStamp=TRUE) {
	 
	 	// for details see the format list at
	 	// http://php.net/manual/de/function.date.php
	 
	 	switch ($format) {
	 	 	case 	'D'		:	// short weekday
			  					$val=($isTimeStamp) ? date('w',$value) : $value;  
			  					return DateTimeUtil::translateDayOfWeek($val,TRUE);
			case 	'l'		:	// full  weekday
			  					$val=($isTimeStamp) ? date('w',$value) : $value;  
			  					return DateTimeUtil::translateDayOfWeek($val);
			case 	'M'		:	// short monthname
			  					$val=($isTimeStamp) ? date('m',$value) : $value;  
			  					return DateTimeUtil::translateMonth($val,TRUE);
			case 	'F'		:	// full monthname
			  					$val=($isTimeStamp) ? date('m',$value) : $value;  
			  					return DateTimeUtil::translateMonth($val,TRUE);
	 	 
	 	 	default			:  
			  					return strftime('%'.$format,$value);
	 	}
	 	
	 	return $value;
	 
	}
	
	
	static function translateDayOfWeek($wd,$short=FALSE) {
	
	 	$wdays=array('SUN','MON','TUE','WED','THU','FRI','SAT');
	 
	 	$babelIndex='DateTime::';
	 	$babelIndex.=($short) ? 'Short' : 'Full';
	 	$babelIndex.='WeekDay';
		$babelIndex.=' '.$wdays[intval($wd)]; 
		return Babel::_($babelIndex,'','DateTime');   
	 
	}
	
	static function translateMonth($m,$short=FALSE) {
	 
	 	$month=array('','JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
	 
	 	$babelIndex='DateTime::';
	 	$babelIndex.=($short) ? 'Short' : 'Full';
	 	$babelIndex.='MonthName';
		$babelIndex.=' '.$month[intval($m)]; 
		return Babel::_($babelIndex,'','DateTime');   
	 
	}
	
	
 
}


?>