<?php
/**
 * author
 * Date: 2019/8/23 0023
 * Time: 下午 2:04
 */

namespace Core\Coroutine;

use Swoole\Coroutine as SwCo;
use chan;

class Coroutine
{
    /**
     * @var array
     * @desc 保存当前协程根id
     *      结构：["当前协程Id"=> "根协程Id"]
     */
    public static $idMaps = [];

    /**
     * @return mixed
     * @desc 获取当前协程id
     */
    public static function getId()
    {
        return SwCo::getuid();
    }

    /**
     * @desc 父id自设, onRequest回调后的第一个协程，把根协程Id设置为自己
     */
    public static function setBaseId()
    {
        $id                = self::getId();
        self::$idMaps[$id] = $id;
        return $id;
    }

    /**
     * @param null $id
     * @param int $cur
     * @return int|mixed|null
     * @desc 获取当前协程根协程id
     */
    public static function getPid($id = null, $cur = 1)
    {
        if ($id === null) {
            $id = self::getId();
        }
        if (isset(self::$idMaps[$id])) {
            return self::$idMaps[$id];
        }
        return $cur ? $id : -1;
    }

    /**
     * @return bool
     * @throws \Exception
     * @desc 判断是否是根协程
     */
    public static function checkBaseCo()
    {
        $id = SwCo::getuid();
        if (empty(self::$idMaps[$id])) {
            return false;
        }
        if ($id !== self::$idMaps[$id]) {
            return false;
        }
        return true;
    }

    /**
     * @param $cb //协程执行方法
     * @param null $deferCb //defer方法
     * @return mixed
     * @从协程中创建协程，可保持根协程id的传递
     */
    public static function create($deferCb = null)
    {
        $nid = self::getId();
        $idChan = new chan(1);
        go(function () use ($idChan,$deferCb, $nid) {
            $id = SwCo::getuid();
            $idChan->push($id);
            defer(function () use ($deferCb, $id) {
                //self::call($deferCb);
                self::clear($id);
            });
            $pid = self::getPid($nid);
            if ($pid == -1) {
                $pid = $nid;
            }

            //self::call($cb);
        });
        $id = $idChan->pop();
        self::$idMaps[$id] = $id;
    }

    /**
     * @param $cb
     * @param $args
     * @return null
     * @desc 执行回调函数
     */
    public static function call($cb, $args)
    {
        if (empty($cb)) {
            return null;
        }
        $ret = null;
        if (\is_object($cb) || (\is_string($cb) && \function_exists($cb))) {
            $ret = $cb(...$args);
        } elseif (\is_array($cb)) {
            list($obj, $mhd) = $cb;
            $ret = \is_object($obj) ? $obj->$mhd(...$args) : $obj::$mhd(...$args);
        }
        return $ret;
    }

    /**
     * @param null $id
     * @desc 协程退出，清除关系树
     */
    public static function clear($id = null)
    {
        if (null === $id) {
            $id = self::getId();
        }
        unset(self::$idMaps[$id]);
    }
}