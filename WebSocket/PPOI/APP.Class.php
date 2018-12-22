<?php
require 'PDO.Class.php';

class APP{

	private $conntype;
	private $redis;
	private $config;
	private $mysql;
	
	//构造，连接redis和mysql
	public function __construct(){
		echo "Loading...\n";
		$this -> config = require('config.php');
		$this -> redis = new Redis;
		$this -> redis -> connect($this -> config['REDIS_SERVER'], $this -> config['REDIS_PORT']);
		$this -> redis -> auth($this -> config['REDIS_PASSWD']); 
		echo "Redis Connected\n";
		$this -> mysql = new Mysql($this -> config['MYSQL_SERVER'], $this -> config['MYSQL_DB'], $this -> config['MYSQL_USER'], $this -> config['MYSQL_PASS']);
		echo "MySQL Connected\n";
		//$this -> loadConfig();
	}
	public function redis_safe_get($key){
		for($i=0;$i<10;$i++){
			$r = $this -> redis -> get($key);
			if($r){
				return $r;
			}
			usleep(150000);
		}
		return false;
	}
	private function syncSitelisttoRedis(){
		$sites = $this -> mysql -> select('sites');
		$ret = array();
		if($sites){
			foreach($sites as $s){
				//拉取MySQL中的站点信息来储存
				//格式site-key 内容JSON储存 0为总hash 1为上次提交时间 2为siteid
				$this -> redis -> set('site-'.$s['site_key'], json_encode(array($s['hashes'], '0', $s['sid'])));
				$this -> redis -> set('siteid-'.$s['sid'], $s['site_key']);	//同时维护一个反向查找
				$ret[] =  $s['site_key'];
			}
			return $ret;
		}else{
			return false;
		}
	}
	//初次启动，将mysql中的信息同步到redis
	public function loadConfig(){
		//同步站点列表
		$this -> syncSitelisttoRedis();
		echo "Sitelist Synced\n";
		//同步站点用户列表
		//为了性能起见只同步最近活跃的1000用户，其他需要再拉
		$site_users = $this -> mysql -> select('site_users',array(), 'lastsubmit DESC', '1000');
		if($site_users){
			foreach($site_users as $s){
				//格式siteuser-siteid-username 内容JSON储存 0为总hash 1为上次提交时间
				if(!$s['sid'] || !$s['username']){
					continue;
				}
				$this -> redis -> set('siteuser-'.$s['sid'].'-'.$s['username'], json_encode(array($s['hashes'], $s['lastsubmit'])));
			}
		}
		echo "Siteuser Synced\n";
		//同步矿池信息
		$pools = json_decode($this -> mysql -> select('system', array('key'=>'xmr_pools'))[0]['value'], true);
		foreach($pools as $p){
			if($p['Choose']){
				$this -> redis -> set('system-pool', json_encode($p));
				break;
			}
		}
		echo "Pooldata Synced\n";
		//信息同步
	}
	
	public function addReject($sitekey){
		if($this -> redis -> exists('reject-'.$sitekey)){
			$count = $this -> redis -> get('reject-'.$sitekey);
			$this -> redis -> set('reject-'.$sitekey, $count);
			if($count >= 50){
				$uid = $this -> mysql -> select('sites', array('site_key' => $sitekey))[0]['uid'];
				$this -> mysql -> update('users', array('ban' => '账户异常, 请等待调查(如正常会在24小时内解除)'), array('uid' => $uid));
			}
		}else{
			$this -> redis -> set('reject-'.$sitekey, 1);
		}
	}
	
