<?php
/**
 * author
 * Date: 2019/8/24 0024
 * Time: 下午 2:32
 */
namespace Core;

class Help{
    /**
     * @param $dir
     * @param string $filter
     * @param array $result
     * @return array|bool
     * author ZBY
     * Title 递归获取目录下所有文件
     * Date 2019/8/24 0024 下午 2:35
    */
    public static function getDirTree($dir, $filter = '', &$result = array()){
        try {
            $files = new \DirectoryIterator($dir);
            foreach ($files as $file) {
                if ($file->isDot()) {
                    continue;
                }
                $filename = $file->getFilename();
                if ($file->isDir()) {
                    self::getDirTree($dir . DS . $filename, $filter, $deep, $result);
                } else {
                    if (!empty($filter) && !\preg_match($filter, $filename)) {
                        continue;
                    }
                    $result[$dir][] = $filename;
                }
            }
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取访客ip
     * @return [type] [description]
     */
    public static function getIp()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            try {
                if ($ip) {
                    array_unshift($ips, $ip);
                    $ip = FALSE;
                }
                for ($i = 0; $i < count($ips); $i++) {
                    if (!preg_match('/^(10│172.16│192.168)./i', $ips[$i])) {
                        $ip = $ips[$i];
                        break;
                    }
                }
            } catch (\Exception $e) {
                $return = $e->getMessage() . '<br />' . $ips;
                return $return;
            }
        }
        print_r($_SERVER);
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    /**
     * @return string
     * author ZBY
     * Title  获取当前完整url
     * Date 2019/8/26 0026 下午 5:24
    */
    public static function GetCurUrl($request) {
        $url = 'http://';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url = 'https://';
        }

        $url .=$request->header['host'].$request->server['path_info'];
        return $url;
    }

    /**
     * @param $return
     * @return string
     * author ZBY
     * Title  json格式化
     * Date 2019/8/26 0026 下午 6:07
    */
    public static function json($return){
        return json_encode($return, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param $curlopt_url
     * @return mixed
     * author ZBY
     * Title  http访问
     * Date ct
     *
     */
    public static function curl_get($curlopt_url){
        //初始化
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $curlopt_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        //打印获得的数据
        return $output;
    }
}