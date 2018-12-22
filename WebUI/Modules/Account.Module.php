<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');
ImportModule('User');

class Account extends Init{
	
	public function Loader(){
		$uri = D('REWRITE',array('[String]0'=>'','[String]1'=>''),32);
		if($uri[0] == 'account'){
			$this -> accountLoader($uri[1]);
			return true;
		}elseif($uri[1] == 'settings'){
			$this -> settingLoader($uri[1]);
			return true;
		}
	}
	
	private function accountLoader($action){
		switch($action){
			case "login":
				$this -> accountLogin();
			break;
			case "signup":
				$this -> accountSignup();
			break;
			case "logout":
				$this -> accountLogout();
			break;
			case "verify":
				$this -> accountVerify();
			break;
			case "request-password-reset":
				$this -> accountResetPwd();
			break;
			case "reset-password":
				$this -> accountDoResetPwd();
			break;
			default:
				parent::outNotfound();
			break;
		}
		return true;
	}
	
	private function accountLogout(){
		if(isset($_GET['nonce']) && !is_array($_GET['nonce']) && $_GET['nonce'] == substr(json_decode($_COOKIE['session'], true)['id'],0,16)){
			setcookie('session','',time()-86400,'/');
			header("Location: /");
		}else{
			parent::outError('链接有误', '您的请求链接无效');
		}
		return true;
	}
	
