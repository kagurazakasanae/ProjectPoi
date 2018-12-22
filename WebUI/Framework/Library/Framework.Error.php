<?php
/*
Shokaku PHP Framework V2
A simple PHP Framework

Framework.Error.php
Created:2015-9-15 19:08:02
*/

Class FrameworkError{
	private $Error;
	private $Trace;
	private $ErrorMessage;
	private $Configs;
	public function __construct($error){
		$this -> Error = $error;
		$this -> Trace = $error -> getTrace();
		$this -> ErrorMessage = htmlspecialchars($error -> getMessage());
		$this -> Configs = C("Framework");
	}
	public function SystemERROR(){
		$html = <<<HTML
		
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>System Error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
	<style type="text/css">
	<!--
	body { background-color: white; color: black; font: 9pt/11pt verdana, arial, sans-serif;}
	#container { width: 1024px; }
	#message   { width: 1024px; color: black; }

	.red  {color: red;}
	a:link     { font: 9pt/11pt verdana, arial, sans-serif; color: red; }
	a:visited  { font: 9pt/11pt verdana, arial, sans-serif; color: #4e4e4e; }
	h1 { color: #FF0000; font: 18pt "Verdana"; margin-bottom: 0.5em;}
	.bg1{ background-color: #FFFFCC;}
	.bg2{ background-color: #EEEEEE;}
	.table {background: #AAAAAA; font: 11pt Menlo,Consolas,"Lucida Console"}
	.info {
	    background: none repeat scroll 0 0 #F3F3F3;
	    border: 0px solid #aaaaaa;
	    border-radius: 10px 10px 10px 10px;
	    color: #000000;
	    font-size: 11pt;
	    line-height: 160%;
	    margin-bottom: 1em;
	    padding: 1em;
	}

	.help {
	    background: #F3F3F3;
	    border-radius: 10px 10px 10px 10px;
	    font: 12px verdana, arial, sans-serif;
	    text-align: center;
	    line-height: 160%;
	    padding: 1em;
	}

	.sql {
	    background: none repeat scroll 0 0 #FFFFCC;
	    border: 1px solid #aaaaaa;
	    color: #000000;
	    font: arial, sans-serif;
	    font-size: 9pt;
	    line-height: 160%;
	    margin-top: 1em;
	    padding: 4px;
	}
	-->
	</style>
</head>
<body>
<div id="container">
<h1>System Error</h1>
<div class='info'><li>
HTML;
		$html .= $this -> ErrorMessage;
		$html .= <<<AHTML
	</li></div>

<div class="info"><p><strong>PHP Debug</strong></p><table cellpadding="5" cellspacing="1" width="100%" class="table"><tr><td><ul>
AHTML;
		if($this -> Configs['DEBUG_MODE'] === true){
			for($i=0;$i<count($this -> Trace);$i++){
				if(isset($this -> Trace[$i]['file'])){
					$this -> Trace[$i]['file'] = str_replace('\\','/',$this -> Trace[$i]['file']);
					$this -> Trace[$i]['file'] = str_replace(ROOT_PATH.'/','',$this -> Trace[$i]['file']);
				}
				if(isset($this -> Trace[$i]['line'])){
					$html .= '<li>[Line: '.$this -> Trace[$i]['line'].']';
				}else{
					$html .= '<li>[Line: 0]Unkown file';
				}
				if(isset($this -> Trace[$i]['file'])){
					$html .= $this -> Trace[$i]['file'];
				}
				if(isset($this -> Trace[$i]['class'])){
					$html .= '('.$this -> Trace[$i]['class'].$this -> Trace[$i]['type'].$this -> Trace[$i]['function'].')</li>';
				}else{
					$html .= '('.$this -> Trace[$i]['function'].')</li>';
				}
			}
		}else{
			$html .= '<li>DEBUG_MODE has been disabled</li>';
		}
		$html .= <<<BHTML
		</ul></td></tr></table></div><div class="help">我们已经将此出错信息详细记录, 由此给您带来的访问不便我们深感歉意. </div>
</div>
</body>
</html>
BHTML;
		echo $html;
		if($this -> Configs['LOG_ERROR']){
			$this -> LogError();
		}
	}
	private function LogError(){
		file_put_contents(ROOT_PATH.'/Framework/Logs/'.time().mt_rand(100,999).'.txt',var_export($this -> Trace,true),FILE_APPEND);
	}
	
}