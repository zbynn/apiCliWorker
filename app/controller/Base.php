<?php
/**
 * author
 * Date: 2019/8/26 0026
 * Time: 下午 4:03
 */

namespace Controller;

use App\Common;
use App\Dao\Model\LogInterfaceRequest;
use Core\Help;
use Core\ExceptionApi;
use App\Dao\Model\Platform;
use AES\OpensslAES;
use AES\CryptAES;

require_once APPPATH . DS . 'Common.php';
require_once APPPATH . DS . 'extend/AES/OpensslAES.php';
require_once APPPATH . DS . 'extend/AES/CryptAES.php';

class Base extends Common
{
    //返回码
    public $returnCode = [
        '0' => 'ok',

        '10000' => '未传递参数',
        '10001' => '平台编号参数缺失',
        '10002' => '平台编号无匹配',
        '10003' => '签名参数缺失',
        '10004' => '签名验证失败',
        '10005' => '当前请求的IP地址不在授权范围内',
        '10006' => '请求参数requesttime错误',
        '10007' => '请求超时',
        '10008' => '平台编号错误',
        '10009' => '平台正在审核中，请等候审核通过。',
        '10010' => '平台被禁止访问，请联系客服。',
        '10011' => '平台已经关闭',
        '10012' => '查询起始时间和结束时间参数错误',
        '10013' => '注单号参数缺失',

        '20001' => '未分配游戏',
        '20002' => '登入游戏服务器响应失败',
        '20003' => '获取令牌（token）失败',
        '20004' => '缺少转账必传参数：',
        '20005' => '转账类型错误',
        '20006' => '转账失败',
        '20008' => '平台钱包模式不支持，只支持子钱包模式',
        '20009' => '请传入平台订单号或系统订单号',
        '20010' => '未找到转账记录，转账失败',
        '20011' => '同一订单重复请求',
        '20012' => '玩家余额不足',
        '20013' => '转账过程出现异常，请手动查验转账记录',
        '20014' => '两次转账的时间间隔必须大于5秒',
        '20015' => '账号异常，已被关闭，禁止转账',

        '30000' => '新增玩家失败',
        '30001' => '玩家ID参数错误',
        '30002' => '玩家查询失败',
        '30003' => '未配置平台参数key',
        '30004' => '未配置平台响应接口：',
        '30005' => '平台响应接口响应数据错误',
        '30006' => '平台响应接口通讯失败',
        '30007' => '没有查询到相关记录',
        '30008' => '查询失败',
        '30009' => '查询的数据源不存在',
        '30010' => '该玩家账号禁止登录',

        '40001' => '服务器响应请求失败',
    ];

    public $requestValidTime = 0;  // 请求有效时长，单位：秒

    //验证状态，是否开启
    public $initVerityStatus;

    public $phpVersion;

    //解密结果
    public $requestResult;

    //平台信息
    public $platformInfo;

    //日志数据
    public $logRequest;

    public function __construct($initVerityStatus = true)
    {
        parent::__construct();

        $v                = explode('.', PHP_VERSION);
        $this->phpVersion = (int)($v[0] . $v[1]);

        $this->initVerityStatus = $initVerityStatus;
        self::initVerity($initVerityStatus);
    }

