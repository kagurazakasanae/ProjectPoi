<?php
ini_set('default_socket_timeout', -1);

echo "Mainbody Loading...\n";
use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use \Workerman\Lib\Timer;


require_once __DIR__ . '/Workerman/Autoloader.php';
require_once __DIR__ . '/PPOI/function.php';
require_once __DIR__ . '/PPOI/APP.Class.php';


$ws_worker = new Worker("websocket://127.0.0.1:2000");
$ws_worker -> count = 12;

$poi = new APP;
$poi -> loadConfig();
/*
//临时信息储存
//尝试token直接同步，其他每秒同步
//键sitekey 值增加值,每次同步清0
$anonymousStore = array();
//站点用户信息临时储存
//键sitekey<>username 值0为增加hash 值1为redis记录的初始hash
$siteuserStore = array();
//站点缓存,临时缓存站点是否存在,每6秒同步
//inarray则存在
$siteStore = array();
//同步锁，redis同步时暂停对缓冲区进行读写
$syncLock = false;

//初始化站点缓存
if(!$siteStore = $poi -> getSitelist()){
	echo "Can not fetch sitelist\n";
	die;
}
//设定同步任务,同步站点列表
Timer::add(6, function()use($poi, &$siteStore){
	$tmp = $poi -> getSitelist();
	if(is_array($tmp)){
		$siteStore = $tmp;
	}
});
//同步任务#2 更新用户列表，服务器->本地 迫加型
Timer::add(33, function()use($poi, &$siteuserStore){
	$userlist = $poi -> getSiteUsetList();
	if($userlist){
		foreach($userlist as $k => $v){
			if(!isset($siteuserStore[$k])){
				$siteuserStore[$k] = $v;
			}
		}
	}
});
//替换用函数
function chkSitekeyfromTmpStore($sitekey){
	return in_array($sitekey, $GLOBALS['siteStore']);
}

echo "Fetching userlist\n";
if(!$siteuserStore = $poi -> getSiteUsetList()){
	echo "Can not fetch userlist\n";
}
//替换用函数
function getUserInfofromTmpStore($sitekey, $username){
	if(isset($GLOBALS['siteuserStore'][$sitekey.'<>'.$username])){
		return $GLOBALS['siteuserStore'][$sitekey.'<>'.$username];
	}else{
		return false;
	}
}
function addUserToTmpStore($sitekey, $username){
	while(true){
		if(!$GLOBALS['syncLock']){
			break;
		}
		usleep(50000);
	}
	$GLOBALS['syncLock'] = true;
	$GLOBALS['siteuserStore'][$sitekey.'<>'.$username] = array(0, 0);
	$GLOBALS['syncLock'] = false;
}
//替换addHash函数
function addHashtoTmpStore($sitekey, $type, $username = null){
	while(true){
		if(!$GLOBALS['syncLock']){
			break;
		}
		usleep(50000);
	}
	$GLOBALS['syncLock'] = true;
	if($type == 'anonymous'){
		if(isset($GLOBALS['anonymousStore'][$sitekey])){
			$GLOBALS['anonymousStore'][$sitekey] += 256;
		}else{
			$GLOBALS['anonymousStore'][$sitekey] = 256;
		}
	}elseif($type == 'user'){
		if(isset($GLOBALS['anonymousStore'][$sitekey.'<>'.$username])){
			$GLOBALS['anonymousStore'][$sitekey.'<>'.$username][0] += 256;
			$GLOBALS['anonymousStore'][$sitekey.'<>'.$username][1] += 256;
		}else{
			$GLOBALS['anonymousStore'][$sitekey.'<>'.$username] = array(0, 0);
		}
	}
	//echo 'triggered'."\n";
	$GLOBALS['syncLock'] = false;
}
*/
$poolData = $poi -> getPoolData();

$ws_worker -> poolConnections = array();
$ws_worker -> connectionInfo = array();
$ws_worker -> rejectCount = array();
$ws_worker -> poiPool = array();
$connection_count = 0;



$ws_worker -> onWorkerStart = function($worker){
	$worker -> poiPool[$worker -> id] = new APP;
};

$ws_worker -> onConnect = function($connection)use(&$connection_count){
    ++$connection_count;
	//6s未完成验证关闭连接
	$connection -> auth_timer_id = Timer::add(6, function()use($connection){
		$connection -> close();
	}, null, false);
};

$ws_worker -> onClose = function($connection)use(&$connection_count){
	//连接关闭，释放资源
    $connection_count--;
	if(isset($connection -> uid)){
		if(isset($connection -> connectionInfo[$connection -> uid])){
			unset($connection -> connectionInfo[$connection -> uid]);
		}
		if(isset($connection -> rejectCount[$connection -> uid])){
			unset($connection -> rejectCount[$connection -> uid]);
		}
		if(isset($connection -> poolConnections[$connection -> uid])){
			$connection -> poolConnections[$connection -> uid] -> close();	//关掉矿池连接
			unset($connection -> poolConnections[$connection -> uid]);
		}	
	}
};

