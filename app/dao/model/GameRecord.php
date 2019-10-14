<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;

use App\Dao\Dao;

class GameRecord
{
    //表名
    public $table;
    //mysql对象
    public $db;
    private static $instance;
    public function __construct()
    {
        $this->table = 'gs_game_record';
        $this->db    = (new Dao('db_api'))->getDb();
        defer(function () {
            //利用协程的defer特性，自动回收资源
            $this->db->release();
        });
    }

    /**
     * @param $where
     * @param string $field
     * @return mixed
     * author ZBY
     * Title  获取平台信息
     * Date 2019/8/27 0027 上午 10:50
     */
    public function getInfo($where = '', $field = '*')
    {
        $where = $where ? "WHERE {$where}" : '';
        $sql = "SELECT {$field} FROM {$this->table} {$where} limit 0,1";

        $res = $this->db->query($sql);

        if (!$res) {
            return false;
        }

        return $res[0];
    }

    /**
     * @param $where
     * @param string $field
     * @return bool
     * author ZBY
     * Title  获取所有游戏记录信息
     * Date 2019/8/28 0028 上午 10:29
     */
    public function getList($where = '', $field = '*')
    {
        $where = $where ? "WHERE {$where}" : '';
        $sql = "SELECT {$field} FROM {$this->table} {$where}";

        $res = $this->db->query($sql);
//        $this->db->getDb()->release();

        if (!$res) {
            return false;
        }

        return $res;
    }
}