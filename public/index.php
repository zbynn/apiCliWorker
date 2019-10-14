<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 4:46
 */
use Core\Server;

define('DS', DIRECTORY_SEPARATOR);
define('ROOTPATH',dirname(__DIR__));
define('COREPATH',ROOTPATH . DS . 'wsdapi/Core');
define('APPPATH',ROOTPATH . DS . 'app');
define('APP_SIGN_KEY','YkxXPPiM6yxgiRjWeUXXcz4GROCamnOS');
//引入服务文件
require COREPATH . DS . 'Server.php';

//启动服务
Server::run();