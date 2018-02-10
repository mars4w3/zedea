<?php


class DBDriver  {

	var $Connection=null; 
	var $Host='localhost';
	var $Port='';
	var $User='';
	var $Pass='';
	var $Database='';
	var $TablePrefix='';
	
	var $LogLevel=0;
	
	var $SelectBlobs = FALSE;
	var $CacheSelects = TRUE;
	
	var $LastError='';
	
	var $useAccessFilter = FALSE;
 
 	function __construct() {

 	}

	function configure($config) {
	 
	 	$dbhost=ArrayUtil::getValue($config,'dbhost',FALSE);
	 	$dbuser=ArrayUtil::getValue($config,'dbuser',FALSE);
	 	$dbpass=ArrayUtil::getValue($config,'dbpass',FALSE);
	 	$dbname=ArrayUtil::getValue($config,'dbname',FALSE);
	 	$tblprefix=ArrayUtil::getValue($config,'tblprefix',FALSE);
	 	
	 	if ($dbhost) { $this->Host=$dbhost; }
	 	if ($dbuser) { $this->User=$dbuser; }
	 	if ($dbpass) { $this->Pass=$dbpass; }
	 	if ($dbname) { $this->Database=$dbname; }
	 	if ($tblprefix) { $this->TablePrefix=$tblprefix; }
	 
	 
	}

	function getInstance($obj) {
	 	if (is_object($obj)) {
	 	 	return $obj;
	 	}
	 	return new DBDriver();
	}

	function connect() {
	 	
			 
	}

	function getSessionLastQuery() {
		$sessionQuery=SessionUtil::getDecryptedValue('_query','');
 	 	$requestQuery=RequestUtil::getParam('sql','');
 	 	if ($requestQuery!=$sessionQuery && !empty($requestQuery) && RequestUtil::getParam('_ado','')=='sqlQuery') {
 	 	 	SessionUtil::setEncryptedValue('_query',$requestQuery);
 	 	 	
 	 	}
 	 	$out= SessionUtil::getDecryptedValue('_query','');
 	 	return $out;
 	 	 
	 
	}

 	function setSessionDatabase() {
 	 	$sessionDB=SessionUtil::getDecryptedValue('_dbn',$this->Database);
 	 	$requestDB=RequestUtil::getParam('dbn',$this->Database);
 	 	if ($requestDB!=$sessionDB && RequestUtil::getParam('_ado','')=='changeDB') {
 	 	 	SessionUtil::setEncryptedValue('_dbn',$requestDB);
 	 	 	
 	 	}
 	 	$this->Database=SessionUtil::getDecryptedValue('_dbn',$this->Database);
 	 
 	}


	function buildStatement($parts=array()) {
	 	$statement='';
	 	
	 	$keys=array('SELECT','FROM','WHERE','ORDER BY','LIMIT');
	 	foreach ($keys as $key) {
	 		$part=ArrayUtil::getValue($parts,$key);
	 		if (!empty($part)) {
	 			$statement.=$key.' '.$part.' '; 
	 		}
	 	}
	 	return $statement;
	 	
	 	
	}

	function translateStatement($statement) {
	 	return $statement;
	}
	
	function escape($statement) {
	 	return $statement;
	}
	
	function parseStatement($statement) {
	 	$out=array();
	 	$out['statement']=$statement;
	 	
	 	$parts=array(
		 	'SELECT'=>'',
			'FROM'=>'',
			'WHERE'=>'',
			'LIMIT'=>'',
			'ORDER'=>'',
			'GROUP'=>'',
			'HAVING'=>'',
		);
		$statement=str_replace("\n",' ',$statement);
		foreach($parts as $key=>$part) {
		 	$statement=preg_replace('/'.$key.'/i',"\n".$key,$statement);
		}
		$split=explode("\n",$statement);
	 	for ($s=0;$s<count($split);$s++) {
	 	 	$line=trim($split[$s]);
	 	 	$sql=substr($line,0,strpos($line,' '));
	 	 	$out[$sql]=trim(str_replace($sql,'',$line));
	 	} 	
		
	 	return $out;
	 
	}

