<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Token extends Init{
	
	public function Loader(){
		$uri = D('REWRITE', array('[String]1'=>''),32)['1'];
		switch($uri){
			case "verify":
				$this -> verifyToken();
				return;
			break;
			default:
				parent::genError('4');
				return;
			break;
		}
	}
	
	public function verifyToken(){
		$para = D('POST', array('[String]secret'=>'', '[String]token'=>'', '[String]hashes'=>''), 64);
		foreach($para as $p){
			if(trim($p) == ''){
				parent::genError('6');
				return;
			}
		}
		if(!is_numeric($para['hashes'])){
			parent::genError('1');
			return;
		}
		$token = $this -> redis -> get('tokens-'.$para['token']);
		if($token){
			$token = json_decode($token, true);
		}else{
			parent::genError('7');
			return;
		}
		if($uinfo = $this -> Mysql -> select('users', array('api_key'=>$para['secret']))[0]){
			if($this -> Mysql -> confirm('sites', array('site_key'=>$token[0], 'uid'=>$uinfo['uid']))){
				if($token[2] >= $para['hashes']){
					$this -> redis -> del('tokens-'.$para['token']);
					H('json');
					echo json_encode(array('success'=>true, 'hashes'=>intval($token[2]), 'created'=>$token[3], 'error'=>''));
					return;
				}else{
					parent::genError('7');
					return;
				}
			}else{
				parent::genError('7');
				return;
			}
		}else{
			parent::genError('2');
			return;
		}
	}
}