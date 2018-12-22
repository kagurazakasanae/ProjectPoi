<?php
/*
Shokaku PHP Framework V2
A simple PHP Framework

index.php
Created:2015-9-14 22:34:42
*/

date_default_timezone_set('Asia/Shanghai');  

define('ROOT_PATH',str_replace('\\','/',dirname(__FILE__)));
define('IN_FRAMEWORK',true);

require(ROOT_PATH.'/Framework/Init.php');

try{
	Framework::Run();
}catch(Exception $e){
	Import("Error");
	$error = new FrameworkError($e);
	$error -> SystemERROR();
}