	function select($args=array()) {
	 
	 
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	$dbtable=$this->prepareTablename($dbtable);
	 	
	 	$columns=ArrayUtil::getValue($args,'columns');
	 	if (!$columns) {
		 	return FALSE; 
		} 
	 
	 
	 	$filter=ArrayUtil::getValue($args,'filter','1');
	 	
	 	$sortBy=ArrayUtil::getValue($args,'sortBy','');
		$sortOrder=ArrayUtil::getValue($args,'sortOrder','');
		$defSort=trim($sortBy.' '.$sortOrder);
		 
		$sort=ArrayUtil::getValue($args,'sort',$defSort);
	 	$limit=ArrayUtil::getValue($args,'limit','1');
	 	$group=ArrayUtil::getValue($args,'group','');
		  
	 
	 
	 	$statement=" SELECT ";
	 	if ($this->CacheSelects) {
	 		$statement.=" ";
			$statement.=$this->translateStatement("SQL_CACHE");
			$statement.=" ";
	 	}
	 	$list='';
	 	foreach($columns as $col) {
	 	 	$list.=(empty($list)) ?  '' : ' , ';
	 	 	$list.=$col;
	 	}
	 	$statement.=$list;
	 	
	 	$statement.=" FROM ";
	 	
	 	$statement.=$dbtable;
	 	
	 	
	 	$statement.=" WHERE ".$filter;
	 	
	 	if (!empty($group)) {
	 		$statement.=" GROUP BY ".$group;
	 	}
	 	
	 	if (!empty($sort)) {
	 		$statement.=" ORDER BY ".$sort;
	 	}
	 	
	 	$statement.=" LIMIT ".$limit;
	 	
	 	
		$result=$this->execute($statement);
		
		
		$resulttype= ArrayUtil::getValue($args,'resulttype','data');
		
		switch ($resulttype) {
		 
		 	case 'data' : $out=$this->getResult($result); break;
		 	case 'record' : $data=$this->getResult($result); $out=isset($data[0]) ? $data[0] : FALSE; break;
		 	default		: $out=$result;
		}
 		
		return $out; 
		
	 	
	}
	
	
	function getRecord($id,$args=array()) {
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	$dbtable=$this->prepareTablename($dbtable);
	 	$defaultCol='id';
	 	$col=ArrayUtil::getValue($args,'column',$defaultCol);
	 	$filter=$col."='".$id."'";
	
	 	
	 	$args['columns']=ArrayUtil::getValue($args,'columns',array('*'));
	 	$args['filter']=$filter;
	 	$args['limit']=1;
	 	
	 	if ($this->useAccessFilter) {
	 	 	$accessfilter=ModAccess::getQueryFilter();
	 		if (!empty($accessfilter)) {
	 		 	$filter.=(!empty($filter)) ? ' AND ' : '';
	 	 		$filter.=" (".$accessfilter.")";
	 	 		$args['filter']=$filter;
	 		}
	 	}
	 	
	 	
	 	$data= $this->select($args);
	 	if (is_array($data)) {
	 	 	return (isset($data[0])) ? $data[0] : FALSE;
	 	}
	 	return FALSE;
	 
	}
	
	
	