	public function chkReject($sitekey){
		if($this -> redis -> exists('reject-'.$sitekey)){
			$count = $this -> redis -> get('reject-'.$sitekey);
			if($count >= 50){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	//获得站点列表
	public function getSitelist(){
		//echo "Fetching sitelist\n";
		$ret = $this -> syncSitelisttoRedis();
		//echo "Sitelist returned\n";
		return $ret;
	}
	private function getSitekeybySid($sid){
		return $this -> redis -> get('siteid-'.$sid);
	}
	//取得用户列表
	public function getSiteUsetList(){
		for($i=0;$i<6;$i++){
			$users = $this -> redis -> keys('siteuser-*');
			if($users){
				break;
			}
		}
		if(!$users){
			echo "Can not fetch any users\n";
			return false;
		}
		$ret = array();
		foreach($users as $u){
			$sid = explode('-', substr($u, 9))[0];
			$username = substr($u, strlen($sid)+10);
			$uinfo = json_decode($this -> redis_safe_get($u), true);
			$sitekey = $this -> getSitekeybySid($sid);
			$ret[$sitekey.'<>'.$username] = array(0, $uinfo[0]);
		}
		echo "Userlist returned\n";
		return $ret;
	}
	//判断sitekey是否存在
	public function chkSitekey($key){
		$exists = false;
		for($i=0;$i<8;$i++){
			if($this -> redis -> exists('site-'.$key)){
				$exists = true;
				break;
			}
		}
		if(!$exists){
			//redis不存在,去mysql查
			$res = $this -> mysql -> select('sites', array('site_key'=>$key));
			if(!$res){
				return false;	//tan90
			}
			//存在则同步过去
			$this -> redis -> set('site-'.$res[0]['site_key'], json_encode(array($res[0]['hashes'], '0', $res[0]['sid'])));
			return true;
		}else{
			return true;
		}
	}
	//创建新token
	public function genToken($sitekey){
		$token = randChar(24);
		$this -> redis -> setex('tokens-'.$token, 3600, json_encode(array($sitekey, $token, 0, time())));	//格式 sitekey token hashes createtime
		return $token;
	}
	//根据sitekey获取siteid
	public function getSidBySitekey($key){
		$this -> chkSitekey($key);
		if($res = $this -> redis_safe_get('site-'.$key)){
			return json_decode($res, true)[2];
		}else{
			return false;
		}
	}
	//获取指定user的信息
	public function getUserInfo($sitekey, $username){
		if($sid = $this -> getSidBySitekey($sitekey)){
			if($res = $this -> redis -> get('siteuser-'.$sid.'-'.$username)){
				return json_decode($res, true);
			}
		}
		return false;
	}
	//为指定site添加新用户
	public function addNewUser($sitekey, $username){
		if($sid = $this -> getSidBySitekey($sitekey)){
			//储存格式 0为总hash 1为上次提交时间
			$this -> redis -> set('siteuser-'.$sid.'-'.$username, json_encode(array(0, 0)));
		}
	}
	//获取矿池信息
	public function getPoolData(){
		return json_decode($this -> redis -> get('system-pool'), true);
	}
	//添加hash 矿池接受之后调用
	public function addHash($sitekey, $type, $token=null, $username=null){
		//$this -> chkSitekey($sitekey);
		//echo "addHash {$sitekey} {$num}\n";
		for($i=0;$i<5;$i++){
			$siteinfo = json_decode($this -> redis -> get('site-'.$sitekey), true);
			if($siteinfo){
				break;
			}
			usleep(100000);
		}
		if($siteinfo){
			$siteinfo[0] += 128;	//添加一个基本单位
			$siteinfo[1] = time();	//time.now
			//var_dump($siteinfo);
			$this -> redis -> set('site-'.$sitekey, json_encode($siteinfo));	//写回
			if($type == 'token'){
				//token型
				$tokeninfo = json_decode($this -> redis_safe_get('tokens-'.$token), true);
				$tokeninfo[2] += 128;	//2是hash 加一个基本单位
				$this -> redis -> set('tokens-'.$token, json_encode($tokeninfo));	//设定
			}elseif($type == 'user'){
				$sid = $this -> getSidBySitekey($sitekey);
				$userinfo = json_decode($this -> redis -> get('siteuser-'.$sid.'-'.$username), true);
				if(!$userinfo){
					$this -> addNewUser($sitekey, $username);
					$userinfo = array();
				}
				@$userinfo[0] += 128;
				$userinfo[1] = time();
				$this -> redis -> set('siteuser-'.$sid.'-'.$username, json_encode($userinfo));	//set
			}
		}else{
			echo "Cannot get sitekey(addHash) info\nSitekey: {$sitekey}\n";
		}
	}
}