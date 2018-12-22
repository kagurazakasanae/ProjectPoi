<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('User');

class Init{
	protected $Mysql;
	protected $XT;
	protected $Configs;
	protected $User;
	public function __construct(){
		Import("Security");
		//$this -> Mysql = M("Mysql");
		$this -> XT = M("XT");
		$this -> Configs = C("Site");
		$this -> User = new User($this -> Configs);
		$this -> XT -> setdata("site",$this -> Configs['SITE_URL']);
		$this -> XT -> setdata("name",$this -> Configs['SITE_TITLE']);
		$this -> XT -> setdata("description",$this -> Configs['SITE_DES']);
		$islogin = $this -> User -> isLogin($this -> Configs['SITE_KEY']);
		$this -> XT -> setdata('logined', $islogin);
		if($islogin){
			$this -> XT -> setdata('nonce', substr(json_decode($_COOKIE['session'], true)['id'],0,16));
		}
		$module =  D('REWRITE',array('[String]0'=>'','[String]1'=>''));
		$module = $module[0] != 'account' ? $module[0] : $module[1];
		$this -> XT -> setdata('thismodule', $module);
	}
	
	public function outNotfound(){
		H('404');
		$this -> XT -> setdata('thistitle', '页面未找到');
		$this -> XT -> parse('404');
		$this -> XT -> out();
		return true;
	}
	
	public function outError($title, $reason){
		$this -> XT -> setdata('thistitle', '错误');
		$this -> XT -> setdata('messagetitle', $title);
		$this -> XT -> setdata('messagecontent', $reason);
		$this -> XT -> parse('message');
		H('403');
		$this -> XT -> out();
		return true;
	}
	
	public function verifyToken($token, $hashes){
		$data = http_build_query(array('secret'=>'H9ZDTr2vHO1VKW4mBIVTWEaF', 'token'=>$token, 'hashes'=>$hashes));
		$ch = curl_init();  
		curl_setopt($ch, CURLOPT_URL, 'https://api.ppoi.org/token/verify');  
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
		curl_setopt($ch, CURLOPT_SSLVERSION, 4);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
		curl_setopt($ch, CURLOPT_POST, true);  
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
		$ret = curl_exec($ch);
		curl_close($ch);
		return json_decode($ret, true);
	}
}