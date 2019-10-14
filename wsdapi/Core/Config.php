<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 4:56
 */
namespace Core;
use Core\Help;

class Config
{
    /**
     * @var 配置map
     */
    public static $configMap;

    /**
     * author ZBY
     * Title 读取配置，默认是app/config/default.php
     * Date 2019/8/22 0022 下午 4:57
    */
//    public static function load()
//    {
//        $configPath = APPPATH . DS . 'config';
//        self::$configMap = require $configPath . DIRECTORY_SEPARATOR . 'default.php';
//    }

    /**
     * @desc 读取配置，默认是application/config 下除default所有的php文件
     *          非default配置，可以热加载
     */
    public static function loadLazy()
    {
        $configPath = APPPATH . DS . 'config';
        $files = Help::getDirTree($configPath, "/.php$/");
        if (!empty($files)) {
            foreach ($files as $dir => $filelist) {
                foreach ($filelist as $file) {
                    $filename = $dir . DS . $file;
                    $fileKeyName = substr($file,0,-4);
                    self::$configMap[$fileKeyName] = include_once "{$filename}";
                }
            }
        }
    }

    /**
     * @param $key
     * @return null
     * author ZBY
     * Title 读取配置
     * Date 2019/8/22 0022 下午 4:57
    */
    public static function get($key)
    {
        if(isset(self::$configMap[$key])) {
            return self::$configMap[$key];
        }

        return null;
    }
}