<?php
/*
Shokaku PHP Framework V2
A simple PHP Framework

Framework.php
Created:2015-9-14 23:28:19
*/

Class Framework{
	static function Run(){
		$Router = C("Router");
		$FrameworkConfigs = C("Framework");
		$req = explode('/',str_replace(str_replace('/index.php','',$_SERVER['SCRIPT_NAME']),'',$_SERVER['REQUEST_URI']));	//分割请求url
		foreach($req AS $tmp){
			if($tmp == ''){
				continue;
			}else{
				$requests[] = explode('?',$tmp)[0];
			}
		}
		unset($req);	//unset释放内存
		unset($tmp);
		if($FrameworkConfigs['SESSION_AUTO_START'] === true){
			session_start();
		}
		if(!isset($requests[0]) || trim($requests[0]) == ''){
			$Module = $FrameworkConfigs['DEFAULT_MODULE'];
			$Action = $FrameworkConfigs['DEFAULT_ACTION'];
			$GLOBALS['Requests'] = array();
		}else{
			$GLOBALS['Requests'] = $requests;
			if(isset($Router[$requests[0]])){
				$R = explode(',',$Router[$requests[0]]);
				$Module = $R[0];
				$Action = $R[1];
			}else{
				throw new Exception('Module \''.$requests[0].'\' not exists in route rules');
			}
		}
		ImportModule($Module);
		if(class_exists($Module)){
			$obj = new $Module;
			if(method_exists($obj,$Action)){
				call_user_func(array($obj,$Action));
			}else{
				throw new Exception('Method \''.$Action.'\' not exists in module \''.$Module.'\'');
			}
		}else{
			throw new Exception('Class \''.$Module.'\' not exists');
		}
	}
}