<?php
/*
for php 5.4 linux
@function:
   export data from mongodb and import to mysql
   very useful for grails change database from mongodb to mysql
@author: yifangyou
@date:   2014-03-02 00:33:00

create database xsb2 CHARACTER SET utf8;
mysqldump --default-character-set=utf8 xsb > xsb.sql
mysql --default-character-set=utf8 xsb2 < xsb.sql
*/

ini_set('mongo.long_as_object',1);
$mysqlDbName="testmysqldb";
$mongoDbName="testMongodb";
$mongoUrl="mongodb://192.168.60.5";
$isDeleteAllDataFirst=false;

mysql_connect("localhost","root","") or die(mysql_error());
$mysql_config=array('hostname'=> "localhost",
	 'username'=> 'root',
	 'password'=> '',   
	 'database'=> $mysqlDbName,
	 'charset'=> 'utf8',
	 'pconnect'=> 0,      
	 'debug'=> "0",    
	 ); 
	 
include("mysql.class.php");

$mysqldb=new mysql($mysql_config);

$mysqldb->connect() or die(mysql_error());
echo 456;
$mongo = new MongoClient($mongoUrl,array('connect'=>false));//init mongodb
$mongo->connect();//connet to mongo db
$db = $mongo->selectDB($mongoDbName);
$collections=$db->listCollections();
foreach ($collections as $collection) {
    $collectionName=$collection->getName();
    if(strpos($collectionName,"next_id")===FALSE){
		echo $collectionName."=>".toUnderlineCase($collectionName)."\n";
		$mysqlTableName=toUnderlineCase($collectionName);
		$mysqlColumns=getMysqlColumns($mysqlDbName,$mysqlTableName);
		if(count($mysqlColumns)>0){
			$cursor = $collection->find();
			$insertCount=0;
			//delete all data;
			if($isDeleteAllDataFirst){
				mysql_query("truncate table $mysqlTableName") or $mysqldb->delete($mysqlTableName,1) or die(mysql_error());
			}
			foreach ($cursor as $doc) {
				$data = array();
				foreach($doc as $colName => $value){
					$colName=toUnderlineCase($colName);
					if(in_array($colName,$mysqlColumns))
					$data[$colName]=$value;
				}
				if(count($data)>0){
					$mysqldb->insert($data,$mysqlTableName,true) or die(mysql_error());
					$insertCount++;
				}
			}
			error_log("$mysqlTableName insert $insertCount\n",3,"success.log");
		}else{
			error_log("$mysqlTableName not exits\n",3,"nofound.log");
		}
	}
}



	/**
     * @static change camel to undeline case
     * @param $in  
     * @return string undeline case
     */
    function toUnderlineCase($in)
    {
		$snakeTableName = preg_replace('/(?<!\b)(?=[A-Z])/', "_", str_replace("_","",$in));  
        return strtolower($snakeTableName);
    }

	/**
     * @static get columns name by mysql db name and table name;
     * @param $mysqlDbName mysql db name
	 * @param $mysqlTableName mysql table name
     * @return array all column names in $mysqlTableName
     */
	function getMysqlColumns($mysqlDbName,$mysqlTableName){
		$columnArray=array();
		$sql="SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='$mysqlDbName'  AND `TABLE_NAME`='$mysqlTableName'";
		//echo $sql."\n";
		$res = mysql_query($sql) or die(mysql_error());
		while ($row = mysql_fetch_assoc($res)){
			foreach($row as $column => $columnname){ 
				
				$columnArray[] = $columnname;
			}
		} 
		return $columnArray;
	} 
   
?>