<?php
if(!defined('IN_FRAMEWORK')){ die; }

class User{
	
	private $Configs;
	private $m;
	
	public function __construct($configs){
		$this -> Configs = $configs;
		$this -> m =  M("Mysql");
	}
	
	public static function isLogin($sitekey){
		if(isset($_COOKIE['session'])){
			if($sess = json_decode($_COOKIE['session'], true)){
				$ip = Security::GetIP();
				if(!$ip){
					throw new Exception('Can not get your IP');
				}
				if(!is_array($sess['n']) && !is_array($sess['pp']) && $sess['id'] === md5($sess['n'].$sess['pp'].$sitekey) && sprintf('%u', ip2long($ip)) === $sess['pp']){
					return $sess['n'];
				}
			}
			return false;
		}
	}
	
	public function loginInUser($email, $password){
		$m = $this -> m;
		$ip = Security::GetIP();
		if(!$ip){
			throw new Exception('Can not get your IP');
		}
		if($data = $m -> select('users', array('email'=>$email, 'password' => md5(md5($password).$this -> Configs['SITE_KEY'])))){
			if($data[0]['ban'] == ''){
				setcookie('session',json_encode(array('n'=>$email, 'id'=>md5($email.sprintf('%u', ip2long(Security::GetIP())).$this -> Configs['SITE_KEY']), 'pp'=>sprintf('%u', ip2long($ip)))), time() + (86400 * 7), '/');
				return true;
			}else{
				return $data[0]['ban'];
			}
		}else{
			return false;
		}
	}
	
	public function getUserinfo($email){
		$m = $this -> m;
		return $m -> select('users', array('email' => $email))[0];
	}
	
	public function regUser($email, $password){
		$m = $this -> m;
		if($m -> confirm('users', array('email'=>$email))){
			return false;
		}else{
			$ip = Security::GetIP();
			if(!$ip){
				throw new Exception('Can not get your IP');
			}
			$m -> add('users', array('email'=>$email, 'password' => md5(md5($password).$this -> Configs['SITE_KEY']), 'api_key' => RandChar(24), 'is_validated' => '0', 'reg_date' => time(), 'reg_ip' => sprintf('%u', ip2long($ip)), 'payment_config' => '', 'paid_xmr' => '0', 'paid_hashes' => '0', 'total_hashes' => '0', 'last_total' => '0', 'hour_avrg' => ''));
			$uid = $m -> select('users', array('email'=>$email))[0]['uid'];
			$m -> add('sites', array('uid'=>$uid, 'site_name'=>'你的站点', 'site_key'=>RandChar(24), 'hashes'=>'0', 'last_hashes'=>'0', 'speed'=>'0'));
			$activelink = $this -> Configs['SITE_URL'].'/account/verify?token='.Security::authcode($email, 'ENCODE', $this -> Configs['SITE_KEY'], 86400);
			$mailcontent = '您好，感谢您注册ProjectPoi。<br />您的验证链接为: <a href="'.$activelink. '">'.$activelink.'</a>注意：该链接有效期只有1天。';
			Import('PHPMailer');
			Import('SMTP');
			$mail = new PHPMailer(false);
			$mail -> isSMTP();
			$mail -> Host = $this -> Configs['SMTP_SERVER'];
			$mail -> SMTPAuth = true;
			$mail -> Username = $this -> Configs['SMTP_USER'];
			$mail -> Password = $this -> Configs['SMTP_PASS'];
			$mail -> SMTPSecure = 'tls';
			$mail -> Port = 587;
			$mail -> setFrom($this -> Configs['SMTP_USER'], 'ProjectPoi');
			$mail -> addAddress($email);
			$mail -> isHTML(true);
			$mail -> Subject = 'ProjectPoi 邮箱地址验证';
			$mail -> Body = $mailcontent;
			$mail -> send();
			return true;
		}
	}
	
	public function sendResetLink($email){
		$resetlink = $this -> Configs['SITE_URL'].'/account/reset-password?token='.Security::authcode('ResetPassword***'.$email, 'ENCODE', $this -> Configs['SITE_KEY'], 7200);
		$mailcontent = '您好,<br />要重设您的密码，请点击以下链接: <a href="'.$resetlink. '">'.$resetlink.'</a><br />注意：该链接有效期只有2小时。<br />如果您没有请求重设密码，那么可能是有人在尝试进入您的账户，请注意您的账户安全。<br />请求IP: '.Security::GetIP();
		Import('PHPMailer');
		Import('SMTP');
		$mail = new PHPMailer(false);
          //  $mail -> SMTPDebug = 2; 
		$mail -> isSMTP();
		$mail -> Host = $this -> Configs['SMTP_SERVER'];
		$mail -> SMTPAuth = true;
		$mail -> Username = $this -> Configs['SMTP_USER'];
		$mail -> Password = $this -> Configs['SMTP_PASS'];
		$mail -> SMTPSecure = 'tls';
		$mail -> Port = 587;
		$mail -> setFrom($this -> Configs['SMTP_USER'], 'ProjectPoi');
		$mail -> addAddress($email);
		$mail -> isHTML(true);
		$mail -> Subject = 'ProjectPoi 重设密码';
		$mail -> Body = $mailcontent;
		$mail -> send();
		return true;
	}
	
	public function verifyUser($email){
		$m = $this -> m;
		$m -> update('users', array('is_validated' => '1'), array('email' => $email));
		return true;
	}
	
	public function updatePassword($email, $password){
		$m = $this -> m;
		$m -> update('users', array('password' => md5(md5($password).$this -> Configs['SITE_KEY'])), array('email' => $email));
		return true;
	}
	
	public function updatePayment($email, $payment){
		$m = $this -> m;
		$m -> update('users', array('payment_config' => $payment), array('email' => $email));
		return true;
	}
	
	public function updateEmail($email, $newemail){
		$m = $this -> m;
		$m -> update('users', array('email' => $newemail, 'is_validated' => '0'), array('email' => $email));
		$activelink = $this -> Configs['SITE_URL'].'/account/verify?token='.Security::authcode($email, 'ENCODE', $this -> Configs['SITE_KEY'], 86400);
		$mailcontent = '您好，您在ProjectPoi上更改了新的邮箱。<br />您的验证链接为: <a href="'.$activelink. '">'.$activelink.'<br />注意：该链接有效期只有1天。';		
		Import('PHPMailer');
		Import('SMTP');
		$mail = new PHPMailer(false);
		$mail -> isSMTP();
		$mail -> Host = $this -> Configs['SMTP_SERVER'];
		$mail -> SMTPAuth = true;
		$mail -> Username = $this -> Configs['SMTP_USER'];
		$mail -> Password = $this -> Configs['SMTP_PASS'];
		$mail -> SMTPSecure = 'tls';
		$mail -> Port = 587;
		$mail -> setFrom($this -> Configs['SMTP_USER'], 'ProjectPoi');
		$mail -> addAddress($email);
		$mail -> isHTML(true);
		$mail -> Subject = 'ProjectPoi 邮箱地址验证';
		$mail -> Body = $mailcontent;
		$mail -> send();
	}
	
	public function updateAPIKey($email){
		$m = $this -> m;
		$m -> update('users', array('api_key' => RandChar(24)), array('email' => $email));
		return true;
	}
	
}