    /**
     * @return bool
     * author ZBY
     * Title  公共验证方法
     * Date 2019/8/26 0026 下午 5:57
     */
    public function initVerity()
    {
        //如果验证状态为false，则跳过不验证
        if (!$this->initVerityStatus) {
            return true;
        }

        if (self::isPost()) {
            //获取post参数
            $postParam = $this->request->post;
            //实例化Log类
//            $log = $this->log;

            //初始化日志信息
            $this->logRequest['lir_datetime']  = date('Y-m-d H:i:s');// . '.' . msecTime();  // 请求日志
            $this->logRequest['lir_ip']        = $this->request->server['remote_addr'];  // 请求日志
            $this->logRequest['lir_param']     = json_encode($postParam, JSON_UNESCAPED_UNICODE);  // 请求日志
            $this->logRequest['lir_interface'] = Help::GetCurUrl($this->request);
            $this->logRequest['lir_platform']  = isset($postParam['platformno']) ? $postParam['platformno'] : '';

            //参数非空验证
            if (!isset($postParam['platformno']) || !isset($postParam['parameter']) || is_null($postParam['platformno']) || is_null($postParam['parameter'])) {
                $return = [
                    'code' => '10000',
                    'msg'  => $this->returnCode['10000']
                ];
                throw new ExceptionApi(Help::json($return));
            }

            //验证平台有效性，并返回平台信息
            $platform = $this->verifyPlatform($postParam['platformno']);

            // 验证IP白名单
            $this->verifyIpWhiteList($platform['result']['p_ipwhitelist']);

            // 解密参数parameter，获得请求参数字符串
            // 解密parameter参数
            $deParamStr = $this->deCryptAES($postParam['parameter'], $platform['result']['p_key']);

            // parameter参数字符串转数组
            parse_str($deParamStr, $paramArray);
            $deParamStr = '';
            $this->logRequest['lir_paramdecode'] = json_encode($paramArray, JSON_UNESCAPED_UNICODE);  // 请求日志
            $this->logRequest['lir_username']  = isset($paramArray['username']) ? $paramArray['username'] : '';

            //记录日志
//            $logInterfaceRequestModel = new LogInterfaceRequest();
//            $logInterfaceRequestModel->insert($logRequest);

            //是否存在平台编号
            if (!array_key_exists('platformno', $paramArray)) {
                $return = [
                    'code' => '10001',
                    'msg'  => $this->returnCode['10001']
                ];
                throw new ExceptionApi(Help::json($return));
            }

            if ($postParam['platformno'] != $paramArray['platformno']) {
                $return = [
                    'code' => '10008',
                    'msg'  => $this->returnCode['10008']
                ];
                throw new ExceptionApi(Help::json($return));
            }

            // 验签参数
            $this->verifySign($paramArray, $platform['result']['p_key']);

            // 验证请求时间是否超时
            $this->verifyRequestTime($paramArray['requesttime']);

            //格式化平台数据
            $reData = [
                'platformid'     => $platform['result']['p_id'],           // 平台ID
                'accounts'       => $platform['result']['p_accounts'],     // 平台帐号
                'platformkey'    => $platform['result']['p_key'],          // 平台KEY
                'identifier'     => $platform['result']['p_identifier'],   // 平台编号
                'platformname'   => $platform['result']['p_name'],         // 平台名称
                'platformprefix' => $platform['result']['p_prefix'],       // 平台用户前缀
                'dockingpeople'  => $platform['result']['p_dockingpeople'],// 平台对接人
                'maill'          => $platform['result']['p_maill'],        // 平台对接人邮箱
                'wallet'         => $platform['result']['p_wallet'],       // 平台钱包类型
                'docking'        => $platform['result']['p_docking'],      // 平台对接状态
                'domain'         => $platform['result']['p_domain'],       // 平台域名
                'ipwhitelist'    => $platform['result']['p_ipwhitelist'],  // 平台ip白名单
                'state'          => $platform['result']['p_state'],        // 平台状态；0=待审；1=审核通过；2=关闭；3=黑名单；
                'extract'        => $platform['result']['p_extract'],      // 平台抽水比例
            ];

            //处理多用户条件
            if (isset($paramArray['username']) && trim($paramArray['username']) != '') {
                $paramUsername = '';
                if (strstr($paramArray['username'], ',')) {
                    $expUsername = explode(',', $paramArray['username']);
                    foreach ($expUsername as $k => $v) {
                        $paramUsername .= ',' . $reData['platformprefix'] . $v . '';
                    }
                    $paramUsername = trim($paramUsername, ',');
                } else {
                    $paramUsername = $reData['platformprefix'] . $paramArray['username'];
                }
                $paramArray['username'] = $paramUsername;
            }

            $this->requestResult = $paramArray;
            $this->platformInfo = $reData;
            $paramArray = '';
            $reData = '';
            $platform='';


//                go(function () use ($log) {
//
//                    $log->log('aaa');
//
//                });
        }


        /*for ($i = 0; $i <= 500; $i++) {
go(function ()use($i,$start_time){
    $cli = new Swoole\Coroutine\Http\Client('www.baidu.com', 443,true);
    $cli->setHeaders([
        'Host' => "www.baidu.com",
        "User-Agent" => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $cli->set([ 'timeout' => 0.11]);
    $cli->get('/');
    $cli->close();
    echo  "协程{$i}已完成,耗时".(time()-$start_time).PHP_EOL;
});
}*/
    }

    /**
     * @param $platformno
     * @return array
     * @throws ExceptionApi
     * author ZBY
     * Title  验证平台是否合法
     * Date 2019/8/27 0027 上午 10:57
     */
    public function verifyPlatform($platformno)
    {
        //初始化Platform类
        $platformModel = new Platform();
        $where         = "p_identifier = '{$platformno}'";
        $res           = $platformModel->getPlatformInfo($where);
        if (!$res) {
            $return = [
                'code' => '10002',
                'msg'  => $this->returnCode['10002']
            ];
            throw new ExceptionApi(Help::json($return));
        }

        switch ($res['p_state']) {
            case '0':
                $return = [
                    'code' => '10009',
                    'msg'  => $this->returnCode['10009']
                ];
                throw new ExceptionApi(Help::json($return));
                break;

            case '2':
                $return = [
                    'code' => '10011',
                    'msg'  => $this->returnCode['10011']
                ];
                throw new ExceptionApi(Help::json($return));
                break;

            case '3':
                $return = [
                    'code' => '10010',
                    'msg'  => $this->returnCode['10010']
                ];
                throw new ExceptionApi(Help::json($return));
                break;

            default:
                $return = [
                    'code'   => '0',
                    'msg'    => $this->returnCode['0'],
                    'result' => $res
                ];
                break;
        }

        return $return;
    }

