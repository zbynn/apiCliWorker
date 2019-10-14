<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 4:44
 */

namespace Core;

use Core\Config;
use Core\Route;
use Core\Loader;
use Core\Coroutine\Context;
use Core\Pool\Context as PoolContext;
use Core\Coroutine\Coroutine;
use Core\Pool\Mysql;

class Server
{

    public function __construct()
    {

    }

    /**
     * author ZBY
     * Title  服务启动方法
     * Date 2019/8/22 0022 下午 5:01
     */
    final public static function run()
    {
        require COREPATH . DS . 'Loader.php';
        require_once COREPATH . DS . 'Config.php';
        require_once COREPATH . DS . 'Route.php';
        require_once COREPATH . DS . 'Coroutine/Context.php';
        require_once COREPATH . DS . 'Pool/Context.php';
        require_once COREPATH . DS . 'Coroutine/Coroutine.php';
        require_once COREPATH . DS . 'Pool/Mysql.php';
        require_once COREPATH . DS . 'Help.php';
        require_once COREPATH . DS . 'ExceptionApi.php';
        $loader = new Loader();
        //先注册自动加载
        \spl_autoload_register([$loader, 'autoLoader']);
        //加载配置
        Config::loadLazy();

        $dbConfig = Config::get('default');
        //通过读取配置获得ip、端口等
        $http = new \swoole_http_server($dbConfig['host'], $dbConfig['port']);

        $default = Config::get('default');
        $http->set([
            "worker_num" => $default['worker_num'],
        ]);

        //监听进程启动
        $http->on('start', function (\swoole_server $serv) {
            //服务启动
            file_put_contents(ROOTPATH . DS . 'pid' . DS . 'master.pid', $serv->master_pid);
            file_put_contents(ROOTPATH . DS . 'pid' . DS . 'manager.pid', $serv->manager_pid);
        });

        $http->on('shutdown', function () {
            try{
                //服务关闭，删除进程id
                unlink(ROOTPATH . 'DS' . 'pid' . DS . 'master.pid');
                unlink(ROOTPATH . 'DS' . 'pid' . DS . 'manager.pid');
            }catch (\Exception $e){
                print_r($e);
            }

        });

        //监听服务启动
        $http->on('workerStart', function (\swoole_http_server $serv, int $worker_id) {
            if (1 == $worker_id) {
                if (function_exists('opcache_reset')) {
                    //清除opcache 缓存，swoole模式下其实可以关闭opcache
                    \opcache_reset();
                }
            }
            try {
                $mysqlConfig = Config::get('db_api');
                if (!empty($mysqlConfig)) {
                    //配置了mysql, 初始化mysql连接池
                    Mysql::getInstance($mysqlConfig);
                }
                $mysqlConfig = Config::get('db_game');
                if (!empty($mysqlConfig)) {
                    //配置了mysql, 初始化mysql连接池
                    Mysql::getInstance($mysqlConfig);
                }
            } catch (\Exception $e) {
                //初始化异常，关闭服务
                $serv->shutdown();
                print_r($e);
            } catch (\PDOException $pe){
                //初始化异常，关闭服务
                print_r($pe);
            }
        });

        //服务端口接收数据监听
        $http->on('request', function ($request, $response) {

            //初始化根协程ID
            Coroutine::setBaseId();

            //初始化上下文
            $context = new Context($request, $response);

            //存放容器pool
            PoolContext::getInstance()->put($context);

            //协程退出，自动清空
            defer(function () {
                //清空当前pool的上下文，释放资源
                PoolContext::getInstance()->release();
            });

            try {
                //自动路由
                $result = Route::dispatch(
                    $request->server['path_info']
                );
                $response->header('Content-Type', 'application/json');
                $response->end($result);
            } catch (ExceptionApi $ea){
                $response->header('Content-Type', 'application/json');
                $response->end($ea->getMessage());
            } catch (\PDOException $pe){
                $response->status(501);
                $response->end($pe->getMessage().$pe->getFile().$pe->getLine());
            }
            catch (\Exception $e) {
                $response->status(502);
                $response->end($e->getMessage().$e->getFile().$e->getLine());
            } catch (\Error $e) {
                $response->status(503);
                $response->end($e->getMessage().$e->getFile());
            } catch (\Throwable $e) {  //兜底
                $response->status(504);
                $response->end($e->getMessage().$e->getFile());
            }
        });
        $http->start();
    }
}