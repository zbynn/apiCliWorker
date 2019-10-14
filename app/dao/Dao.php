<?php
/**
 * author
 * Date: 2019/8/24 0024
 * Time: 下午 4:52
 */
namespace APP\Dao;
use Core\Config;
use Core\Db\Mysql;
use Core\Pool\Mysql as MysqlPool;
use Core\Coroutine\Coroutine;
use Core\Help;

class Dao
{
    /**
     * @var mysql连接数组
     * @desc 不同协程不能复用mysql连接，所以通过协程id进行资源隔离
     */
    private $dbs;
    /**
     * @var 数据库配置名称, 用于处理多个数据库
     */
    private $dbTag;
    /**
     * Dao constructor.
     * @param $entity
     * @param $dbTag
     * @throws \ReflectionException
     */
    public function __construct($dbTag = null)
    {
        $this->dbTag = $dbTag;
    }
    /**
     * @param $dbTag
     * @desc 更换数据库连接池
     */
    public function setDbName($dbTag)
    {
        $this->dbTag = $dbTag;
    }
    /**
     * @return Mysql
     * @throws \Exception
     */
    public function getDb()
    {
        //应该是这里有问题，并发下，调用了相同的协程mysql连接池
        $coId = Coroutine::getId();
        if (empty($this->dbs[$coId])) {
            //不同协程不能复用mysql连接，所以通过协程id进行资源隔离
            //达到同一协程只用一个mysql连接，不同协程用不同的mysql连接
            if ($this->dbTag) {
                $mysqlConfig = Config::get($this->dbTag);
            } else {
                $mysqlConfig = null;
            }
            $this->dbs[$coId] = MysqlPool::getInstance($mysqlConfig)->get();
            defer(function () {
                //利用协程的defer特性，自动回收资源
                $this->recycle();
            });
        }
        return $this->dbs[$coId];
    }
    /**
     * @throws \Exception
     * @desc mysql资源回收到连接池
     */
    public function recycle()
    {
        $coId = Coroutine::getId();
        if (!empty($this->dbs[$coId])) {
            $mysql = $this->dbs[$coId];
            MysqlPool::getInstance($mysql->getConfig())->put($mysql);
            unset($this->dbs[$coId]);
        }
    }

    /**
     * author ZBY
     * Title 自动加载
     * Date 2019/8/28 0028 下午 6:38
    */
    public static function loadLazy()
    {
        $configPath = APPPATH . DS . 'dao/model';
        $files = Help::getDirTree($configPath, "/.php$/");
        if (!empty($files)) {
            foreach ($files as $dir => $filelist) {
                foreach ($filelist as $file) {
                    $filename = $dir . DS . $file;
                    include_once "{$filename}";
                }
            }
        }
    }
}