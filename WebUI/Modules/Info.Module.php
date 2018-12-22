<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Info extends Init{
	
	public function Loader(){
		$uri = D('REWRITE',array('[String]1'=>''),32)['1'];
		H('normal');
		switch($uri){
			case "faq":
				$this -> showFAQ();
			break;
			case "terms-of-service":
				$this -> showTOS();
			break;
			case "privacy":
				$this -> showPrivacy();
			break;
			case "captcha-help":
				$this -> showCaptchaHelp();
			break;
			default:
				parent::outNotFound();
				return true;
			break;
		}
	}

	
	private function showFAQ(){
		$this -> XT -> setdata('thistitle', 'FAQ');
		$this -> XT -> parse('infofaq');
		$this -> XT -> out();
		return true;
	}
	
	private function showTOS(){
		$this -> XT -> setdata('thistitle', 'ToS');
		$this -> XT -> parse('infotos');
		$this -> XT -> out();
		return true;
	}
	
	private function showPrivacy(){
		$this -> XT -> setdata('thistitle', '隐私政策');
		$this -> XT -> parse('infoprivacy');
		$this -> XT -> out();
		return true;
	}
	
	private function showCaptchaHelp(){
		$this -> XT -> setdata('thistitle', 'PoW验证码');
		$this -> XT -> parse('infocaptcha');
		$this -> XT -> out();
		return true;
	}

}