	private function accountLogin(){
		if($this -> User -> isLogin($this -> Configs['SITE_KEY'])){
			header("Location: /dashboard");
			return true;
		}
		if(isGet()){
			$this -> XT -> setdata('notice', '');
			$this -> XT -> setdata('thistitle', '登录');
			$this -> XT -> parse('login');
			H('normal');
			$this -> XT -> out();
			return true;
		}else{
			$logininfo = D('POST', array('[String]email'=>'','[String]password'=>'','[String]projectpoi-captcha-token'=>''), 64);
			if(trim($logininfo['email']) == '' || trim($logininfo['password']) == '' || trim($logininfo['projectpoi-captcha-token']) == '' || !Security::Valid($logininfo['email'],'EMAIL')){
				$this -> XT -> setdata('notice', '请检查您的信息是否正确');
				$this -> XT -> setdata('thistitle', '登录');
				$this -> XT -> parse('login');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			
			if(!parent::verifyToken($logininfo['projectpoi-captcha-token'], 512)['success']){
				$this -> XT -> setdata('notice', '验证码验证失败，请重试');
				$this -> XT -> setdata('thistitle', '登录');
				$this -> XT -> parse('login');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			
			$res = $this -> User -> loginInUser($logininfo['email'], $logininfo['password']);
			if($res !== true){
				if($res === false){
					$this -> XT -> setdata('notice', '用户名或密码错误');
				}else{
					$this -> XT -> setdata('notice', '您无法登录: ' . $res);
				}
				
				$this -> XT -> setdata('thistitle', '登录');
				$this -> XT -> parse('login');
				H('normal');
				$this -> XT -> out();
				return true;
			}else{
				header("Location: /dashboard");
				return true;
			}
		}
	}
	
	private function accountSignup(){
		if($this -> User -> isLogin($this -> Configs['SITE_KEY'])){
			header("Location: /dashboard");
			return true;
		}
		if(isGet()){
			$this -> XT -> setdata('notice', '');
			$this -> XT -> setdata('thistitle', '注册');
			$this -> XT -> parse('signup');
			H('normal');
			$this -> XT -> out();
			return true;
		}else{
			$reginfo = D('POST', array('[String]email'=>'','[String]password'=>'','[String]projectpoi-captcha-token'=>''), 64);
			if(trim($reginfo['email']) == '' || trim($reginfo['password']) == '' || trim($reginfo['projectpoi-captcha-token']) == '' || !Security::Valid($reginfo['email'],'EMAIL')){
				$this -> XT -> setdata('notice', '请检查您的信息是否正确');
				$this -> XT -> setdata('thistitle', '注册');
				$this -> XT -> parse('signup');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			
			if(!parent::verifyToken($reginfo['projectpoi-captcha-token'], 512)['success']){
				$this -> XT -> setdata('notice', '验证码验证失败，请重试');
				$this -> XT -> setdata('thistitle', '注册');
				$this -> XT -> parse('signup');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			
			if(strlen(trim($reginfo['password'])) < 8){
				$this -> XT -> setdata('notice', '密码最少为8位');
				$this -> XT -> setdata('thistitle', '注册');
				$this -> XT -> parse('signup');
				H('normal');
				$this -> XT -> out();
				return true;
			}			
			if($this -> User -> regUser($reginfo['email'], $reginfo['password'])){
				$this -> XT -> setdata('notice', '注册成功，请检查来自我们的邮件并点击其中链接验证您的邮箱。');
				$this -> XT -> setdata('thistitle', '注册');
				$this -> XT -> parse('signup');
				H('normal');
				$this -> XT -> out();
				return true;
			}else{
				$this -> XT -> setdata('notice', '该邮箱已存在，如忘记密码请选择找回密码。');
				$this -> XT -> setdata('thistitle', '注册');
				$this -> XT -> parse('signup');
				H('normal');
				$this -> XT -> out();
				return true;
			}
		}
	}
	
	private function accountVerify(){
		$this -> XT -> setdata('thistitle', '验证邮箱');
		if(!isset($_GET['token']) || is_array($_GET['token']) || trim($_GET['token']) == '' || stristr(fileCacheGet('emailverifytoken'), $_GET['token']) || !$email = Security::authcode($_GET['token'], 'DECODE', $this -> Configs['SITE_KEY'])){
			$this -> XT -> setdata('messagetitle', '链接失效');
			$this -> XT -> setdata('messagecontent', '您的链接已失效');
			$this -> XT -> parse('message');
			H('normal');
			$this -> XT -> out();
			return true;
		}
		$this -> User -> verifyUser($email);
		fileCachePut('emailverifytoken', $_GET['token']);
		$this -> XT -> setdata('messagetitle', '邮箱验证');
		$this -> XT -> setdata('messagecontent', '您的邮箱已成功验证');
		$this -> XT -> parse('message');
		H('normal');
		$this -> XT -> out();
		return true;
	}
	
	private function accountResetPwd(){
		if($this -> User -> isLogin($this -> Configs['SITE_KEY'])){
			header("Location: /dashboard");
			return true;
		}
		H('normal');
		if(isGet()){
			$this -> XT -> setdata('thistitle', '找回密码');
			$this -> XT -> parse('requestresetpasswd');
			H('normal');
			$this -> XT -> out();
			return true;
		}else{
			$reqinfo = D('POST', array('[String]email'=>'','[String]projectpoi-captcha-token'=>''), 64);
			if(trim($reqinfo['email']) == '' || trim($reqinfo['projectpoi-captcha-token']) == '' || !Security::Valid($reqinfo['email'],'EMAIL')){
				$this -> XT -> setdata('notice', '请检查您的信息是否正确');
				$this -> XT -> setdata('thistitle', '找回密码');
				$this -> XT -> parse('requestresetpasswd');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			if(!parent::verifyToken($reqinfo['projectpoi-captcha-token'], 512)['success']){
				$this -> XT -> setdata('notice', '验证码验证失败，请重试');
				$this -> XT -> setdata('messagetitle', '验证码验证失败');
				$this -> XT -> setdata('messagecontent', '验证码验证失败，请重试');
				$this -> XT -> setdata('thistitle', '找回密码');
				$this -> XT -> parse('message');
				H('normal');
				$this -> XT -> out();
				return true;
			}
			if($this -> User -> getUserinfo($reqinfo['email'])['is_validated'] == '1'){
				$this -> User -> sendResetLink($reqinfo['email']);
			}
			$this -> XT -> setdata('thistitle', '找回密码');
			$this -> XT -> setdata('messagetitle', '找回密码');
			$this -> XT -> setdata('messagecontent', '如果您的账户存在且邮箱通过验证您应当会收到一封包含重设密码链接的邮件。');
			$this -> XT -> parse('message');
			H('normal');
			$this -> XT -> out();
			return true;
		}
	}
	
	private function accountDoResetPwd(){
		H('normal');
		if(isGet()){
			$this -> XT -> setdata('thistitle', '找回密码');
			if((!isset($_GET['token']) || is_array($_GET['token']) || trim($_GET['token']) == '' || stristr(fileCacheGet('passwordToken'), $_GET['token']) || !$email = Security::authcode($_GET['token'], 'DECODE', $this -> Configs['SITE_KEY'])) or !stristr($email, 'ResetPassword***')){
				$this -> XT -> setdata('messagetitle', '链接失效');
				$this -> XT -> setdata('messagecontent', '您的链接已失效');
				$this -> XT -> parse('message');
				$this -> XT -> out();
				return true;
			}
			$email = str_replace('ResetPassword***','',$email);
			$this -> XT -> setdata('email', $email);
			$this -> XT -> setdata('token', $_GET['token']);
			$this -> XT -> parse('resetpasswd');
			$this -> XT -> out();
			return true;
		}else{
			$this -> XT -> setdata('thistitle', '找回密码');
			if((!isset($_POST['token']) || is_array($_POST['token']) || trim($_POST['token']) == '' || stristr(fileCacheGet('passwordToken'), $_POST['token']) || !$email = Security::authcode($_POST['token'], 'DECODE', $this -> Configs['SITE_KEY'])) or !stristr($email, 'ResetPassword***')){
				$this -> XT -> setdata('messagetitle', '链接失效');
				$this -> XT -> setdata('messagecontent', '您的链接已失效');
				$this -> XT -> parse('message');
				$this -> XT -> out();
				return true;
			}
			$email = str_replace('ResetPassword***','',$email);
			if(strlen(trim($_POST['password'])) < 8 || strlen(trim($_POST['password'])) > 32){
				$this -> XT -> setdata('messagetitle', '输入错误');
				$this -> XT -> setdata('messagecontent', '密码最少为8位');
				$this -> XT -> parse('message');
				$this -> XT -> out();
				return true;
			}
			$this -> User -> updatePassword($email, $_POST['password']);
			fileCachePut('passwordToken', $_POST['token']);
			$this -> XT -> setdata('messagetitle', '已重设');
			$this -> XT -> setdata('messagecontent', '您的密码已重设');
			$this -> XT -> parse('message');
			$this -> XT -> out();
			return true;
		}
	}

}