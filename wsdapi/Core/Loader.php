<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 4:52
 */
namespace Core;

class Loader{
    /**
     * @param $class
     * author ZBY
     * Title  自动加载方法
     * Date 2019/8/22 0022 下午 5:00
    */
    final public static function autoLoader($class)
    {
        //把类转为目录，eg \a\b\c => /a/b/c.php
        $classPath = \str_replace('\\', DS, $class) . '.php';

        //约定框架类都在Core目录下, 业务类都在app下
        $findPath = [
            ROOTPATH . DS . 'app' . DS,
        ];
        //遍历目录，查找文件
        foreach ($findPath as $path) {
            //如果找到文件，则require进来
            $realPath = $path . $classPath;
            if (is_file($realPath)) {
                require_once "{$realPath}";
                return;
            }
        }

    }
}