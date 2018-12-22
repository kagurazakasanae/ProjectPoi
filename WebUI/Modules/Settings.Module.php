<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Settings extends Init{
	
	private $email;
	private $userinfo;
	private $m;
	
	public function Loader(){
		$uri = D('REWRITE',array('[String]1'=>''),32)['1'];
		if(!$this -> email = $this -> User -> isLogin($this -> Configs['SITE_KEY'])){
			header("Location: /account/login");
			return false;
		}
		if($uri == ''){
			$this -> showMenu();
			return true;
		}else{
			$m = M('Mysql');
			$this -> userinfo = $m -> select('users', array('email' => $this -> email));
			if($this -> userinfo){
				$this -> userinfo = $this -> userinfo[0];
			}else{
				parent::outError('系统错误', '系统遇到一个未知错误，无法继续处理您的请求。');
				return true;
			}
			$this -> m = $m;
			if($uri == 'account'){
				$this -> handleAccount();
				return true;
			}elseif($uri == 'payments'){
				$this -> handlePayments();
				return true;
			}elseif($uri == 'sites'){
				$this -> handleSites();
				return true;
			}elseif($uri == 'sites-new'){
				$this -> handleNewSites();
				return true;
			}elseif($uri == 'api-revoke-keys'){
				$this -> handleRevokeKey();
				return true;
			}else{
				parent::outNotFound();
				return true;
			}
		}
		
	}
	
	private function showMenu(){
		$this -> XT -> setdata('thistitle', '设置');
		H('normal');
		$this -> XT -> parse('settings');
		$this -> XT -> out();
		return true;
	}
	
	private function handleAccount(){
		$this -> XT -> setdata('thistitle', '账户设置');
		H('normal');
		if(isGet()){
			$this -> XT -> setdata('notice', '');
			$this -> XT -> setdata('currentemail', $this -> email);
			$this -> XT -> parse('settingsaccount');
			$this -> XT -> out();
			return true;
		}else{
			$accinfo = D('POST', array('[String]nonce'=>'','[String]email'=>'','[String]newPassword'=>'','[String]password'=>''), 64);
			if(trim($accinfo['nonce']) == '' || trim($accinfo['password']) == '' || $accinfo['nonce'] != substr(json_decode($_COOKIE['session'], true)['id'],0,16) || !Security::Valid($accinfo['email'], 'EMAIL')){
				parent::outError('请求无效', '您的请求无法被系统处理，请重试。');
				return true;
			}
			
			if(md5(md5($accinfo['password']).$this -> Configs['SITE_KEY']) != $this -> userinfo['password']){
				$this -> XT -> setdata('notice', '您输入的密码与您当前密码不符，请确认。');
				$this -> XT -> setdata('currentemail', $this -> email);
				$this -> XT -> parse('settingsaccount');
				$this -> XT -> out();
				return true;
			}
			if(strlen(trim($accinfo['newPassword'])) < 8){
				$this -> XT -> setdata('notice', '新密码最少为8位字符。');
				$this -> XT -> setdata('currentemail', $this -> email);
				$this -> XT -> parse('settingsaccount');
				$this -> XT -> out();
				return true;
			}
			if(trim($accinfo['newPassword']) != ''){
				$this -> User -> updatePassword($this -> email, trim($accinfo['newPassword']));
			}
			if($accinfo['email'] != $this -> email){
				$this -> User -> updateEmail($this -> email, $accinfo['email']);
			}
			setcookie('session','', time() - 86400, '/');
			$this -> XT -> setdata('notice', '您的更改已保存，请重新登录。');
			$this -> XT -> setdata('thistitle', '登录');
			$this -> XT -> parse('login');
			H('normal');
			$this -> XT -> out();
			return true;
		}
	}
	
	private function handlePayments(){
		$this -> XT -> setdata('thistitle', '支付设置');
		H('normal');
		if(isGet()){
			if($payinfo = json_decode($this -> userinfo['payment_config'], true)){
				$this -> XT -> setdata('currentminpayout', $payinfo['minpayout']);
				$this -> XT -> setdata('currentaddr', $payinfo['address']);
			}else{
				$this -> XT -> setdata('currentaddr', '');
				$this -> XT -> setdata('currentminpayout', '0.3');
			}
			if(isset($_GET['saved'])){
				$this -> XT -> setdata('notice', '更改已保存');
			}else{
				$this -> XT -> setdata('notice', '');
			}
			$this -> XT -> parse('settingspayment');
			$this -> XT -> out();
			return true;
		}else{
			$payinfo = D('POST', array('[String]nonce'=>'','[String]paymentAddress'=>'NOHTML','[String]paymentMinimum'=>'NOHTML','[String]password'=>''));
			$this -> XT -> setdata('currentaddr', $payinfo['paymentAddress']);
			$this -> XT -> setdata('currentminpayout', $payinfo['paymentMinimum']);
			if(md5(md5($payinfo['password']).$this -> Configs['SITE_KEY']) != $this -> userinfo['password']){
				$this -> XT -> setdata('notice', '您输入的密码与您当前密码不符，请确认。');
				$this -> XT -> parse('settingspayment');
				$this -> XT -> out();
				return false;
			}
			if(!is_numeric($payinfo['paymentMinimum'])){
				$this -> XT -> setdata('notice', '您提供的数据不合法，请检查。');
				$this -> XT -> parse('settingspayment');
				$this -> XT -> out();
				return false;
			}
			if($payinfo['paymentMinimum'] < 0.05){
				$this -> XT -> setdata('notice', '最低支付额度为0.05XMR。');
				$this -> XT -> parse('settingspayment');
				$this -> XT -> out();
				return false;
			}
			$this -> User -> updatePayment($this -> email, json_encode(array('address' => $payinfo['paymentAddress'], 'minpayout' => $payinfo['paymentMinimum'])));
			header("Location: /settings/payments?saved");
			return true;
		}
	}
	
	private function handleSites(){
		H('normal');
		if(isGet()){
			if(isset($_GET['saved'])){
				$this -> XT -> setdata('notice', '更改已保存');
			}else{
				$this -> XT -> setdata('notice', '');
			}
			$this -> XT -> setdata('thistitle', '站点 &amp; API 密钥设置');
			$this -> XT -> setdata('apikey', $this -> userinfo['api_key']);
			$sites = $this -> m -> select('sites', array('uid' => $this -> userinfo['uid']));
			$this -> XT -> setdata('sites', $sites);
			$this -> XT -> setdata('allownewsite', count($sites) < 5);
			$this -> XT ->parse('settingsites');
			$this -> XT -> out();
			return true;
		}else{
			$newdata = D('POST', array('[String]nonce'=>''), 32);
			if(trim($newdata['nonce']) == '' || $newdata['nonce'] != substr(json_decode($_COOKIE['session'], true)['id'],0,16) || !is_array($_POST['sites'])){
				parent::outError('请求无效', '您的请求无法被系统处理，请重试。');
				return true;
			}
			foreach($_POST['sites'] as $k => $v){
				$this -> m -> update('sites', array('site_name'=>htmlspecialchars($v)), array('sid'=>str_replace('sid_', '', $k), 'uid' => $this -> userinfo['uid']));
			}
			header("Location: /settings/sites?saved");
			return true;
		}
	}
	
	private function handleNewSites(){
		if(isGet()){
			H('normal');
			$this -> XT -> setdata('thistitle', '添加站点');
			$this -> XT ->parse('sitesnew');
			$this -> XT -> out();
			return true;
		}else{
			$newdata = D('POST', array('[String]name'=>'NOHTML', '[String]nonce'=>''), 64);
			$sitecount = count($sites = $this -> m -> select('sites', array('uid' => $this -> userinfo['uid'])));
			if(trim($newdata['nonce']) == '' || $newdata['nonce'] != substr(json_decode($_COOKIE['session'], true)['id'],0,16) || $sitecount >= 5){
				H('normal');
				parent::outError('请求无效', '您的请求无法被系统处理，请重试。');
				return true;
			}
			$sitename = substr($newdata['name'], 0, 64);
			if(trim($sitename) == ''){
				$sitename = '新加站点';
			}
			$this -> m -> add('sites', array('uid'=>$this -> userinfo['uid'], 'site_name'=>$sitename, 'site_key'=>RandChar(24), 'hashes'=>'0', 'last_hashes'=>'0', 'speed'=>'0'));
			header("Location: /settings/sites?saved");
			return true;
		}
	}
	
	private function handleRevokeKey(){
		if(isGet()){
			H('normal');
			$this -> XT -> setdata('notice', '');
			$this -> XT -> setdata('thistitle', '更换密钥');
			$this -> XT ->parse('revokekey');
			$this -> XT -> out();
			return true;
		}else{
			$newdata = D('POST', array('[String]password'=>'', '[String]nonce'=>''), 64);
			if(trim($newdata['nonce']) == '' || $newdata['nonce'] != substr(json_decode($_COOKIE['session'], true)['id'],0,16)){
				H('normal');
				parent::outError('请求无效', '您的请求无法被系统处理，请重试。');
				return true;
			}
			if(md5(md5($newdata['password']).$this -> Configs['SITE_KEY']) != $this -> userinfo['password']){
				$this -> XT -> setdata('notice', '您输入的密码与您当前密码不符，请确认。');
				$this -> XT -> parse('revokekey');
				$this -> XT -> out();
				return false;
			}
			$this -> User -> updateAPIKey($this -> email);
			header("Location: /settings/sites?saved");
			return true;
		}
	}
	
}