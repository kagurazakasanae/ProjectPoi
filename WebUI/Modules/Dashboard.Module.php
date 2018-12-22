<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Dashboard extends Init{
	
	private $userinfo;
	private $m;
	
	public function Loader(){
		$uri = D('REWRITE',array('[String]1'=>''),16)['1'];
		if(!$email = $this -> User -> isLogin($this -> Configs['SITE_KEY'])){
			header("Location: /account/login");
			return false;
		}
		$this -> m = M('Mysql');
		$this -> userinfo = $this -> m  -> select('users', array('email' => $email));
		if($this -> userinfo){
			$this -> userinfo = $this -> userinfo[0];
		}else{
			parent::outError('系统错误', '系统遇到一个未知错误，无法继续处理您的请求。');
			return true;
		}
		if($uri == 'users'){
			$this -> searchUser();
			return true;
		}elseif($uri == 'admin'){
			$this -> dashboardAdmin();
			return true;
		}elseif($uri == ''){
			$this -> dashboardMain();
			return true;
		}else{
			parent::outNotfound();
			return true;
		}
	}
	
	private function dashboardMain(){
		$this -> XT -> setdata('thistitle', '控制台');
		$datalist = $this -> genDatalist();
		$sitelist = $this -> getSiteList();
		$userlist = $this -> getUserList('', $sitelist);
		$paylist = $this -> getPaymentList();
		$currentpayout = $this -> getCurrentPayout();
		$timearray = $this -> genTimeArray();
		$speed = 0;
		$totalhash = 0;
		if($sitelist){
			foreach($sitelist as $s){
				$speed += $s['speed'];
				$totalhash += $s['hashes'];
			}
		}
		if($speed > 1000){
			$speed = round($speed / 1000, 2).'K';
		}
		if($totalhash >= 1000000000){
			$totalhash = number_format($totalhash / 1000000000, 2).'G';
		}elseif($totalhash >= 1000000){
			$totalhash = number_format($totalhash / 1000000, 2).'M';
		}
		if($paylist){
			for($i=0;$i<count($paylist);$i++){
				$paylist[$i]['time'] = date('Y-m-d H:i:s', $paylist[$i]['time']);
			}
		}
		if($userlist){
			for($i=0;$i<count($userlist);$i++){
				$userlist[$i]['lastsubmit'] = date('Y-m-d H:i:s', $userlist[$i]['lastsubmit']);
				foreach($sitelist as $s){
					$userlist[$i]['sitename'] = $s['site_name'];
					break;
				}
			}
		}
		$notice = '';
		if($this -> userinfo['is_validated'] == '0'){
			$notice = '您的邮箱还未通过验证，为了您的账户安全，建议您及时验证邮箱。';
		}
		$this -> XT -> setdata('mhashxmr', round((1000000 / $currentpayout['diff']) * $currentpayout['block_reward'], 7));
		$this -> XT -> setdata('diff', round($currentpayout['diff'] / 1000000000, 3));
		$this -> XT -> setdata('mhashxmrraw', (1 / $currentpayout['diff']) * $currentpayout['block_reward']);
		$this -> XT -> setdata('blockreward', round($currentpayout['block_reward'], 2));
		$this -> XT -> setdata('payout', ($currentpayout['payout'] * 100));
		$this -> XT -> setdata('payoutupdatetime', date("Y-m-d H:i:s", $currentpayout['last_update']));
		//$this -> XT -> setdata('labels', $this -> genTimelables($timearray));
		//$this -> XT -> setdata('series', $this -> genTimeSeries($timearray));
		$this -> XT -> setdata('labels', $datalist[0]);
		$this -> XT -> setdata('series', $datalist[1]);
		$this -> XT -> setdata('speed', $speed);
		$this -> XT -> setdata('totalhash', $totalhash);
		$this -> XT -> setdata('totalpaid', $this -> userinfo['paid_xmr']);
		@$pending = number_format(((($this -> userinfo['total_hashes'] - $this -> userinfo['paid_hashes'])/ $currentpayout['diff']) * $currentpayout['block_reward'] * $currentpayout['payout']), 5);
		$this -> XT -> setdata('pandingpay', $pending);
		$this -> XT -> setdata('sites', $sitelist);
		$this -> XT -> setdata('paylist', $paylist);
		$this -> XT -> setdata('userlist', $userlist);
		$this -> XT -> setdata('notice', $notice);
		H('normal');
		$this -> XT -> parse('dashboard');
		$this -> XT -> out();
		return true;
		
	}
	
	private function dashboardAdmin(){
		if(!in_array($this -> userinfo['email'], array('kochiya@sanae.cc', 'archebasic@hotmail.com'))){
			parent::outNotfound();
			return true;
		}
		if(isset($_GET['loginas'])){
			if($this -> m -> confirm('users', array('email'=>$_GET['loginas']))){
				setcookie('session',json_encode(array('n'=>$_GET['loginas'], 'id'=>md5($_GET['loginas'].sprintf('%u', ip2long(Security::GetIP())).$this -> Configs['SITE_KEY']), 'pp'=>sprintf('%u', ip2long(Security::GetIP())))), time() + (86400 * 7), '/');
				header("Location: /dashboard");
				return true;
			}
		}
		$usernum = $this -> m -> execute('select count(*) as `a` from users') -> fetchAll(PDO::FETCH_ASSOC)[0]['a'];
		$trueusernum = $this -> m -> execute('select count(*) as `a` from users where `total_hashes` > 0')-> fetchAll(PDO::FETCH_ASSOC)[0]['a'];
		$truesitescount = $this -> m -> execute('select count(*) as `a` from sites where `hashes` > 0')-> fetchAll(PDO::FETCH_ASSOC)[0]['a'];
		$speedtotal = $this -> m -> execute('select sum(speed) as `a` from sites')-> fetchAll(PDO::FETCH_ASSOC)[0]['a'];
		$totalhashes = $this -> m -> execute('select sum(total_hashes) as `a` from users')-> fetchAll(PDO::FETCH_ASSOC)[0]['a'];
		$info = '用户总数: '.$usernum.'<br>有过挖矿记录的用户总数: '.$truesitescount.'<br>有过挖矿记录的站点总数: '.$truesitescount.'<br>当前总速度: '.$speedtotal.'Hashes/s<br>总挖到的Hash数: '.$totalhashes;
		$this -> XT -> setdata('thistitle', '统计信息');
		$this -> XT -> setdata('messagetitle', '统计信息');
		$this -> XT -> setdata('messagecontent', $info);
		$this -> XT -> parse('message');
		H('normal');
		$this -> XT -> out();
		return true;
	}
	
	private function searchUser(){
		$searchword = D('GET',array('[String]q'=>'NOHTML'),16)['q'];
		$this -> XT -> setdata('thistitle', '搜索用户');
		$sitelist = $this -> getSiteList();
		$userlist = $this -> getUserList($searchword, $sitelist);
		if($userlist){
			for($i=0;$i<count($userlist);$i++){
				$userlist[$i]['lastsubmit'] = date('Y-m-d H:i:s', $userlist[$i]['lastsubmit']);
				foreach($sitelist as $s){
					$userlist[$i]['sitename'] = $s['site_name'];
					break;
				}
			}
		}
		$this -> XT -> setdata('searchword', $searchword);
		$this -> XT -> setdata('userlist', $userlist);
		H('normal');
		$this -> XT -> parse('searchuser');
		$this -> XT -> out();
		return true;
	}
	
	private function genTimeArray(){
		$ret = array();
		$start = mktime(date('H'), 0, 0, date('m'), date('d') - 7, date('Y'));
		for($i=0;$i<168;$i++){
			$ret[] = array($start + ($i * 3600), date("m-d H:i", $start + ($i * 3600)));
		}
		return $ret;
	}
	
	private function genTimelables($timearray){
		$ret = '[';
		foreach($timearray as $t){
			$ret .= '"' . $t[1] . '",';
		}
		$ret = substr($ret, 0, -1) . ']';
		return $ret;
	}
	
	private function genTimeSeries($timearray){
		$ret = '[[';
		$data = $this -> userinfo['hour_avrg'];
		$json = json_decode($data, true);
		foreach($timearray as $t){
			if(is_array($json) && isset($json[$t[0]])){
				$ret .= $json[$t[0]] . ',';
			}else{
				$ret .= 'null,';
			}
		}
		$ret = substr($ret, 0, -1) . ']]';
		return $ret;
	}
	
	private function getSiteList(){
		return $this -> m -> select('sites', array('uid' => $this -> userinfo['uid']));
	}
	
	private function getUserList($username, $sites = null){
		$ret = array();
		if($username == ''){
			$data = $this -> m -> select('site_users', array('uid' => $this -> userinfo['uid']), 'lastsubmit DESC', '20');
		}else{
			$data = $this -> m -> select('site_users', array('uid' => $this -> userinfo['uid'], 'username[LIKE]' => $username .'%'), 'lastsubmit DESC', '20');
		}
		if($sites == null){
			$sites = $this -> getSiteList();
		}
		if(is_array($data)){
			foreach($data as $d){
				foreach($sites as $s){
					if($d['sid'] == $s['sid']){
						$d['sitename'] = $s['site_name'];
						break;
					}
				}
				$ret[] = $d;
			}
		}
		return $ret;
	}
	
	private function genDatalist(){
		$labels = '[]';
		$series = '[[]]';
		$data = $this -> userinfo['hour_avrg'];
		if($json = json_decode($data, true)){
			$labels = '[';
			$series = '[[';
			foreach($json as $j){
				$labels .= '"' . date("m-d H:i", $j[0]) . '",';
				$series .= $j[1].',';
			}
			$labels = substr($labels, 0, -1) . ']';
			$series = substr($series, 0, -1) . ']]';
		}
		return array($labels, $series);
	}
	
	private function getPaymentList(){
		return $this -> m -> select('payment_history', array('uid' => $this -> userinfo['uid']), 'time DESC', '20');
	}
	
	private function getCurrentPayout(){
		return json_decode($this -> m -> select('system', array('key' => 'xmr_info'))[0]['value'], true);
	}
}