	function mapColumns($data,$dbtable,$typeconvert=TRUE) {
	 	
		$dbtable=$this->prepareTablename($dbtable);
		 
		$dbcolumns=$this->getTableColumns($dbtable);
	 	
	 	foreach($data as $key=>$val) {
	 	 	if (!isset($dbcolumns[$key])) {
	 	 	 	unset($data[$key]);
	 	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'TableColumn '.$key.' does not exist');
	 	 	}
	 	 	// Type-Conversion
	 	 	if ($typeconvert) {
		 	 	$datatype=$dbcolumns[$key]['Type'];
		 	 	if (stristr($datatype,'date')) {
		 	 	 	$data[$key]=DateTimeUtil::formatGivenDate($data[$key],'%Y-%m-%d %H:%M:%S');
		 	 	}
		 	 	if (stristr($datatype,'timestamp')) {
		 	 	 	unset($data[$key]);
		 	 	}
			}	 	 	
	 	 	
	 	}
	 	return $data;
	}
	
	
	
	function recordexists($args=array(),$returnData=FALSE) {
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 
	 	$compare=ArrayUtil::getValue($args,'compare',array());
		if ($compare) {
	 
		 	$dbdriver=DBDriver::getInstance(ArrayUtil::getValue($args,'DBDriver',null));
		
			$args=array(
				'keyEnclosure'=>'',
				'valEnclosure'=>"'",
				'valEscapeCallback'=>array($dbdriver,'escape'),
	
			);	
			$compareKeyVal=ArrayUtil::toKeyValString($compare,' AND ',"'",$args);
			$filter=$compareKeyVal;
	 	 	 	
		 	$record=$this->select(array('dbtable'=>$dbtable,'columns'=>array('*'),'filter'=>$filter,'resulttype'=>'record'));

	 	 	if ($record) {
			   if ($returnData) {
			    	return $record;
			   } 
			   return TRUE; 
	 	 	}
	 	}
	 	return FALSE;
	 
	}
	
	
	function insert($data=array(),$args=array()) {
		$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	
	 	$ifNotExists=ArrayUtil::getValue($args,'ifnotexists',FALSE);
	 	if ($ifNotExists) {
	 	 	if ($this->recordexists($args)) {
	 	 	 	return FALSE;
	 	 	}
	 	}
	 	
	 	$dbtable=$this->prepareTablename($dbtable);
	 	


		$data=$this->mapColumns($data,$dbtable);

		$keys=ArrayUtil::getKeys($data);
		$values=ArrayUtil::getValues($data);

		$dbdriver=DBDriver::getInstance(ArrayUtil::getValue($args,'DBDriver',null));
		
		$args=array(
			'valEscapeCallback'=>array($dbdriver,'escape'),

		);

		$insertKeys=ArrayUtil::toSeparatedString($keys,',','');
		$insertValues=ArrayUtil::toSeparatedString($values,',',"'",$args);
		
		$statement='INSERT INTO ';
		$statement.=$dbtable;
		$statement.=' ('.$insertKeys.') ';
		$statement.=' VALUES ';
		$statement.=' ('.$insertValues.') ';

		//var_dump($statement);
			  
		$result=$this->execute($statement);	 
		if ($result) {
			$out=$this->getInsertID($data,$dbtable);
		}
		else {
		 	$out=FALSE;
		}
		return $out;
	 
	}
	
	
	function getInsertId($data=array(),$dbtable='') {
		
		$dbtable=$this->prepareTablename($dbtable);
		
		$queryargs=array(
	 		'dbtable'=>$dbtable,
			'columns'=>array('MAX(id) AS LASTINSERTID'),			 			
	 	);
	 	
	 	$data=$this->select($queryargs);
	 	return $data[0]['LASTINSERTID'];
	 
	}


	function update($data=array(),$compare=array(),$args=array()) {
	 
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	$dbtable=$this->prepareTablename($dbtable);
	 	
	 	$data=$this->mapColumns($data,$dbtable);
	 	$compare=$this->mapColumns($compare,$dbtable,FALSE);
	 	
		$dbdriver=DBDriver::getInstance(ArrayUtil::getValue($args,'DBDriver',null));
	
		$args=array(
			'keyEnclosure'=>'',
			'valEnclosure'=>"'",
			'valEscapeCallback'=>array($dbdriver,'escape'),

		);	
		$updateKeyVal=ArrayUtil::toKeyValString($data,',',"'",$args);
		$compareKeyVal=ArrayUtil::toKeyValString($compare,' AND ',"'",$args);

		
		$statement='UPDATE ';
		$statement.=$dbtable;
		$statement.=' SET ';
		$statement.=$updateKeyVal;
		$statement.=' WHERE ';
		$statement.=$compareKeyVal;
		
		
		$result=$this->execute($statement);		
	 
	}
	
	
	function delete($compare=array(),$args=array()) {
	 
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	$dbtable=$this->prepareTablename($dbtable);
	 	
	 	$compare=$this->mapColumns($compare,$dbtable,FALSE);
	
		$dbdriver=DBDriver::getInstance(ArrayUtil::getValue($args,'DBDriver',null));

		$limit=ArrayUtil::getValue($args,'limit',1);
	
		$args=array(
			'keyEnclosure'=>'',
			'valEnclosure'=>"'",
			'valEscapeCallback'=>array($dbdriver,'escape'),

		);	
		$compareKeyVal=ArrayUtil::toKeyValString($compare,' AND ',"'",$args);

		
		$statement='DELETE ';
		$statement.=' FROM ';
		$statement.=$dbtable;
		$statement.=' WHERE ';
		$statement.=$compareKeyVal;
		$statement.=' LIMIT ';
		$statement.=$limit; 
		
		$result=$this->execute($statement);		
	 
	}




	function getColValue($dbtable='',$column,$compare=array(),$default=FALSE) {
	 
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (empty($column)) {
	 	 	return FALSE;
	 	}
	 	
	 	$compare=$this->mapColumns($compare,$dbtable,FALSE);
	 	$args=array(
			'keyEnclosure'=>'',
			'valEnclosure'=>"'",
		);	
		$compareKeyVal=ArrayUtil::toKeyValString($compare,' AND ',"'",$args);
	 	
	 	$args=array(
			'dbtable'=>$dbtable,
			'columns'=>array($column),
			'filter'=>$compareKeyVal,
			'limit'=>1,
			'resulttype'=>'record',
			 
		);
		$data=$this->select($args);
		if (!$data) {
		 	return $default;
		}
		$out=ArrayUtil::getValue($data,$column,$default);
		return $out;
	 
	}

	function setColValue($dbtable='',$compare=array(),$key='',$value='') {
	 
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (empty($key)) {
	 	 	return FALSE;
	 	}
	 	$args=array(
			'dbtable'=>$dbtable, 
		);
		$data=array($key=>$value);
		return $this->update($data,$compare,$args);
	 
	}
	
	function prepareColAttr($column,$addColName=TRUE) {
	 
	 		$out='';
	 		$dbattr=ArrayUtil::getValue($column,'dbattr',array());
 	 	 	$datatypeattr=ArrayUtil::getValue($column,'datatypeattr',array());
 	 	 	
 	 	 	$columnName=ArrayUtil::getValue($column,'name','column'.$ct);
 	 	 	if ($addColName) {
 	 	 		$out.=$columnName;
 	 	 	}
 	 	 	$out.=' ';
 	 	 	$out.=ArrayUtil::getValue($column,'datatype','text');
 	 	 	$size=ArrayUtil::getValue($datatypeattr,'SIZE',FALSE);
 	 	 	$out.=(($size) ? '('.$size.')' : '');
 	 	 	
 	 	 	$out.=' ';
 	 	 	$out.=(ArrayUtil::getValue($datatypeattr,'UNSIGNED',FALSE)===TRUE) ? 'UNSIGNED' : '';
 	 	 	$out.=' ';
 	 	 	$out.=(ArrayUtil::getValue($datatypeattr,'ZEROFILL',FALSE)===TRUE) ? 'ZEROFILL' : '';
 	 	 	
 	 	 	$out.=' ';
 	 	 	$out.=(ArrayUtil::getValue($datatypeattr,'NULL',FALSE)===TRUE) ? 'NULL' : 'NOT NULL';
 	 	 	
			$out.=' ';
 	 	 	$default=ArrayUtil::getValue($datatypeattr,'DEFAULT',FALSE);
 	 	 	$out.=(($default) ? "DEFAULT '".$default."' " : '');
 	 	 	
 	 	 	return $out;
	 
	}



	function tableExists($dbtable) {
	 	$res= $this->getTableInfo($dbtable);
	 	if ($res) {
	 	 	return TRUE;
	 	}
	 	return FALSE;
	 
	}


	function createTable($dbtable='',$columns=array(),$ifNotExists=TRUE) {
	
	 
	 	$dbtable=$this->prepareTablename($dbtable);
	 	
	 	if ($ifNotExists) {
	 	 	if ($this->tableExists($dbtable)) {
	 	 	 	return;
	 	 	}
	 	}
	 	
	 
	 	$statement=$this->translateStatement('CREATE TABLE');
	 	$statement.=' ';
	 	$statement.=($ifNotExists) ? $this->translateStatement('IF NOT EXISTS') : '';
	 	$statement.=' ';
	 	$statement.=' '.$dbtable.' ';
	 	$statement.=' ( ';
	 
	 	$def='';
	 	$add='';
 	 	$ct=0;
 	 	foreach($columns as $column) {
 	 	 
 	 	 	$line='';
 	 	 	$dbattr=ArrayUtil::getValue($column,'dbattr',array());
 	 	 	$datatypeattr=ArrayUtil::getValue($column,'datatypeattr',array());
 	 	 	
 	 	 	$columnName=ArrayUtil::getValue($column,'name','column'.$ct);
 	 	 	$line.=$columnName;
 	 	 	$line.=' ';
 	 	 	$line.=ArrayUtil::getValue($column,'datatype','text');
 	 	 	$size=ArrayUtil::getValue($datatypeattr,'SIZE',FALSE);
 	 	 	$line.=(($size) ? '('.$size.')' : '');
 	 	 	
 	 	 	$line.=' ';
 	 	 	$line.=(ArrayUtil::getValue($datatypeattr,'UNSIGNED',FALSE)===TRUE) ? 'UNSIGNED' : '';
 	 	 	$line.=' ';
 	 	 	$line.=(ArrayUtil::getValue($datatypeattr,'ZEROFILL',FALSE)===TRUE) ? 'ZEROFILL' : '';
 	 	 	
 	 	 	$line.=' ';
 	 	 	$line.=(ArrayUtil::getValue($datatypeattr,'NULL',FALSE)===TRUE) ? 'NULL' : 'NOT NULL';
 	 	 	
			$line.=' ';
 	 	 	$default=ArrayUtil::getValue($datatypeattr,'DEFAULT',FALSE);
 	 	 	$line.=(($default) ? "DEFAULT '".$default."' " : '');
 	 	 	
 	 	 	$line.=' ';
 	 	 	$auto=ArrayUtil::getValue($dbattr,'AUTO_INCREMENT',FALSE);
 	 	 	$line.=(($auto) ? "AUTO_INCREMENT " : '');
 	 	 	if ($auto) {
 	 	 	 	$add.=', ' ;
 	 	 	 	$add.='  INDEX ('.$columnName.')';
 	 	 	 	$add.=' ';
 	 	 	}
 	 	 	
 	 	 	$line.=' ';
 	 	 	$unique=ArrayUtil::getValue($dbattr,'UNIQUE',FALSE);
 	 	 	$line.=(($unique) ? "UNIQUE " : '');
 	 	 	if ($unique) {
 	 	 	 	$add.=', ' ;
 	 	 	 	$add.=' KEY ('.$columnName.')';
 	 	 	 	$add.=' ';
 	 	 	}
 	 	 			
 	 	 
 	 	 
 	 	 	$ct++;
 	 	 	$def.=(!empty($def)) ? ' ,'."\n" : "\n";
 	 	 	$def.=$line;
 	 	 	$def.="\n";
 	 	 
 	 	}
 	 	$statement.=$def;
 	 	$statement.=$add;
 	 	$statement.=' )';
 	 	$statement.=' ';
 	 	
 	 	//var_dump($statement);
 	 	
 	 	$result=$this->execute($statement);
 	 
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
	 
	 
	}


	function alterTable($dbtable='',$cmd='CHANGE_COLUMN',$column=array(),$args=array()) {
	 	
		$dbtable	= $this->prepareTablename($dbtable);
		$colName	= FALSE; 
		
		if (!$this->tableExists($dbtable)) {
	 	 	 	return FALSE;
	 	}
	
		if (strstr($cmd,'COLUMN')) {
			if (!is_array($column)) {
			 	return FALSE;
			}
			$colName = ArrayUtil::getValue($column,'name',FALSE);
			if (!$colName) {
			 	return FALSE;
			} 
		}

		$statement=$this->translateStatement('ALTER TABLE');	 
		$statement.=' '.$dbtable.' ';
			
	 	switch ($cmd) {
	 	 	case 'CHANGE_COLUMN' 	: 
			  							$colStatement = $this->prepareColAttr($column);
			  							$statement.=' '.$this->translateStatement('CHANGE');
			  							$statement.=' '.$colName;
			  							$statement.=' '.$colStatement;
			  							break;
			case 'ADD_COLUMN' 		: 
			  							$colStatement = $this->prepareColAttr($column);
			  							$statement.=' '.$this->translateStatement('ADD');
			  							$statement.=' '.$colStatement;
			  							$addAfter	= ArrayUtil::getValue($args,'ADD_AFTER',FALSE);
			  							if ($addAfter)	{
			  							 	$statement.= ' '.$this->translateStatement('AFTER').' '.$addAfter; 
			  							}
			  							break;
			  							
			default					:
										return FALSE; 	// nothing to alter
	 	 									
	 	}
	 

	 
	 	$result=$this->execute($statement);
 	 
	 	OverrideUtil::callHooks(__CLASS__,__METHOD__,$this);
			  
		return $result;								
	 	 									

	 
	}


	function renameTable() {
	 
	 
	}




	

	function dropTable($dbtable='') {
	 
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (!ModAccess::UserIsAdmin()) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Operation requires AdminUser');
	 	 	return FALSE;
	 	}
	 
	 	$dbtable=$this->prepareTablename($dbtable);
	 	if (!$this->tableExists($dbtable)) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Table <i>'.$dbtable.'</i> does not exist');
	 	 	return FALSE;
	 	}
	 	
	 	$statement=$this->translateStatement('DROP TABLE');
		$statement.=' '.$dbtable;
	 	$result=$this->execute($statement);
	 
	}


	function emptyTable($dbtable='')  {
	 
	 	if (empty($dbtable)) {
	 	 	return FALSE;
	 	}
	 	if (!ModAccess::UserIsAdmin()) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Operation requires AdminUser');
	 	 	return FALSE;
	 	}
	 
	 	$dbtable=$this->prepareTablename($dbtable);
	 	if (!$this->tableExists($dbtable)) {
	 	 	ErrorHandler::throwException(__CLASS__,__METHOD__,'Table <i>'.$dbtable.'</i> does not exist');
	 	 	return FALSE;
	 	}
	 	
	 	$statement=$this->translateStatement('TRUNCATE TABLE');
		$statement.=' '.$dbtable;
	 	$result=$this->execute($statement);
	 
	 
	}

	function calcLimitOffset($count=0,$limit=0,$page=0) {
	 	
	 	if ($count<=$limit) {
	 	 	return $limit;
	 	}
	 	if ($page<1) {
	 	 	return $limit;
	 	}
	 	$offset=($page-1)*$limit; 
	 	$expr=$offset.','.$limit;
	 	return $expr;
	 
	}

	function countRows($args=array()) {
	 
	 	$dbtable=ArrayUtil::getValue($args,'dbtable');
	 	if (!$dbtable) {
	 	 	return FALSE;
	 	}
	 	$dbtable=$this->prepareTablename($dbtable);
	 
	 	$countKey='__num_rows_by_statement__';
	 
	 	$statement='SELECT ';
	 	$statement.=$this->translateStatement('COUNT');
	 	$statement.='(*)';
	 	$statement.=' AS '.$countKey;
	 	
	 	$statement.=' FROM '.$dbtable;
	 	
	 	$filter=ArrayUtil::getValue($args,'filter','1');
	 	
	 	$statement.=" WHERE ".$filter;
	 	
	 	$result=$this->execute($statement);
	 	
	 	$data=$this->getResult($result);
	 	
	 	$count= ArrayUtil::getValue($data[0],$countKey,0);
	 	
	 	return intval($count);	
	 	
	 
	}

	function getTables() {
	 	$statement=$this->translateStatement('SHOW TABLES');
	 	$result=$this->execute($statement);
		$resulttype= ArrayUtil::getValue($args,'resulttype','data');	
		switch ($resulttype) {
		 
		 	case 'data' : $out=$this->getResult($result); break;
		 	default		: $out=$result;
		}
		return $out; 
	}
	
	
	function getTableInfo($dbtable,$args=array()) {
	 	$statement=$this->translateStatement('DESCRIBE');
	 	$statement.=' '.$dbtable;
	 	$result=$this->execute($statement);
		$resulttype= ArrayUtil::getValue($args,'resulttype','data');	
		switch ($resulttype) {
		 
		 	case 'data' : $out=$this->getResult($result); break;
		 	default		: $out=$result;
		}
		return $out; 
	}
	
	function getTableColumns($dbtable,$args=array()) {
	 	$tblInfo=$this->getTableInfo($dbtable,$args);
	 	$out=array();
	 	foreach ($tblInfo as $row) {
	 	 	$columnName=$row['Field'];
	 	 	$out[$columnName]=$row;
	 	}
	 	return $out;
	 
	}
	
	function describeColumn($dbtable,$column,$what='') {
	 	$columns=$this->getTableColumns($dbtable);
	 	$info = $columns[$column];
	 	switch ($what) {
	 	 	case 'COMPACT' : 
	 	 					$out='';
	 	 					foreach($info as $key=>$val) {
	 	 					 	if ($key!='Field') {
	 	 					 		$out.=$val.' ';
	 	 					 	}
	 	 					}
	 	 					return $out;
	 	 	case 'Type'		:
			  				$out= ArrayUtil::getValue($info,'Type',FALSE);
			  				return $out;
	 	 					
	 		default			: return $info;
	 	}
	}
	
	

	function show($what) {
	 	$statement=$this->translateStatement('SHOW '.$what);
	 	$result=$this->execute($statement);
		$resulttype= ArrayUtil::getValue($args,'resulttype','data');	
		switch ($resulttype) {
		 
		 	case 'data' : $out=$this->getResult($result); break;
		 	default		: $out=$result;
		}
		return $out; 
	}

	function getShowOptions() {
	 	$opt= array(
		 		'VARIABLES'=>'Variablen',
		 		'TABLES'=>'Tabellen',
		 		'DATABASES'=>'Datenbanken',
		 		'PROCESSLIST'=>'Prozesse',
		 		'STATUS'=>'Serverstatus',
		 		'OPEN TABLES'=>'Offene Tabllen',
		 		'ERRORS'=>'Fehler',
		 		'WARNINGS'=>'Warnungen',
		 		'COLLATION'=>'Kollation',
		 		'CHARACTER SET'=>'Zeichensatz',
		 
		 		);
		 return $opt;		
	 
	}

	function execute($sql) {

	 	if (!$this->Connection) {
	 	 	$this->connect();
	 	}
	 	if (!$this->Connection) {
	 		return FALSE;
	 	}	 
	 	
	 	$this->log($sql);
	 	//ErrorHandler::dump($sql);
	 	return $this->query($sql);
	 
	}

	function query($sql) {
	 
		echo $sql;
	 	return FALSE;
		 	 
	}


	function getResult($ressource=0) {
	 
	 	return $ressource;
	 
	}



	function prepareTablename($tablename) {
	 	$tblprefix=$this->TablePrefix;
	 	if (!empty($tblprefix)) {
	 	 	if (substr($tablename,0,strlen($tblprefix))!=$tblprefix) {
	 	 	 	$tablename=$tblprefix.$tablename;
	 	 	}
	 	}
		return $tablename;
	 
	}
	
	
	function getLastError() {
	 	return $this->LastError;
	}
	
	function translateError($errmsg) {
	 	
	 	if (preg_match('/duplicate entry \'([^\']*)\'/i',$errmsg,$matches)) {
	 	 	$babelrepl=array('dbvalue'=>$matches[1]);
	 	 	$out=Babel::_('DBError::DuplicateEntry %%dbvalue%%',$babelrepl);
	 	 	return $out;
	 	}
	 
	 	return $errmsg;
	}
	
	
	
	function log($statement) {
	 
	 	if (preg_match('/INSERT|UPDATE|DELETE|ALTER/i',$statement)) {
	 
		 	$logpath='log';
		 	$path=Loader::getPath($logpath,TRUE);
		 	$file=$path.'/dbquery.log';
		 	$out='---'."\n";
		 	$out.=time();
		 	$out.="\n";
		 	$out.=trim($statement);
		 	$out.="\n";
		 	FileUtil::appendFile($file,$out,TRUE);
	 	
	 	}
	}
	
	
	
	
	
	
	
	function joinedSelect($args) {
 
 		$tables 	= ArrayUtil::getValue($args,'tables',array());
		$joins 		= ArrayUtil::getValue($args,'joins',array()); 
		
		$sortBy 		= ArrayUtil::getValue($args,'sortBy',''); 
		$sortOrder 		= ArrayUtil::getValue($args,'sortOrder',''); 
		$filter 		= ArrayUtil::getValue($args,'filter',''); 
		
		
		
		$DBDriver	= ArrayUtil::getValue($args,'DBDriver',null); 

		// build the sql statement
		
		// a) SELECT + columns
		
		$select='SELECT ';
		$selectCols='';
		
		$joinFilter='';
		$joinSort='';

		
		foreach($tables as $table=>$info) {
		 	$dbtable=ArrayUtil::getValue($info,'dbtable','');
		 	$dbtable=$DBDriver->prepareTableName($dbtable);
		 	$columns=ArrayUtil::getValue($info,'columns',array());
		 	 
			foreach ($columns as $column) {
		 		$selectCols.=(!empty($selectCols)) ? ' , ' : '';
		 		$colExpr=$column;
		 		if (!strstr($column,'%%dbtable%%')) {
		 		 	$colExpr='%%dbtable%%'.'.'.$column;
		 		}
		 		$colExpr=str_replace('%%dbtable%%',$dbtable,$colExpr);
		 		$selectCols.=$colExpr;
		 		$filter=str_replace($column,$colExpr,$filter);
		 		$sortBy=str_replace($column,$colExpr,$sortBy);
		 	}
		 	
		 
		 
		}
		
		$select.=$selectCols;
		
		// b) FROM + JOIN
		$fromTable	= '';
		$dbjoin		= '';
		$groupBy	= '';
		 		
		foreach($joins as $join) {
		
		 	$lt			= $DBDriver->prepareTableName($tables[($join['LT'])]['dbtable']);
		 	$fromTable.=(empty($fromTable)) ? $lt : '';
		 	
		 	$rt			= $DBDriver->prepareTableName($tables[($join['RT'])]['dbtable']);
		 	$lc			= $lt.'.'.$join['LC'];
		 	$rc			= $rt.'.'.$join['RC'];
		 	$s			= array('[LC]','[RC]');
		 	$r			= array($lc,$rc);
		 	$on			= str_replace($s,$r,$join['ON']);
		 	$s			= array('[RT]','[ON]');
		 	$r			= array($rt,$on);
		 	
			$expr		= str_replace($s,$r,'LEFT JOIN [RT] ON ([ON])');
		 	$dbjoin		.=' '.$expr;
		 	
		 	$groupBy	.=' GROUP BY '.$lc;
		}
		
		
		$from	= ' FROM '.$fromTable;
		
		$sql	.=$select.$from.$dbjoin;
		
		// c) WHERE 
		if (!empty($filter)) {
		 	$sql.=' WHERE '.$filter;
		}
		
		// d) GROUP
		
		$sql	.=$groupBy;
		
		// e) ORDER
		if (!empty($sortBy)) {
		 
		 	$sql.=' ORDER BY '.trim($sortBy.' '.$sortOrder);
		}
		
		// f) LIMIT
		
		//echo $sql;
		//die();
		
		$res	= $DBDriver->execute($sql);
		$data	= $DBDriver->getResult($res);
		
		return $data;
		
	}
	
	
	
 
 
}