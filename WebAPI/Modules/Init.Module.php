<?php
if(!defined('IN_FRAMEWORK')){ die; }

class Init{
	protected $redis;
	protected $Configs;
	protected $Mysql;
	
	public function __construct(){
		Import("Security");
		$this -> Mysql = M("Mysql");
		$this -> Configs = C("Site");
		$this -> redis = new Redis;
		$this -> redis -> connect($this -> Configs['REDIS_SERVER'], $this -> Configs['REDIS_PORT']);
		$this -> redis -> auth($this -> Configs['REDIS_PASSWD']); 
	}
	
	public static function genError($code){
		H('json');
		if($code == '1'	){
			echo '{"success":false,"error":"bad_request"}';
			return true;
		}elseif($code == '2'){
			echo '{"success":false,"error":"invalid_secret"}';
			return true;
		}elseif($code == '3'){
			echo '{"success":false,"error":"wrong_method"}';
			return true;
		}elseif($code == '4'){
			echo '{"success":false,"error":"not_found"}';
			return true;
		}elseif($code == '5'){
			echo '{"success":false,"error":"internal_error"}';
			return true;
		}elseif($code == '6'){
			echo '{"success":false,"error":"missing_input"}';
			return true;
		}elseif($code == '7'){
			echo '{"success":false,"error":"invalid_token"}';
			return true;
		}elseif($code == '8'){
			echo '{"success":false,"error":"unknown_user"}';
			return true;
		}elseif($code == '9'){
			echo '{"success":false,"error":"insufficent_funds"}';
			return true;
		}
	}
	//根据sitekey获取siteid
	public function getSidBySitekey($key){
		if($res = $this -> redis -> get('site-'.$key)){
			return json_decode($res, true)[2];
		}else{
			return false;
		}
	}
}