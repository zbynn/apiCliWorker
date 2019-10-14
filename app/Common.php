<?php
/**
 * author
 * Date: 2019/8/24 0024
 * Time: 下午 4:27
 */

namespace App;

use Core\Loader;
use Core\Pool\Context;
use App\Dao\Dao;
use Core\Log\Logger;
use Core\ExceptionApi;

/**
 * Class Common
 * @package Controller
 * @Title 公共类
 */
require_once APPPATH . DS . 'Dao/Dao.php';
require_once COREPATH . DS . 'Log/Logger.php';

class Common
{
    //请求信息
    public $context;
    //请求参数
    public $request;

    //日志对象
    public $log;

    public function __construct()
    {
        //初始化参数
        $this->context = Context::getInstance()->get();
        $this->request = $this->context->getRequest();

        $logPath   = ROOTPATH . DS . 'Log';
        $this->log = new Logger($logPath);
        //自动引入model
        Dao::loadLazy();
    }

    /**
     * @return bool
     * @throws \Exception
     * author ZBY
     * Title  判断是否是get请求
     * Date 2019/8/26 0026 上午 11:14
     */
    public function isGet()
    {
        if (!property_exists($this->request, 'server')) {
            throw new ExceptionApi("非法请求！");
        }

        if (strtolower($this->request->server['request_method']) == 'get') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     * author ZBY
     * Title  判断是否是post请求
     * Date 2019/8/26 0026 上午 11:14
     */
    public function isPost()
    {
        if (!property_exists($this->request, 'server')) {
            throw new ExceptionApi("非法请求！");
        }

        if (strtolower($this->request->server['request_method']) == 'post') {
            return true;
        }

        return false;
    }
}