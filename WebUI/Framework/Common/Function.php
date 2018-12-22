<?php
/*
Shokaku PHP Framework V2
A simple PHP Framework

Common.php
Created:2015-9-15 18:41:26
*/
if(!defined('IN_FRAMEWORK')){ die; }

function Import($C){
	if(file_exists(ROOT_PATH.'/Framework/Library/Framework.'.$C.'.php')){
		return require_once(ROOT_PATH.'/Framework/Library/Framework.'.$C.'.php');
	}else{
		throw new Exception("Controller '$C' not exists");
	}
}

function ImportModule($M){
	if(file_exists(ROOT_PATH.'/Modules/'.$M.'.Module.php')){
		return require_once(ROOT_PATH.'/Modules/'.$M.'.Module.php');
	}else{
		throw new Exception("Module '$M' not exists");
	}
}

function M($N){
	Import($N);
	return new $N;
}

function C($C){
	if(file_exists(ROOT_PATH.'/Configs/'.$C.'.Config.php')){
		return require(ROOT_PATH.'/Configs/'.$C.'.Config.php');
	}else{
		throw new Exception("Config file '$C' not exists");
	}
}

function D($Method,array $array,$length=false){
	switch($Method){
		case "GET":
			$Q = $_GET;
		break;
		case "POST":
			$Q = $_POST;
		break;
		case "COOKIE":
			$Q = $_COOKIE;
		break;
		case "REWRITE":
			$Q = $GLOBALS['Requests'];
		break;
	}
	$R = array();
	foreach($array as $k => $v){
		if(stristr($k,'[Int]')){
			$k = str_replace('[Int]','',$k);
			if(isset($Q[$k])){
				if(is_numeric($Q[$k])){
					$Q[$k] = (int)$Q[$k];
				}else{
					throw new Exception("Parameter '$k' must be integer");
				}
			}else{
				$R[$k] = (int)'';
				continue;
			}
		}elseif(stristr($k,'[String]')){
			$k = str_replace('[String]','',$k);
			if(isset($Q[$k])){
				if(is_string($Q[$k])){
					$Q[$k] = (string)$Q[$k];
				}else{
					throw new Exception("Parameter '$k' must be string");
				}
			}else{
				$R[$k] = (string)'';
				continue;
			}
		}elseif(stristr($k,'[Array]')){
			$k = str_replace('[Array]','',$k);
			if(isset($Q[$k])){
				if(is_array($Q[$k])){
					$Q[$k] = (array)$Q[$k];
				}else{
					throw new Exception("Parameter '$k' must be array");
				}
			}else{
				$R[$k] = array();
				continue;
			}
		}
		$filters = explode(',',$v);
		for($i=0;$i<count($filters);$i++){
			switch($filters[$i]){
				case "NOHTML":
					if(is_array($Q[$k])){
						for($t=0;$t<count($Q[$k]);$t++){
							$Q[$k][$t] = htmlspecialchars($Q[$k][$t]);
						}
					}else{
						$Q[$k] = htmlspecialchars($Q[$k]);
					}
				break;
				case "NOTAGS":
					if(is_array($Q[$k])){
						for($t=0;$t<count($Q[$k]);$t++){
							$Q[$k][$t] = strip_tags($Q[$k][$t]);
						}
					}else{
						$Q[$k] = strip_tags($Q[$k]);
					}
				break;
				case "ADDSLASHES":
					if(is_array($Q[$k])){
						for($t=0;$t<count($Q[$k]);$t++){
							$Q[$k][$t] = addslashes($Q[$k][$t]);
						}
					}else{
						$Q[$k] = addslashes($Q[$k]);
					}
				break;
				case "LOWER":
					if(is_array($Q[$k])){
						for($t=0;$t<count($Q[$k]);$t++){
							$Q[$k][$t] = strtolower($Q[$k][$t]);
						}
					}else{
						$Q[$k] = strtolower($Q[$k]);
					}
				break;
				case "PLUS":
					if(is_array($Q[$k])){
						for($t=0;$t<count($Q[$k]);$t++){
							if(is_numeric($Q[$k][$t]) && $Q[$k][$t] < 0){
								$Q[$k][$t] = 0;
							}
						}
					}else{
						if(is_numeric($Q[$k][$t]) && $Q[$k][$t] < 0){
							$Q[$k][$t] = 0;
						}
					}
				break;
			}
		}
		if($length && $length > 0){
			if(is_array($Q[$k])){
				for($t=0;$t<count($Q[$k]);$t++){
					if(strlen($Q[$k][$t]) > $length){
						throw new Exception("The request is too long");
					}
				}
			}else{
				if(strlen($Q[$k]) > $length){
					throw new Exception("The request is too long");
				}
			}
		}
		if(isset($Q[$k])){
			$R[$k] = $Q[$k];
		}else{
			$R[$k] = '';
		}
	}
	return $R;
}

function RandChar($length){
	$chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
	$return = '';
	for($i=0;$i<$length;$i++){
		$return .= substr($chars,mt_rand(1,strlen($chars)-1),1);
	}
	return $return;
}

function H($code){
	switch($code){
		case "normal":
			header("Content-type:text/html;charset=UTF-8");
		break;
		case "json":
			header("Content-type:application/json");
		break;
		case "404":
			header("HTTP/1.1 404 Not Found");
		break;
		case "500":
			header('HTTP/1.1 500 Internal Server Error');
		break;
	}
}

function T($message,$location){
	$html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>提示</title>
<style type="text/css">
.kk {height: auto;width: 560px;margin-top: 50px;margin-right: auto;margin-left: auto;padding: 20px;background-color: #FFFFFF;border: 2px solid #DBDBDB;font-family: "黑体";}
.kk.ok{border: 2px solid #B6FFA4;}
.kk.error{border: 2px solid #FF7D7D;}
</style>
</head>
<body>
<div class="kk">
<table><tr>
<td><i class="fa fa-exclamation-circle fa-2x text-warning" ></i></td><td style="padding: 15px;">
HTML;
	$html .= $message;
	$html .= '<p><a href="'.$location.'">如果浏览器未跳转请点击此处</a></p></td>';
$html .= <<<AHTML
</tr></table>
</div>
</body>
</html>
AHTML;
	echo $html;
}

function isGet(){
	return $_SERVER['REQUEST_METHOD'] == 'GET';
}

function isPost(){
	return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function fileCacheGet($name){
	if(file_exists(ROOT_PATH.'/Cache/'.$name.'.cache')){
		return file_get_contents(ROOT_PATH.'/Cache/'.$name.'.cache');
	}else{
		return false;
	}
}

function fileCachePut($name, $content){
	file_put_contents(ROOT_PATH.'/Cache/'.$name.'.cache', $content."\n", FILE_APPEND);
}