<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;

use App\Dao\Dao;

class LogUserLogon
{
    //表名
    public $table;
    //mysql对象
    public $db;

    private static $instance;
    public function __construct()
    {
        $this->table = 'gs_log_user_logon';
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
     * Title  添加一条数据
     * Date 2019/8/27 0027 上午 10:50
     */
    public function insert($data)
    {
        $strFields = '`' . implode('`,`', array_keys($data)) . '`';
        $strValues = "'" . implode("','", array_values($data)) . "'";
        $sql       = "INSERT INTO {$this->table} ({$strFields}) VALUES ({$strValues})";

        $res = $this->db->query($sql);
//        $this->db->getDb()->release();

        if (!empty($res['insert_id'])) {
            return $res['insert_id'];
        }

        return false;
    }
}