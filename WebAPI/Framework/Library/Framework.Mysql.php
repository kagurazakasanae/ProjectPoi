<?php
//PDO MySQL Controller
//Author: Shokaku
//Version: 1.0
//Create Date: 2015-7-10 19:33:06
//Last Modify: 2015-9-15 21:01:17
//注意:只有当调用本操作类封装好的方法时才可以免过滤并不会造成注入,如直接调用execute方法执行拼接好的SQL语句请做好过滤
if(!defined('IN_FRAMEWORK')){ die; }

class Mysql{
	private $pdo;
	/*
		公有方法,构析函数
		接收参数:None
		返回参数:None
		作用:初始化pdo对象
	*/
	public function __construct(){
		$Mysql_Config = C("Mysql");
		$dsn = 'mysql:host='.$Mysql_Config['Host'].';dbname='.$Mysql_Config['Database'].'';
		$this->pdo = new PDO($dsn,$Mysql_Config['Username'],$Mysql_Config['Password'],array(PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES UTF8',PDO::ATTR_PERSISTENT => true));  //如不需要MySQL长连接请将最后的true改成false
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}
	/*
		公有方法,add
		接收参数:$table(string)(必须),$addData(array)(必须)
		$addData格式:
		array(
			"column" => "data"
		)
		返回参数:rows inserted(int)
		作用:向数据表中插入内容
	*/
	public function add($table,Array $addData){
		$addFields = array();  
		$addValues = array();  
		foreach($addData as $key => $value){
			if(is_array($value)){
				throw new Exception('Error occur when process the sql query');
			}
			$addFields[] = $key;
			$addValues[] = $value;
		}
		unset($key);
		unset($value);
		$addFields = '`'.implode('`,`',$addFields).'`';
		$add = '';
		for($i=0;$i<count($addValues);$i++){
			$add .= "?,";
		}
		$add = substr($add,0,-1);
		foreach($addValues as $value){
			$preparedata[] = $value;
		}
        $sql = "INSERT INTO $table ($addFields) VALUES ($add)";
		
		return $this->execute($sql,$preparedata)->rowCount();
	}
	/*
		公有方法,update
		接收参数:$table(string)(必须),$updateData(array)(必须),$where(array)(可选)
		$updateData格式
		array(
			"column" => "data"
		)
		$where格式
		array(
			"column" => "data"
		)
		返回参数:rows inserted(int)
		作用:修改数据表中的内容,如需where为不等式或其他,请自行执行sql查询
	*/
	public function update($table,Array $updateData,Array $where = array()){
		$updateQuery = '';
		foreach($updateData as $key => $value){
			$updateQuery .= '`'.$key.'`='.'?,';
			$preparedata[] = $value;
		}
		$updateQuery = substr($updateQuery,0,-1);
		$wheredata = '';
		foreach($where as $key => $value){
			if(is_array($value)){
				throw new Exception('Error occur when process the sql query');
			}
			$wheredata .= '`'.$key.'`=? AND ';
			$preparedata[] = $value;
		}
		$wheredata = substr($wheredata,0,-4);
		
		unset($where);
		unset($key);
		unset($value);
		
		if(isset($wheredata)){
			$sql = "UPDATE $table SET $updateQuery WHERE $wheredata";
		}else{
			$sql = "UPDATE $table SET $updateQuery";
		}
		
		return $this->execute($sql,$preparedata)->rowCount();
	}
	/*
		公有方法,confirm
		接收参数:$table(string)(必须),$confirmData(array)(必须)
		$confirmData格式
		array(
			"column" => "data"
		)
		返回参数:bool
	*/
	public function confirm($table,Array $confirmData){
		$wheredata = '';
		foreach($confirmData as $key => $value){
			if(is_array($value)){
				throw new Exception('Error occur when process the sql query');
			}
			$wheredata .= '`'.$key.'`=? AND ';
			$preparedata[] = $value;
		}
		unset($confirmData);
		$wheredata = substr($wheredata,0,-4);
		$sql = "SELECT * FROM $table WHERE $wheredata LIMIT 1";
		
		if($this->execute($sql,$preparedata)->rowCount() == 1){
			return true;
		}else{
			return false;
		}
	}
	/*
		公有方法,delete
		接收参数:$table(string)(必须),$deleteData(array)(必须)
		$deleteData格式
		array(
			"column" => "data"
		)
		返回参数:bool
	*/
	public function delete($table,Array $deleteData){
		$wheredata = '';
		foreach($deleteData as $key => $value){
			if(is_array($value)){
				throw new Exception('Error occur when process the sql query');
			}
			$wheredata .= '`'.$key.'`=? AND ';
			$preparedata[] = $value;
		}
		unset($deleteData);
		$wheredata = substr($wheredata,0,-4);
		$sql = "DELETE FROM $table WHERE $wheredata";
		
		if($this->execute($sql,$preparedata)->rowCount() > 0){
			return true;
		}else{
			return false;
		}
	}
	/*
		公有方法,select
		接收参数:$table(string)(必须),$selectData(array)(可选),$orderby(string)(可选)
		$selectData格式
		array(
			"column" => "data"
		)
		or
		array(
			"column[LIKE]" => "%xxx%"
		)
		$orderby格式
		"colume DESC"
		或
		"column"
		或任何可以写在order by后面的
		$limit格式
		"0,10"
		或任何可以写在limit后面的
		返回参数:
		成功时:array(
			"column" => "data"
		)
		失败时:bool(false)
	*/
	public function select($table,Array $selectData = array(),$orderby = null,$limit = null){
		if(count($selectData) != 0){
			$wheredata = '';
			foreach($selectData as $key => $value){
				if(is_array($value)){
					throw new Exception('Error occur when process the sql query');
				}
				if(stristr($key,'[LIKE]')){
					$key = str_replace('[LIKE]','',$key);
					$wheredata .= '`'.$key.'` LIKE ? AND ';
				}else{
					$wheredata .= '`'.$key.'`=? AND ';
				}
				$preparedata[] = $value;
			}
			unset($selectData);
			$wheredata = substr($wheredata,0,-4);
			$sql = "SELECT * FROM `$table` WHERE $wheredata";
		}else{
			$sql = "SELECT * FROM `$table`";
		}
		if($orderby != null){
			$sql .= ' ORDER BY '.$orderby;
		}
		if($limit != null){
			$sql .= ' LIMIT '.$limit;
		}
		if(isset($preparedata)){
			$result = $this -> execute($sql,$preparedata);
		}else{
			$result = $this -> execute($sql);
		}
		
		if($result -> rowCount() > 0){
			return $result -> fetchAll(PDO::FETCH_ASSOC);
		}else{
			return false;
		}
	}
	/*
		公有方法,countsql
		接收参数:$table(string)(必须),$countData(array)(可选)
		$countData格式
		array(
			"column" => "data"
		)
		返回参数:$count(int)
	*/
	public function countsql($table,Array $countData = array()){
		if(count($countData) != 0){
			$wheredata = '';
			foreach($countData as $key => $value){
				if(is_array($value)){
					throw new Exception('Error occur when process the sql query');
				}
				$wheredata .= '`'.$key.'`=? AND ';
				$preparedata[] = $value;
			}
			$wheredata = substr($wheredata,0,-4);
			unset($countData);
			$sql = "SELECT COUNT(*) AS `a` FROM $table WHERE $wheredata";
			$result = $this -> execute($sql,$preparedata);
		}else{
			$sql = "SELECT COUNT(*) AS `a` FROM $table";
			$result = $this -> execute($sql);
		}
		
		return $result -> fetch(PDO::FETCH_ASSOC)['a'];
	}
	/*
		公有方法,selectmax
		接收参数:$table(string)(必须),$column(必须)
		$column格式
		string
		返回参数:$max(int)
	*/
	public function selectmax($table,$column){
		$sql = "SELECT MAX($column) AS `a` FROM $table";
		$result = $this -> execute($sql);
		return $result -> fetch(PDO::FETCH_ASSOC)['a'];
	}
	/*
		公有方法,selectrandom
		接收参数:$table(string)(必须),$key(必须),$num(int)(必须)
		$column格式
		string
		$key格式
		string 数据表关键键名
		$num格式
		int 返回数量
		返回参数:$return(boolen or array)
	*/
	public function selectrandom($table,$key,$num){
		$total = $this -> selectmax($table,$key);
		$return = array();
		if($total > 0){
			if($total < $num){
				$num = $total;
			}
			for($i=0;$i<$num;$i++){
				while(true){
					$res = $this -> select($table,array($key=>mt_rand(1,$total)));
					if($res){
						$return[$i] = $res[0];
						break;
					}
				}
			}
			return $return;
		}else{
			return false;
		}
	}
	/*
		公有方法,execute
		接收参数:$sql(string)(必须),$data(array)(可选)
		$data是为prepare预处理中的占位符填充内容
		返回参数:$_stmt(PDO result)
	*/
	public function execute($sql,Array $data = array()) {
		//file_put_contents(ROOT_PATH.'/sql.txt',$sql."\r\n",FILE_APPEND);
		$_stmt = $this -> pdo -> prepare($sql);
		if(count($data) > 0){
			$_stmt -> execute($data);
		}else{
			$_stmt -> execute();
		}
		return $_stmt;  
	}  
}