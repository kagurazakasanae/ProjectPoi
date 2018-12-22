<?php
//ini_set('default_socket_timeout', -1);

require_once __DIR__ . '/function.php';
require_once __DIR__ . '/Cronjob.Class.php';

$cb = new Cronjob;

$cb -> syncData();

if(date('i') == 0){
	$cb -> hour_avrg();
}
if(date('h') == 0 && date('i') <= 1){
	$cb -> updateDiff();
}
if(date('h') % 2 == 0 && date('i') <= 1){
	$cb -> payXMR();
}
/*
if(date('i') % 30 == 0){
	$cb -> cleanToken();
}*/