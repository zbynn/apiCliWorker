<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;

use App\Dao\Dao;
use Core\Config;
use App\Dao\Model\Redis;

class UserInfo
{
    //表名
    public $table;
    //mysql对象
    public $db;

    //redis实例
    public $redis;

    public $lockString;


    public function __construct()
    {
        $this->table = 'user_info';
        $this->db    = (new Dao('db_game'))->getDb();
        $this->lockString = 'Lock:insert:userinfo';


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
    public function getInfo($where='', $field = '*',$lock = '')
    {
        $where = $where ? "WHERE {$where}" : '';
        $lock = $lock ?: '';
        $sql = "SELECT {$field} FROM {$this->table} {$where} {$lock}";

        $res = $this->db->query($sql);
//        $this->db->getDb()->release();

        if(!$res){
            return false;
        }

        return $res[0];
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
        //业务锁
        (new Redis)->addLock($this->lockString);
        $strFields = '`' . implode('`,`', array_keys($data)) . '`';
        $strValues = "'" . implode("','", array_values($data)) . "'";
        $sql = "INSERT INTO {$this->table} ({$strFields}) VALUES ({$strValues})";
        $res = $this->db->query($sql);
//        $this->db->getDb()->release();
        (new Redis)->delLock($this->lockString);
        if (!empty($res['insert_id'])) {
            return $res['insert_id'];
        }

        return false;
    }

    /**
     * @param array $array
     * @param $where
     * @return mixed
     * @throws \Exception
     * author ZBY
     * Title  修改一条数据
     * Date 2019/8/27 0027 下午 5:20
    */
    public function update($where = '',array $array)
    {
        $strUpdateFields = '';
        foreach ($array as $key => $value) {
            $strUpdateFields .= "`{$key}` = '{$value}',";
        }
        $strUpdateFields = rtrim($strUpdateFields, ',');
        $where = $where ? "WHERE {$where}" : '';
        $sql = "UPDATE {$this->table} SET {$strUpdateFields} {$where}";
        $res = $this->db->query($sql);
        if($res === false){
            return false;
        }
//        $this->db->getDb()->release();
        return $res['affected_rows'];
    }

    /**
     * @param $data
     * @return bool
     * author ZBY
     * Title  插入或更新 方法，避免全表锁
     * Date 2019/9/6 0006 下午 6:22
    */
    public function insertOrUpdate($data)
    {
        //业务锁
        (new Redis)->addLock($this->lockString);
        $strFields = '`' . implode('`,`', array_keys($data)) . '`';
        $strValues = "'" . implode("','", array_values($data)) . "'";
        $sql = "INSERT INTO {$this->table} ({$strFields}) VALUES ({$strValues})";

        $res = $this->db->query($sql);
        (new Redis)->delLock($this->lockString);
        if (!empty($res['insert_id'])) {
            return $res['insert_id'];
        }

        return false;
    }
}