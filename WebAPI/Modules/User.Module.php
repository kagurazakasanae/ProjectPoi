<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class User extends Init{
	
	public function Loader(){
		$uri = D('REWRITE', array('[String]1'=>''),32)['1'];
		switch($uri){
			case "balance":
				$this -> getBalance();
				return;
			break;
			case "withdraw":
				$this -> withdrawUser();
				return;
			break;
			default:
				parent::genError('4');
				return;
			break;
		}
	}
	
	private function getBalance(){
		$para = D('POST', array('[String]secret'=>'', '[String]name'=>'', '[String]sitekey'=>''), 64);
		foreach($para as $p){
			if(trim($p) == ''){
				parent::genError('6');
				return;
			}
		}
		$sid = parent::getSidBySitekey($para['sitekey']);
		if($uinfo = $this -> redis -> get('siteuser-'.$sid.'-'.$para['name'])){
			if($keyuserinfo = $this -> Mysql -> select('users', array('api_key'=>$para['secret']))[0]){
				if($this -> Mysql -> confirm('sites', array('uid'=>$keyuserinfo['uid'], 'sid'=>$sid))){
					H('json');
                                          	$uinfo=json_decode($uinfo, true);
					echo json_encode(array('success'=>true, 'name'=>$para['name'], 'total'=>$uinfo[0], 'lastsubmit'=>$uinfo[1], 'error'=>''));	
                                          return;
				}else{
					parent::genError('7');
					return;
				}
			}else{
				parent::genError('2');
				return;
			}
		}else{
			parent::genError('8');
			return;
		}
	}
	
	private function withdrawUser(){
		$para = D('POST', array('[String]secret'=>'', '[String]name'=>'', '[String]sitekey'=>'', '[String]amount'=>''), 64);
		foreach($para as $p){
			if(trim($p) == ''){
				parent::genError('6');
				return;
			}
		}
		if(!is_numeric($para['amount']) || $para['amount'] <= 0){
			parent::genError('1');
			return;
		}
		$sid = parent::getSidBySitekey($para['sitekey']);
		if($uinfo = $this -> redis -> get('siteuser-'.$sid.'-'.$para['name'])){
                      	$uinfo=json_decode($uinfo, true);
			if($uinfo[0] < $para['amount']){
				parent::genError('9');
				return;
			}
			if($keyuserinfo = $this -> Mysql -> select('users', array('api_key'=>$para['secret']))){
				if($this -> Mysql -> confirm('sites', array('uid'=>$keyuserinfo['uid'], 'sid'=>$sid))){
					$uinfo[0] -= $para['amount'];
					$this -> redis -> set('siteuser-'.$sid.'-'.$para['name'], json_encode($uinfo));
					H('json');
					echo json_encode(array('success'=>true, 'name'=>$para['name'], 'amount'=>$para['amount'], 'error'=>''));
					return;
				}else{
					parent::genError('7');
					return;
				}
			}else{
				parent::genError('2');
				return;
			}
		}else{
			parent::genError('8');
			return;
		}
	}
	
	private function getTopUser(){
		
	}
}