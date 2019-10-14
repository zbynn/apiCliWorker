<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 5:03
 */

namespace Controller;

use App\Dao\Dao;
use App\Dao\Model\GameRecord;
use App\Dao\Model\LogUserLogon;
use App\Dao\Model\SubPlatform;
use App\Dao\Model\UserBan;
use Controller\Base;
use App\Dao\Model\LogInterfaceRequest;
use App\Dao\Model\PlatformOnlineGame;
use App\Dao\Model\UserInfo;
use Core\Config;
use Core\Help;
use chan;
use App\Dao\Model\Redis;

class Game extends Base
{
    public $lockString;
    public function __construct()
    {
        parent::__construct(true);
        $this->lockString = 'Lock:info:goinGame';
    }

    public function goinGame1(){
        // 检验必传参数
        $requiredParameter = ['username', 'requestip'];//'currency',
        foreach ($requiredParameter as $key => $val) {
            if (!isset($this->requestResult[$val]) || trim($this->requestResult[$val]) == '') {
                $return = ['code' => '10000', 'msg' => $this->returnCode['10000'] . $val, 'result' => []];
                // 请求日志
                $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
                LogInterfaceRequest::getInstance()->insert($this->logRequest);
                return json_encode($return);
            }
        }

        //是否存在gameId
        $this->requestResult['gameid'] = isset($this->requestResult['gameid']) ? $this->requestResult['gameid'] : 0;
        if ($this->requestResult['gameid'] != 0) {
            //游戏记录表是否存在
            $whereExitGame = "game_state=1 AND game_number='{$this->requestResult['gameid']}'";

            $exitGame1 = (new GameRecord())->getInfo($whereExitGame, 'game_id');

            //在线游戏表是否存在
            $whereOnlineGame = "pog_state=1 AND pog_gamenumber={$this->requestResult['gameid']} AND pog_platformid={$this->platformInfo['platformid']}";

            $exitGame2 = (new PlatformOnlineGame())->getInfo($whereOnlineGame, 'pog_id');

            if (!$exitGame1 || !$exitGame2) {
                $return = ['code' => '20001', 'msg' => $this->returnCode['20001'], 'result' => []];

                // 请求日志
                $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
                (new LogInterfaceRequest())->insert($this->logRequest);

                return json_encode($return);
            }
        }
        try{
//            print_r(LogInterfaceRequest::getInstance()->db);
            $a=LogInterfaceRequest::getInstance()->db;
            go(function () use ($a){
                LogInterfaceRequest::getInstance()->db->begin();
                print_r($a);
                LogInterfaceRequest::getInstance()->db->commit();
            });

//            $db=LogInterfaceRequest::getInstance()->db;
//            $db->begin(function( $db, $result) {
//                $db->query("update gs_log_interface_request set lir_username='aaa' where lir_id=476", function($db, $result) {
//                    $db->commit(function($db, $result) {
//                        echo "commit ok\n";
//                    });
//                });
//            });
        }catch (\Exception $e){
            print_r($e->getMessage());
        }catch (Swoole\Errors $er){
            print_r($er->getMessage());
        }


        return 'a';
    }
    /**
     * @return string
     * author ZBY
     * Title  进入游戏接口
     * Date 2019/9/5 0005 下午 3:01
     */
    public function goinGame()
    {
        // 检验必传参数
        $requiredParameter = ['username', 'requestip'];//'currency',
        foreach ($requiredParameter as $key => $val) {
            if (!isset($this->requestResult[$val]) || trim($this->requestResult[$val]) == '') {
                $return = ['code' => '10000', 'msg' => $this->returnCode['10000'] . $val, 'result' => []];
                // 请求日志
                $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
                (new LogInterfaceRequest())->insert($this->logRequest);
                return json_encode($return);
            }
        }

        //是否存在gameId
        $this->requestResult['gameid'] = isset($this->requestResult['gameid']) ? $this->requestResult['gameid'] : 0;

        if ($this->requestResult['gameid'] != 0) {
            //游戏记录表是否存在
            $whereExitGame = "game_state=1 AND game_number='{$this->requestResult['gameid']}'";

            $exitGame1 = (new GameRecord())->getInfo($whereExitGame, 'game_id');

            //在线游戏表是否存在
            $whereOnlineGame = "pog_state=1 AND pog_gamenumber={$this->requestResult['gameid']} AND pog_platformid={$this->platformInfo['platformid']}";

            $exitGame2 = (new PlatformOnlineGame())->getInfo($whereOnlineGame, 'pog_id');

            if (!$exitGame1 || !$exitGame2) {
                $return = ['code' => '20001', 'msg' => $this->returnCode['20001'], 'result' => []];

                // 请求日志
                $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
                (new LogInterfaceRequest())->insert($this->logRequest);

                return json_encode($return);
            }
        }

        //userInfo锁表
        (new UserInfo())->db->begin();
        //全表排他锁
        //UserInfo::getInstance()->getInfo('userId>0', 'userId','for update');
        // 检查接入平台的用户是否存在
        $isExistsUserWhere = "channelId={$this->platformInfo['platformid']} AND username='{$this->requestResult['username']}'";
        (new Redis)->addLock($this->lockString);
        $isExistsUser      = (new UserInfo())->getInfo($isExistsUserWhere, 'userId,username');

        $this->requestResult['nickname'] = isset($this->requestResult['nickname']) ? $this->requestResult['nickname'] : '';
        $currency                        = isset($this->requestResult['currency']) ? $this->requestResult['currency'] : 0;

        if (!$isExistsUser) {
            // 不存在 - 写入 注册
            $insertData = [
                'channelId' => $this->platformInfo['platformid'],
                'username'  => $this->requestResult['username'],
                'nickname'  => $this->requestResult['nickname'],
                //'coin'      => 0,//$userData['currency'],
                'ip'        => $this->request->server['remote_addr'],
                'regTime'   => date('Y-m-d H:i:s'),
                'loginTime' => date('Y-m-d H:i:s'),
                'coin'      => ($this->platformInfo['wallet'] == 1) ? $currency : 0,
                'nickname'  => $this->requestResult['nickname']
            ];

            $userInfo   = (new UserInfo())->insert($insertData);
            $userId   = $userInfo;

            $insertData = '';
            if ($userInfo === false) {
                (new UserInfo())->db->rollback();
            } else {
                (new UserInfo())->db->commit();
            }

        } else
        {
            $updateData = [
//                'userId'    => $isExistsUser['userId'],
                'ip'        => $this->request->server['remote_addr'],
                'loginTime' => date('Y-m-d H:i:s'),
                'coin'      => ($this->platformInfo['wallet'] == 1) ? $currency : 0,
                'nickname'  => $this->requestResult['nickname'],
                'username'  => $this->requestResult['username']
            ];
            // 存在 - 更新
            $userInfoUpdateWhere = "userId='{$isExistsUser['userId']}'";
            $userInfo = (new UserInfo())->update($userInfoUpdateWhere,$updateData);
            $userId   = $isExistsUser['userId'];

            if ($userInfo === false) {
                (new UserInfo())->db->rollback();
            } else {
                (new UserInfo())->db->commit();
            }

            // 玩家是否被禁用（冻结/关闭）
            $banWhere = "userId={$userId}";
            $ban      = (new UserBan())->getInfo($banWhere, 'userId');
            if ($ban) {
                $return = [
                    'code'   => '30010',
                    'msg'    => $this->returnCode['30010'],
                    'result' => []
                ];
                // 请求日志
                $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
                (new LogInterfaceRequest())->insert($this->logRequest);
                return json_encode($return);
            }
        }
        (new Redis)->delLock($this->lockString);


        // 记录登录日志
        $logonLog = [
            'lul_userid'     => $userId,
            'lul_username'   => $this->requestResult['username'],
            'lul_platformid' => $this->platformInfo['platformid'],
            'lul_logontime'  => date('Y-m-d H:i:s'),
            'lul_logonip'    => $this->request->server['remote_addr'],
            'lul_gameid'     => $this->requestResult['gameid']
        ];
        //增加用户登录日志
        (new LogUserLogon())->insert($logonLog);
        $logonLog = '';


        // 获取进入游戏必须的Token
        $sign         = md5('userId=' . $userId . '&key=' . APP_SIGN_KEY);
        $config       = Config::get('config');
        $url          = $config['game_server_address'] . 'interface/getToken?userId=' . $userId . '&sign=' . $sign;
        $getTokenChan = new chan(1);
        go(function () use ($url, $getTokenChan) {
            $_response = Help::curl_get($url);
            $getTokenChan->push($_response);
        });
        $_response = $getTokenChan->pop();
        $response  = json_decode($_response, true);

        //判断返回结果
        if (!isset($response['code']) || !isset($response['data'])) {
            $return = [
                'code'   => '20002',
                'msg'    => $response['msg'],
                'result' => []
            ];
            // 请求日志
            $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
            (new LogInterfaceRequest())->insert($this->logRequest);
            return json_encode($return);
        }

        if ($response['code'] != 200) {
            $return = [
                'code'   => '20003',
                'msg'    => $this->returnCode['20003'],
                'result' => []
            ];
            // 请求日志
            $this->logRequest['lir_return'] = json_encode($return, JSON_UNESCAPED_UNICODE);
            (new LogInterfaceRequest())->insert($this->logRequest);
            return json_encode($return);
        }

        // 获得游戏URL
        $gameUrl = $config['enter_game_url'];
        // 玩家当前是否在游戏中及所在游戏的ID
        $getUserTableChan = new chan(1);
        go(function () use ($config, $userId, $getUserTableChan) {
            $userGame = Help::curl_get($config['game_server_address'] . 'interface/getUserTable?userId=' . $userId);
            $getUserTableChan->push($userGame);
        });
        $userGame = $getUserTableChan->pop();

        $inGame = json_decode($userGame, true);
        $gameid = $this->requestResult['gameid'];

        //如果之前在游戏中，则更新gameid值
        if (isset($inGame['code']) && isset($inGame['data'])) {
            if ($inGame['code'] == 200 && $inGame['data']['isInTable'] == 1) {
                $gameid = $inGame['data']['gameId'];
            }
        }

        // 返回结果
        $result['game_address'] = $gameUrl . '?token=' . $response['data']['token'] . '&gameid=' . $gameid;  // 外网使用

        //添加子平台并更新用户数据-不能影响正常进入游戏
        if (isset($this->requestResult['subchannel']) && !empty($this->requestResult['subchannel'])) {
            $subchannelres = (new SubPlatform())->updateUserSubchannel($this->platformInfo['platformid'], $this->requestResult['subchannel'], $userId, true);

            if ($subchannelres['code'] == 1) {
                //userInfo锁表
                (new UserInfo())->db->begin();
                //全表排他锁
//                UserInfo::getInstance()->getInfo("userId={$userId}", 'userId','for update');
                //更新用户数据
                $updateData2 = [
                    'subchannel' => $subchannelres['data'],
                    'userId'=>$userId,
                    'username'  => $this->requestResult['username']
                ];
                $updateuser  = (new UserInfo())->insertOrUpdate($updateData2);

                if ($updateuser === false) {
                    (new UserInfo())->db->rollback();
                } else {
                    (new UserInfo())->db->commit();
                }

                // 请求日志
                $this->logRequest['lir_return'] = json_encode($subchannelres, JSON_UNESCAPED_UNICODE);
                (new LogInterfaceRequest())->insert($this->logRequest);
            }
        }

        $return = [
            'code'   => '0',
            'msg'    => $this->returnCode['0'],
            'result' => $result,
            'sign'   => $this->makeSign($result, $this->platformInfo['platformkey'])
        ];
        return json_encode($return);
    }
}