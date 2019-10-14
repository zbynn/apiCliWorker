<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;
use Swoole\Coroutine\Redis as ScRedis;
use Core\Config;

class Redis
{
    public $redis;
    public $ex;

    public function __construct()
    {
        $this->redis      = new ScRedis();
        $this->ex         = 3;
        $redisConfig = Config::get('redis');
        $this->redis->connect($redisConfig['Host'], $redisConfig['Port']);

        defer(function () {
            //利用协程的defer特性，自动回收资源
            $this->redis->close();
        });
    }


    /**
     * author ZBY
     * Title 上redis锁
     * Date 2019/8/19 0019 下午 3:01
     */
    public function addLock($lockString){
        while(true){
            $res  = $this->redis->set($lockString, '1', ['nx', 'ex' => $this->ex]);
            if($res) { break; }
            usleep(500);
        }
    }

    /**
     * author ZBY
     * Title  删除redis锁
     * Date 2019/8/19 0019 下午 3:01
     */
    public function delLock($lockString){
        $this->redis->del($lockString);
    }
}