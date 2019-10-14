<?php
/**
 * author
 * Date: 2019/8/23 0023
 * Time: 下午 2:28
 */

namespace Core\Pool;
interface PoolInterface
{
    /**
     * @return mixed
     * @desc 获取
     */
    public function get();

    /**
     * @param $data
     * @return mixed
     * @desc 存入pool
     */
    public function put($data);

    /**
     * @return int
     */
    public function getLength();

    /**
     * @return mixed
     * @desc 释放
     */
    public function release();
}