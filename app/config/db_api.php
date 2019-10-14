<?php
/**
 * author
 * Date: 2019/8/24 0024
 * Time: 上午 11:50
 */
return [
    'name' => 'db_api',
    'pool_size' => 100,     //连接池大小
    'pool_get_timeout' => 1, //当在此时间内未获得到一个连接，会立即返回。（表示所以的连接都已在使用中）
    'master' => [
        'host' => '10.254.1.209',   //数据库ip
        'port' => 3306,          //数据库端口
        'user' => 'root',        //数据库用户名
        'password' => 'root', //数据库密码
        'database' => 'api_h5',   //默认数据库名
        'timeout' => 30,       //数据库连接超时时间
        'charset' => 'utf8mb4', //默认字符集
        'strict_type' => false,  //ture，会自动表数字转为int类型
    ],
];