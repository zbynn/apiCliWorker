<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;

use App\Dao\Dao;

class TableInfo
{
    //表名
    public $table;
    //mysql对象
    public $db;

    public function init($db = 'db_game', $table = ''){
        $this->table = $table;
        $this->db    = (new Dao($db))->getDb();
        return $this->db;
    }

    /**
     * @param $gamenumber
     * @return mixed
     * author ZBY
     * Title  检测表是否存在
     * Date 2019/8/28 0028 上午 10:59
     */
    public function checkTableExit()
    {
        $ckSQL     = "SHOW TABLES LIKE '%" . $this->table . "%'";
        $tableexit = $this->db->query($ckSQL);
//        $this->db->getDb()->release();
        return $tableexit;
    }

    /**
     * @param string $field
     * @return mixed
     * author ZBY
     * Title  检测字段是否存在
     * Date 2019/8/28 0028 上午 11:34
     */
    public function checkFieldExit($field = '')
    {
        $sql       = "select count(*) as count from information_schema.columns where table_name ='{$this->table}' and column_name ='{$field}'";
        $fieldexit = $this->db->query($sql);
//        $this->db->getDb()->release();
        return $fieldexit;
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
    public function update($where = '', array $array)
    {
        $strUpdateFields = '';
        foreach ($array as $key => $value) {
            $strUpdateFields .= "`{$key}` = '{$value}',";
        }
        $strUpdateFields = rtrim($strUpdateFields, ',');
        $where           = $where ? "WHERE {$where}" : '';
        $sql             = "UPDATE {$this->table} SET {$strUpdateFields} {$where}";
        $res             = $this->db->query($sql);
        if ($res === false) {
            return false;
        }
//        $this->db->getDb()->release();
        return $res['affected_rows'];
    }

    /**
     * author ZBY
     * Title  获取数据库连接对象
     * Date 2019/8/28 0028 上午 11:35
     */
    public function getDbObject()
    {
        return $this->db;
    }
}