$ws_worker -> onMessage = function($connection, $data)use($poolData, &$connection_count){
	$poi = $connection -> worker -> poiPool[$connection -> worker -> id];
	//确认数据包
	if(!$data = json_decode($data, true)){
		$connection -> close();
	}elseif(!isset($data['type'])){
		$connection -> close();
	}
	switch($data['type']){
		case "auth":
			if(isset($data['params']) && isset($data['params']['site_key']) && isset($data['params']['type']) && $poi -> chkSitekey($data['params']['site_key']) && !$poi -> chkReject($data['params']['site_key'])){
				//验证通过
				Timer::del($connection -> auth_timer_id);	//取消
				$connection -> uid = randChar(32);	//随机32位字符串作为uid
				$connection -> connectionInfo[$connection -> uid] = array('sitekey'=>$data['params']['site_key']);	//标识下sitekey
				$connection -> rejectCount[$connection -> uid] = 0;
				$res = array();
				switch($data['params']['type']){
					case "anonymous":
						$res['type'] = 'authed';
						$res['params'] = array('token'=>'', 'hashes'=>0);
						$connection -> connectionInfo[$connection -> uid]['type'] = 'anonymous';	//记录类型
						$connection -> connectionInfo[$connection -> uid]['hashes'] = 0;	//hashes
						$connection -> send(json_encode($res));	//发送验证通过的消息
					break;
					case "token":
						$res['type'] = 'authed';
						$token = $poi -> genToken($data['params']['site_key']);	//创建新token
						$res['params'] = array('token'=>$token, 'hashes'=>0);
						$connection -> connectionInfo[$connection -> uid]['type'] = 'token';	//记录类型
						$connection -> connectionInfo[$connection -> uid]['token'] = $token;	//记录分配的token
						$connection -> connectionInfo[$connection -> uid]['hashes'] = 0;	//hashes
						$connection -> send(json_encode($res));	//发送验证通过的消息
					break;
					case "user":
						if(isset($data['params']['user']) && trim($data['params']['user']) != ''){
							$username = htmlspecialchars(substr($data['params']['user'], 0, 32));
							if(!$uinfo = $poi -> getUserInfo($data['params']['site_key'], $username)){
								$hashes = 0;
								$poi -> addNewUser($data['params']['site_key'], $username);	//添加用户
							}else{
								$hashes = $uinfo[1];
							}
							$res['type'] = 'authed';
							$res['params'] = array('token'=>'', 'hashes'=>$hashes);
							$connection -> connectionInfo[$connection -> uid]['type'] = 'user';	//记录类型
							$connection -> connectionInfo[$connection -> uid]['user'] = $username;	//记录username
							$connection -> connectionInfo[$connection -> uid]['hashes'] = $hashes;	//hashes
							$connection -> send(json_encode($res));	//发送验证通过的消息
						}else{
							//瞎搞?.jpg
							$res['type'] = 'error';
							$res['params'] = array('error'=>'invalid_params');
							$connection -> send(json_encode($res));	//发送验证失败的消息
							$connection -> close();	//gun.jpg
						}
					break;
				}
				$connection -> poolConnections[$connection -> uid] = new AsyncTcpConnection('tcp://'.$poolData['Server']);	//创建连接并添加到连接池
				$connection -> poolConnections[$connection -> uid] -> wsConnection = $connection;	//把websocket的连接指针交给poolConnection
				$connection -> poolConnections[$connection -> uid] -> Poi = $poi;
				$connection -> poolConnections[$connection -> uid] -> poolData = $poolData;
				$connection -> poolConnections[$connection -> uid] -> acceptCount = 0;
				$connection -> poolConnections[$connection -> uid] -> connection_count = $connection_count;
				$connection -> poolConnections[$connection -> uid] -> onConnect = function($poolConnection){
					$poolData = $poolConnection -> poolData;
					//成功与矿池建立连接
					$msg = '{"method":"login","params":{"login":"'.$poolData['Address'].'","pass":"worker","agent":"ProjectPoi/0.1"},"id":'.$poolConnection -> connection_count.'}';	//握手消息
					$poolConnection -> send($msg."\n");	//发送
				};
				//var_dump(count($anonymousStore));
				$connection -> poolConnections[$connection -> uid] -> onMessage = function($poolConnection, $data)use(&$syncLock, &$anonymousStore, &$siteuserStore, &$siteStore){
					$poi = $poolConnection -> Poi;
					//收到矿池回复
					if($data = json_decode($data, true)){
						if(!isset($data['error']) || $data['error'] == null){
							if((isset($data['method']) and $data['method'] == 'job') || isset($data['result']['job'])){
								//分配任务
								if(isset($data['method']) && $data['method'] == 'job'){
									if(isset($poolConnection -> pollid)){
										$poolConnection -> wsConnection -> send(json_encode(array('type'=>'job','params'=>$data['params'])));	//将内容发还给本连接对应的websocket
									}else{
										//你甚至没有给我id
										echo "That's weried, pool returns new job without provide pollid\n";
										var_dump($data);
										$poolConnection -> wsConnection -> close();
									}
								}else{
									$poolConnection -> wsConnection -> send(json_encode(array('type'=>'job','params'=>$data['result']['job'])));	//将内容发还给本连接对应的websocket
									$poolConnection -> pollid = $data['result']['id'];
								}
								return;
							}else{
								if($data['result']['status'] == 'OK'){
									$poolConnection -> acceptCount++;
									//矿池接受了结果
									//添加hash
									switch($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['type']){
										case "anonymous":
											if($poolConnection -> acceptCount == 1){
												continue;	//第一次不计 第二次计两次 处理矿池accept问题
											}elseif($poolConnection -> acceptCount == 2){
												$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'anonymous');
											}
											$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'anonymous');
										break;
										case "token":
											if($poolConnection -> acceptCount == 1){
												continue;	//第一次不计 第二次计两次 处理矿池accept问题
											}elseif($poolConnection -> acceptCount == 2){
												$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'token', $poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['token']);
											}
											$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'token', $poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['token']);
										break;
										case "user":
											if($poolConnection -> acceptCount == 1){
												continue;	//第一次不计 第二次计两次 处理矿池accept问题
											}elseif($poolConnection -> acceptCount == 2){
												$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'user', null, $poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['user']);
											}
											$poi -> addHash($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey'], 'user', null, $poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['user']);
										break;
									}
									$poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['hashes'] += 128;	//统计里加个单位
									$poolConnection -> wsConnection -> send('{"type":"hash_accepted","params":{"hashes":'.$poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['hashes'].'}}');	//发送给客户端
									return;
								}
							}
						}else{
							if($data['error']['code'] == -1){
								//矿池拒绝
								$poolConnection -> wsConnection -> rejectCount[$poolConnection -> wsConnection -> uid] += 1;	//拒绝次数+1
								if($poolConnection -> wsConnection -> rejectCount[$poolConnection -> wsConnection -> uid] > 1){	//3拒绝
									$poi -> addReject($poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['sitekey']);
									$poolConnection -> wsConnection -> close();	//挂断.jpg
								}
								$poolConnection -> wsConnection -> send('{"type":"hash_rejected","params":{"hashes":'.$poolConnection -> wsConnection -> connectionInfo[$poolConnection -> wsConnection -> uid]['hashes'].'}}');
							}
							return;
						}
					}else{
						//这种就比较玄学了
						echo 'Cannot decode pool response ' . $data . "\n";
						return;
					}
				};
				$connection -> poolConnections[$connection -> uid] -> onClose = function($poolConnection){
					//矿池都抛弃了我 我还要客户端有何用
					$poolConnection -> wsConnection -> close();	//挂断.jpg
				};
				$connection -> poolConnections[$connection -> uid] -> onError = function($poolConnection, $code, $msg){
					//炸了
					$poolConnection -> wsConnection -> close();	//挂断.jpg
					echo "Pool Connect Error $code $msg\n";
				};
				$connection -> poolConnections[$connection -> uid] -> connect();
				//一旦80s没有提交任何数据则关闭连接
				$connection -> auth_timer_id = Timer::add(80, function()use($connection){
					$connection->close();
				}, null, false);
				return;
			}else{
				//sitekey = tan90
				$connection -> send('{"type":"error","params":{"error":"invalid_site_key"}}');
				$connection -> close();
				return;
			}
		break;
		case "submit":
			if(isset($data['params']) && isset($data['params']['job_id']) && isset($data['params']['nonce']) && isset($data['params']['result'])){
				//收到结果
				if(!isset($connection -> poolConnections[$connection -> uid]) || !isset($connection -> poolConnections[$connection -> uid] -> pollid)){
					echo 'Try to submit without pollid(not authed or server restarted): '.$connection -> uid."\n";
					$connection -> send('{"type":"error","params":{"error":"invalid_params"}}');
					$connection -> close();
					return;
				}
				
				$data['params']['id'] = $connection -> poolConnections[$connection -> uid] -> pollid;
				$send = json_encode(array('method'=>'submit','id'=>1,'params'=>$data['params']));
				Timer::del($connection -> auth_timer_id);	//取消
				$connection -> auth_timer_id = Timer::add(80, function()use($connection){
					$connection->close();
				}, null, false);	//再加回去
				$connection -> poolConnections[$connection -> uid] -> send($send."\n");	//丢给矿池
				return;
			}else{
				//搞事?.jpg
				$connection -> send('{"type":"error","params":{"error":"invalid_params"}}');
				$connection -> close();
			}
		break;
		default:
			//搞事?.jpg
			$connection -> send('{"type":"error","params":{"error":"invalid_params"}}');
			$connection -> close();
			return;
		break;
	}
};

Worker::runAll();