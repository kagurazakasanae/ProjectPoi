<?php
/*
Shokaku PHP Framework V2
A simple PHP Framework

Framework.Security.php
Created:2015-9-16 17:06:50
*/
if(!defined('IN_FRAMEWORK')){ die; }

class Security{
	static function GetIP(){
		if(getenv("HTTP_CLIENT_IP"))
			$ip = getenv("HTTP_CLIENT_IP");
		elseif(getenv("HTTP_X_FORWARDED_FOR"))
			$ip = explode(',',getenv("HTTP_X_FORWARDED_FOR"))[0];
		elseif(getenv("REMOTE_ADDR"))
			$ip = getenv("REMOTE_ADDR");
		if(Self::Valid($ip,'IP'))
			return $ip;
		else return false;
	}
	static function Valid($P,$R){
		switch($R){
			case "IP":
				return filter_var($P,FILTER_VALIDATE_IP);
			break;
			case "EMAIL":
				return filter_var($P,FILTER_VALIDATE_EMAIL);
			break;
			case "URL":
				return filter_var($P,FILTER_VALIDATE_URL);
			break;
		}
	}
	static function MakeHash($action){
		$hash = md5(RandChar(12));
		if(isset($_SESSION['hash'][$action]) && is_array($_SESSION['hash'][$action]) && $_SESSION['hash'][$action]['ip'] == Self::GetIP()){
			return $_SESSION['hash'][$action]['hash'];
		}
		$_SESSION['hash'][$action]['hash'] = $hash;
		$_SESSION['hash'][$action]['action'] = $action;
		$_SESSION['hash'][$action]['ip'] = Self::GetIP();
		return $hash;
	}
	static function ChkHash($action,$hash){
		if(isset($_SESSION['hash'][$action]) && is_array($_SESSION['hash'][$action])){
			if($_SESSION['hash'][$action]['hash'] == $hash && $_SESSION['hash'][$action]['action'] == $action && $_SESSION['hash'][$action]['ip'] == Self::GetIP()){
				unset($_SESSION['hash'][$action]);
				return true;
			}else{
				return false;
			}
		}
	}
	static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
		if ($operation == 'DECODE') {
			$string = str_replace('[a]', '+', $string);
			$string = str_replace('[b]', '&', $string);
			$string = str_replace('[c]', '/', $string);
		}
		$ckey_length = 4;
		$key = md5($key ? $key : 'Framework');
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
		$cryptkey = $keya . md5($keya . $keyc);
		$key_length = strlen($cryptkey);
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
		$string_length = strlen($string);
		$result = '';
		$box = range(0, 255);
		$rndkey = array();
		for ($i = 0;$i <= 255;$i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		for ($j = $i = 0;$i < 256;$i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		for ($a = $j = $i = 0;$i < $string_length;$i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result.= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		if ($operation == 'DECODE') {
			if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			$ustr = $keyc . str_replace('=', '', base64_encode($result));
			$ustr = str_replace('+', '[a]', $ustr);
			$ustr = str_replace('&', '[b]', $ustr);
			$ustr = str_replace('/', '[c]', $ustr);
			return $ustr;
		}
	}
}  