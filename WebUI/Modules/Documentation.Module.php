<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Documentation extends Init{
	
	public function Loader(){
		$uri = D('REWRITE',array('[String]1'=>''),32)['1'];
		H('normal');
		switch($uri){
			case "":
				$this -> showIndex();
			break;
			case "simple-ui":
				$this -> showSimpleUi();
			break;
			case "captcha":
				$this -> showCaptcha();
			break;
			case "miner":
				$this -> showMiner();
			break;
			case "http-api":
				$this -> showHTTPApi();
			break;
			default:
				parent::outNotFound();
				return true;
			break;
		}
	}
	
	private function showIndex(){
		$this -> XT -> setdata('thistitle', '文档');
		$this -> XT -> parse('documentation');
		$this -> XT -> out();
		return true;
	}
	
	private function showSimpleUi(){
		$this -> XT -> setdata('thistitle', '文档');
		$this -> XT -> parse('documentationsimpleui');
		$this -> XT -> out();
		return true;
	}
	
	private function showCaptcha(){
		$this -> XT -> setdata('thistitle', '验证码');
		$this -> XT -> parse('documentationcaptcha');
		$this -> XT -> out();
		return true;
	}
	
	private function showMiner(){
		$this -> XT -> setdata('thistitle', 'JavaScript挖矿');
		$this -> XT -> parse('documentationminer');
		$this -> XT -> out();
		return true;
	}
	
	private function showHTTPApi(){
		$this -> XT -> setdata('thistitle', 'HTTP API');
		$this -> XT -> parse('documentationhttpapi');
		$this -> XT -> out();
		return true;
	}
}