    /**
     * @param string $ipWhiteList
     * @return array
     * author ZBY
     * Title 验证来访平台的访问IP是否在授权的IP白名单内
     * Date 2019/8/27 0027 下午 2:24
     */
    public function verifyIpWhiteList($ipWhiteList = '')
    {
        $ipArr      = explode(',', $ipWhiteList);
        $resquestIP = $this->request->server['remote_addr'];
        if (in_array($resquestIP, $ipArr)) {
            $return = [
                'code' => '0',
                'msg'  => $this->returnCode['0']
            ];
        } else {
            $return = [
                'code' => '10005',
                'msg'  => $this->returnCode['10005'] . $this->resquestIP
            ];
            throw new ExceptionApi(Help::json($return));
        }

        return $return;
    }

    /**
     * @param $paramStr
     * @param $platformKey
     * @return mixed
     * author ZBY
     * Title AES解密传入参数字符串
     * Date 2019/8/27 0027 下午 2:28
     */
    public function deCryptAES($paramStr, $platformKey)
    {
        if ($this->phpVersion >= 71) {
            $return = OpensslAES::decrypt($paramStr, $platformKey);
        } else {
            $aes = new CryptAES();
            $aes->set_key($platformKey);
            $aes->require_pkcs5();
            $return = $aes->decrypt($paramStr);
            $aes    = '';
        }
        return $return;
    }

    /**
     * @param $param
     * @param $platformKey
     * @return array
     * author ZBY
     * Title  验证签名
     * Date 2019/8/27 0027 下午 2:41
     */
    public function verifySign($param, $platformKey)
    {
        if (array_key_exists('sign', $param)) {
            $sign = strtolower($param['sign']);  // 签名转小写
            unset($param['sign']);
            $buff = $this->makeSign($param, $platformKey);
            // MD5 > param
            if ($buff !== $sign) {
                $return = [
                    'code' => '10004',
                    'msg'  => $this->returnCode['10004']
                ];
                throw new ExceptionApi(Help::json($return));
            } else {
                $return = [
                    'code' => '0',
                    'msg'  => $this->returnCode['0']
                ];
            }
        } else {
            $return = [
                'code' => '10003',
                'msg'  => $this->returnCode['10003']
            ];
            throw new ExceptionApi(Help::json($return));
        }

        return $return;
    }

    /**
     * @param $param
     * @param $platformKey
     * @return string
     * author ZBY
     * Title  生成签名
     * Date 2019/8/27 0027 下午 2:41
     */
    public function makeSign($param, $platformKey)
    {
        // 按字典序排序参数
        ksort($param);
        $buff = '';
        foreach ($param as $key => $val) {
            if ($key != 'sign' && !is_array($val)) {//&& $val !== ''
                $buff .= $key . '=' . $val . '&';
            } elseif (is_array($val)) {
                ksort($val);
                foreach ($val as $k => $v) {
                    $buff .= $k . '=' . $v . '&';
                }
            }
        }
        $buff .= 'key=' . $platformKey;
        return md5($buff);
    }

    /**
     * 验证请求时间戳是否有效
     * @param  [type] $resquestTime [description]
     * @return [type]               [description]
     */
    public function verifyRequestTime($resquestTime)
    {
        if ($this->requestValidTime == 0) {
            $return = [
                'code' => '0',
                'msg'  => $this->returnCode['0']
            ];
        } else {
            if (strtotime(date('Y-m-d H:i:s', $resquestTime)) === intval($resquestTime)) {
                if ((time() - $resquestTime) > $this->requestValidTime) {
                    $return = [
                        'code' => '10007',
                        'msg'  => $this->returnCode['10007']
                    ];
                    throw new ExceptionApi(Help::json($return));
                } else {
                    $return = [
                        'code' => '0',
                        'msg'  => $this->returnCode['0']
                    ];
                }
            } else {
                $return = [
                    'code' => '10006',
                    'msg'  => $this->returnCode['10006']
                ];
                throw new ExceptionApi(Help::json($return));
            };
        }

        return $return;
    }
}