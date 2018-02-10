<?php

class MySQLDriver extends DBDriver {
 
	var $Environment	= null;
 
 	function connect() {
 	 
 	 	if (empty($this->Host)) {
 	 	 	ErrorHandler::err();
 	 	 	return FALSE;
 	 	}
 	 
 	 	$server=$this->Host;
 	 	if (!empty($this->Port)) {
 	 	 	$server.=':'.$this->Port;
 	 	}
 	 	$username=$this->User;
 	 	$password=$this->Pass;
 	 	
 	 	$database=$this->Database;
 	 
 	 	if ($con=mysqli_connect($server,$username,$password)) {
 	 	 	if (!empty($database)) {
 	 	 	 	mysqli_select_db($con,$database) or ErrorHandler::err(1,mysqli_error($con),E_NOTICE); 
 	 	 	 	$this->Connection= $con;
 	 	 	 	return TRUE;
 	 	 	}
 	 	 	$this->Connection =$con;
 	 	 	return TRUE;
 	 	}
 	 
 	 	return FALSE;
 	 
 	}
 
 
 	function query($sql) {

 	 	$res=mysqli_query($this->Connection,$sql);
 	 	//var_dump($sql);
 	 	//$res=mysqli_unbuffered_query($sql,$this->Connection);
 	 	if (!$res) {
 	 	 	$msg=$sql.' '.mysqli_error($this->Connection);
 	 	 	ErrorHandler::err(123456,$msg,E_WARNING);
 	 	 	return FALSE;
 	 	}
 	 
 	 	return $res;
 	 
 	}
 	
 	
 	function getResult($result=0) {
 	 
 	 	if (!$result) {
 	 	 	return FALSE;
 	 	}
 	 	if ($result===TRUE) {
 	 	 	return array();
 	 	}
 	 
 	 	$out=array();
 	 	while ($data=mysqli_fetch_array($result,MYSQLI_ASSOC)) {
 	 	 	$out[]=$data;
 	 	 	
 	 	}
 	 	return $out;
 	 
 	}
 	
 	
 	function escape($string) {
 	 	if (function_exists('mysqli_real_escape_string')) {
	 		return mysqli_real_escape_string($string); 
	 	}
	 	$out=$string;
	 	$out=addslashes($string);
	 	return $out;
	} 
 

 	function getInsertId($data=array(),$dbtable='') {
	  
	 	return mysqli_insert_id($this->Connection); 
	} 


	function getLastError() {
	 	return mysqli_error($this->Connection);
	}
	
	
	
		
	function exportDumpToFile($dbtable='',$options='') {
	 
	 	$host=$this->Host;
	 	$user=$this->User;
	 	$pass=$this->Pass;
	 
	 	$dbname=$this->Database;
	 	
	 	$timestamp=strftime('%Y%m%d-%H%M');
	 	
	 	$dumpwhat=$dbname;
	 	$outfileName=$dbname.'_'.$timestamp.'.sql';
	 	
	 	if (!empty($dbtable)) {
	 	 	$dumpwhat.=' '.$dbtable;
	 	 	$outfileName=$dbname.'.'.$dbtable.'_'.$timestamp.'.sql';	
	 	}
	 	
	 	$outpath=Loader::getPath('',TRUE);
	 	$outfile=FileUtil::fullpath($outpath).'/'.$outfileName;
	 	
	 	$mysqldo='mysqldump';
	 	if (stristr($this->getEnv('OS'),'win')) {
	 		$mysqldo=$this->getEnv('BinPath').'/'.$mysqldo.'.exe'; 	
	 	}
	 	
	 	$cmd=$mysqldo.' --host='.$host.' --user='.$user.' --password='.$pass .' '.$options.' '.$dumpwhat.' > '.$outfile; 
	 	
	 	$response=array();
	 	$result=0;
		exec($cmd,$response,$result);
	 	
	 	$out = array(
	 		'command' 	=> $cmd,
	 		'response'	=> $response,
	 		'result'	=> $result,
	 		'outfile'	=> $outfile,
	 		);
	 	return $out;
	 	
	}

	function importDumpFromFile() {
	 
	 
	}


	function backupTable($dbtable) {
	 	$newTableName = $dbtable.'_'.time().'_bak';
	 	$sql='CREATE TABLE '.$newTableName.' SELECT * FROM '.$dbtable;
	 	$res=$this->execute($sql);
	 	return $res;
	 
	}

	function getEnvironment() {
	 	if (!$this->Environment) {
	
			$this->Environment=array(
				'BasePath' => '',
				'BinPath' => '',
				'OS' => '',
			
			); 
	 		$sql='SELECT @@basedir';
	 		$data=$this->getResult($this->execute($sql));
	 		$basedir=ArrayUtil::getValue($data[0],'@@basedir',FALSE);
	 	
		 	$this->Environment['BasePath'] 	= $basedir;
			$this->Environment['BinPath'] 	= $basedir.'/bin'; 
			
			$os=ArrayUtil::getValue($_ENV,'OS',FALSE);
			
			$this->Environment['OS']		= $os;			
	 	}
	}

	function getEnv($what='') {

		if (!$this->Environment) {
		 	$this->getEnvironment();
	  	}
	 
	 	return ArrayUtil::getValue($this->Environment,$what,FALSE);
	 
	} 
 
 
}