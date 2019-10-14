<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 5:01
 */
namespace Core;


class Route
{
    /**
     * @param $path
     * @return mixed
     * author ZBY
     * Title  路由解析方法
     * Date 2019/8/22 0022 下午 5:04
    */
    public static function dispatch($path)
    {
        //默认访问 controller/index.php 的 index方法
        if (empty($path) || '/' == $path) {
            $controller = 'Index';
            $method = 'index';
        } else {
            $maps = explode('/', $path);
            $controller = $maps[1];
            $method = $maps[2];
        }

        $controllerClass = "controller\\{$controller}";
        $class = new $controllerClass;

        return $class->$method();
    }
}