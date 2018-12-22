<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Stat extends Init{
	
	public function Loader(){
		$uri = D('REWRITE', array('[String]1'=>''),32)['1'];
		switch($uri){
			case "payout":
				$this -> showPayout();
				return;
			break;
			case "site":
				$this -> getSites();
				return;
			break;
			default:
				parent::genError('4');
				return;
			break;
		}
	}
	
	private function showPayout(){
		$info = json_decode($this -> Mysql -> select('system', array('key'=>'xmr_info'))[0]['value'], true);
		$info['success'] = true;
		$info['error'] = '';
		H('json');
		echo json_encode($info);
		return;
	}
	
	private function getSites(){
		$para = D('POST', array('[String]secret'=>''), 64);
		if(trim($para['secret']) == ''){
			parent::genError('6');
			return;
		}
		$sitedata = array();
		if($uinfo = $this -> Mysql -> select('users', array('api_key'=>$para['secret']))[0]){
			$sites = $this -> Mysql -> select('sites', array('uid'=>$uinfo['uid']));
			foreach($sites as $s){
				$sitedata[] = array('hashes'=>$s['hashes'],'speed'=>$s['speed']);
			}
			H('json');
			echo json_encode(array('success'=>true, 'sitedata'=>$sitedata, 'error'=>''));
			return;
		}else{
			parent::genError('2');
			return;
		}
	}
	
}