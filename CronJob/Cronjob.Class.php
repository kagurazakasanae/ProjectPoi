<?php
require 'PDO.Class.php';

class Cronjob{

	private $conntype;
	private $redis;
	private $config;
	private $mysql;
	
	//构造，连接redis和mysql
	public function __construct(){
		$this -> config = require('config.php');
		$this -> redis = new Redis;
		$this -> redis -> connect($this -> config['REDIS_SERVER'], $this -> config['REDIS_PORT']);
		$this -> redis -> auth($this -> config['REDIS_PASSWD']); 
		$this -> mysql = new Mysql($this -> config['MYSQL_SERVER'], $this -> config['MYSQL_DB'], $this -> config['MYSQL_USER'], $this -> config['MYSQL_PASS']);
	}
	//辣鸡redis
	public function redis_safe_get($key){
		return $this -> redis -> get($key);
	}
	//统计信息
	//30s
	//与支付进行互斥锁
	public function syncData(){
		echo 'syncdata'."\n";
		while(true){
			//等锁释放
			if(!$this -> redis -> exists('system-inpay')){
				break;
			}
			sleep(1);
		}
		echo 'setlock'."\n";
		$this -> redis -> setex('system-insync', time() + 30, 'insync');
		//同步站点数据
		for($i=0;$i<6;$i++){
			$sites = $this -> redis -> keys('site-*');
			if($sites){
				break;
			}
		}
		$tmpsites = array();
		if($sites){
			foreach($sites as $s){
				$s = str_replace('site-','',$s);
				$tmpsites[$s] = json_decode($this -> redis_safe_get('site-'.$s), true);	//格式site-key 内容JSON储存 0为总hash 1为上次提交时间 2为siteid
			}
			foreach($tmpsites as $k => $v){
				if(!$v){
					continue;
				}
				$siteinfo = $this -> mysql -> select('sites', array('site_key'=>$k))[0];
				if($siteinfo['hashes'] > $v[0]){
					file_put_contents('errorlog.txt', 'key: '.$k.' ori_hashes: '.$siteinfo['hashes'].' attempt_new: '.$v[0]."\n", FILE_APPEND);
				}
				$this -> mysql -> update('sites', array('hashes'=>$v[0], 'last_hashes'=>$siteinfo['hashes'],'speed'=>intval(($v[0]-$siteinfo['hashes'])/30)), array('site_key'=>$k));
			}
		}
		echo 'siteupdated'."\n";
		//同步站点用户列表
		for($i=0;$i<6;$i++){
			$users = $this -> redis -> keys('siteuser-*');
			if($users){
				break;
			}
		}
		$tmpuser = array();
		if($users){
			foreach($users as $u){
				$sid = explode('-', substr($u, 9))[0];
				$username = substr($u, strlen($sid)+10);
				//echo "SID: {$sid} Username: {$username} Time: ".time()."\n";
				//格式siteuser-siteid-username 内容JSON储存 0为总hash 1为上次提交时间
				$uinfo = json_decode($this -> redis_safe_get($u), true);
				//echo "S2: ".time()."\n";
				if($this -> mysql -> confirm('site_users', array('sid'=>$sid, 'username'=>$username))){
					//echo "S3: ".time()."\n";
					$this -> mysql -> update('site_users', array('hashes'=>$uinfo[0], 'lastsubmit'=>$uinfo[1]),array('sid'=>$sid, 'username'=>$username));
					//echo "S4: ".time()."\n";
				}else{
					//echo "S3: ".time()."\n";
					$uid = $this -> mysql -> select('sites', array('sid'=>$sid))[0]['uid'];
					$this -> mysql -> add('site_users', array('sid'=>$sid, 'uid'=>$uid, 'lastsubmit'=>$uinfo[1], 'username'=>$username,'hashes'=>$uinfo[0]));
					//echo "S4: ".time()."\n";
				}
			}
		}
		echo 'userupdated'."\n";
		$sites = $this -> mysql -> execute("SELECT * FROM sites WHERE hashes > 0") -> fetchAll(PDO::FETCH_ASSOC);
		$updatestore = array();
		if($sites){
			foreach($sites as $s){
				if(isset($updatestore[$s['uid']])){
					$updatestore[$s['uid']] += $s['hashes'];
				}else{
					$updatestore[$s['uid']] = $s['hashes'];
				}
			}
			foreach($updatestore as $k => $v){
				$this -> mysql -> update('users', array('total_hashes'=>$v), array('uid'=>$k));
			}
		}
		echo 'hashesupdated'."\n";
		/*$users = $this -> mysql -> select('users');
		if($users){
			foreach($users as $u){
				$sites = $this -> mysql -> select('sites', array('uid'=>$u['uid']));
				$hashes = 0;
				foreach($sites as $s){
					$hashes += $s['hashes'];
				}
				$this -> mysql -> update('users', array('total_hashes'=>$hashes), array('uid'=>$u['uid']));
			}
		}*/
            echo 'del lock';
		$this -> redis -> del('system-insync');
		//信息同步
	}
	//统计下hour avrg
	//每整点
	public function hour_avrg(){
		$users = $this -> mysql -> select('users');
		foreach($users as $u){
			$hourjson = json_decode($u['hour_avrg'], true);
			$avrg = round(($u['total_hashes'] - $u['last_total']) / 3600, 5);
			if($hourjson){
				if(count($hourjson) > 167){
					array_shift($hourjson);
				}
				$hourjson[] = array(time(), $avrg);
			}else{
				$hourjson[] = array(time(), $avrg);
			}
			$this -> mysql -> update('users', array('last_total'=>$u['total_hashes'], 'hour_avrg'=>json_encode($hourjson)), array('uid'=>$u['uid']));
		}
	}
	//更新难度信息
	//每10分钟
	public function updateDiff(){
		$data = json_decode(file_get_contents('http://moneroblocks.info/api/get_stats'),true);
		$info = json_encode(array('diff'=>$data['difficulty'], 'block_reward'=>$data['last_reward']/1000000000000, 'payout'=>0.9, 'last_update'=>$data['last_timestamp']));
		$this -> mysql -> update('system', array('value'=>$info), array('key'=>'xmr_info'));
	}
	//进行支付
	//每两小时整点
	public function payXMR(){
		while(true){
			//等锁释放
			if(!$this -> redis -> exists('system-insync')){
				break;
			}
			sleep(1);
		}
		$this -> redis -> setex('system-inpay', time() + 45, 'inpay');	//上锁
		$users = $this -> mysql -> select('users', array('ban' => ''));
		$xmrdata = json_decode($this -> mysql -> select('system', array('key'=>'xmr_info'))[0]['value'], true);
		$paymentmin = $this -> mysql -> select('system', array('key'=>'payment_min'))[0]['value'];
		$payarray = array();
		foreach($users as $u){
			if($pinfo = json_decode($u['payment_config'], true)){
				$penddinghashes = $u['total_hashes'] - $u['paid_hashes'];
				$penddingxmr = ($penddinghashes / $xmrdata['diff']) * $xmrdata['block_reward'] * $xmrdata['payout'];
				if($pinfo['minpayout'] >= $paymentmin && $penddingxmr >= $pinfo['minpayout']){
					$address = trim($pinfo['address']);
					if(substr($address, 0, 1) != '4' || !preg_match('/([0-9]|[A-B])/', substr($address, 1)) || strlen($address) != 95){
						continue;
					}else{
						if($penddingxmr < 0.3){
							$penddingxmr -= 0.01;
						}
						$xmrinatom = ceil($penddingxmr * 1000000000000);
						$payarray[] = array('uid' => $u['uid'], 'paidhashes' => $u['total_hashes'], 'paidxmr' => $u['paid_xmr'] + $penddingxmr, 'xmrinatom' => $xmrinatom, 'address' => $address, 'thishashes' => $penddinghashes, 'thisxmr' => $penddingxmr);
					}
				}
			}
		}
		$send = array();
		foreach($payarray as $sp){
			$send[] = array('amount' => $sp['xmrinatom'], 'address' => $sp['address']);
			echo "Amount: ".$sp['xmrinatom']." Address: ".$sp['address']."\n";
		}
		if(count($send) > 0){
			$data = array("jsonrpc" => "2.0", "id" => "0", "method" => "transfer", "params" => array("destinations" => $send), 'mixin' => 0, 'get_tx_key' => false, 'priority' => 1);
			$data_string = json_encode($data);
			$ch = curl_init('http://104.236.168.31:8899/json_rpc');
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
			curl_setopt($ch, CURLOPT_USERPWD, 'ppoiwallet:KwLnuaxajga36AUz');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
			$result = curl_exec($ch);
			curl_close($ch);
			var_dump($result);
			if($txres = json_decode($result, true)){
				if(isset($txres['result']['tx_hash'])){
					foreach($payarray as $sp){
						$this -> mysql -> update('users', array('paid_hashes' => $sp['paidhashes'], 'paid_xmr' => $sp['paidxmr']), array('uid'=>$sp['uid']));
						$this -> mysql -> add('payment_history', array('uid' => $sp['uid'], 'time' => time(), 'hashes' => $sp['thishashes'], 'xmr' => $sp['thisxmr'], 'tran_id' => $txres['result']['tx_hash']));
					}
				}
			}
		}
		$this -> redis -> del('system-inpay');
	}
	//清理无用token
	//每半小时
	public function cleanToken(){
		/*$tokens = $this -> redis -> keys('tokens-*');
		foreach($tokens as $t){
			if(time() - json_decode($this -> redis -> get($t), true)[3] > 3600){
				$this -> redis -> del($t);
			}
		}*/
	}
	//判断sitekey是否存在
	public function chkSitekey($key){
		if(!$this -> redis -> exists('site-'.$key)){
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
		$this -> redis -> set('tokens-'.$token, json_encode(array($sitekey, $token, 0, time())));	//格式 sitekey token hashes createtime
		return $token;
	}
	//根据sitekey获取siteid
	public function getSidBySitekey($key){
		//能调用到这个函数的时候肯定已经把站点写入缓存了 不用从mysql拉了
		if($res = $this -> redis -> get('site-'.$key)){
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
	public function addHash($sitekey, $type, $token=null, $user=null){
		$siteinfo = json_decode($this -> redis -> get('site-'.$sitekey), true);
		$siteinfo[0] += 256;	//添加一个基本单位
		$siteinfo[1] = time();	//time.now
		$this -> redis -> set('site-'.$sitekey, json_encode($siteinfo));	//写回
		if($type == 'token'){
			//token型
			$tokeninfo = json_decode($this -> redis -> get('tokens-'.$token), true);
			$tokeninfo[2] += 256;	//2是hash 加一个基本单位
			$this -> redis -> set('tokens-'.$token, json_encode($tokeninfo));	//设定
		}elseif($token == 'user'){
			$userinfo = json_decode($this -> redis -> get('site-'.$sid.'-'.$username), true);
			$userinfo[0] += 256;
			$userinfo[1] = time();
			$this -> redis -> set('site-'.$sid.'-'.$username, json_encode($userinfo));	//set
		}
	}
}