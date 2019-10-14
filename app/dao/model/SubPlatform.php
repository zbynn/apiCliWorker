<?php
/**
 * author
 * Date: 2019/8/27 0027
 * Time: 上午 10:41
 */

namespace App\Dao\Model;

use App\Dao\Dao;
use App\Dao\Model\UserInfo;
use App\Dao\Model\GameRecord;
use chan;

class SubPlatform
{
    //表名
    public $table;
    //mysql对象
    public $db;

    private static $instance;

    public function __construct()
    {
        $this->table = 'gs_subplatform';
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
     * Title  获取子平台信息
     * Date 2019/8/27 0027 上午 10:50
     */
    public function getInfo($where, $field = '*')
    {
        $where = $where ? "WHERE {$where}" : '';
        $sql   = "SELECT {$field} FROM {$this->table} {$where} limit 0,1";

        $res = $this->db->query($sql);
//        $this->db->release();

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
     * Title  获取所有子平台信息
     * Date 2019/8/28 0028 上午 10:29
     */
    public function getList($where = '', $field = '*',$lock = '')
    {
        $where = $where ? "WHERE {$where}" : '';
        $lock = $lock ?: '';
        $sql = "SELECT {$field} FROM {$this->table} {$where} {$lock}";

        $res = $this->db->query($sql);
//        $this->db->getDb()->release();

        if (!$res) {
            return false;
        }

        return $res;
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

    /**
     * @param $platformid
     * @param $subchannel
     * @param $userid
     * @param bool $checkfield
     * @return array
     * author ZBY
     * Title  添加子平台
     * Date 2019/9/6 0006 下午 4:26
    */
    public function updateUserSubchannel($platformid, $subchannel, $userid, $checkfield = true)
    {
        //判断子包网是否已添加
        $subplatformWhere = "sp_subchannel='{$subchannel}' AND sp_pid={$platformid}";
        $subplatform      = $this->getInfo($subplatformWhere, 'sp_id');

        $userInfoModel = new UserInfo();

        if (!empty($subplatform)) {//已经添加子平台
            //更新用户数据
            $updateuserWhere              = "userId={$userid}";
            $updateuserData['subchannel'] = $subplatform['sp_id'];
            $updateuser                   = $userInfoModel->update($updateuserWhere, $updateuserData);

            if ($updateuser !== false) {
                return ['code' => '1', 'msg' => '更新用户数据成功！','data'=>$subplatform['sp_id']];
            } else {
                return ['code' => '400', 'msg' => '更新用户数据失败！'];
            }
        } else {
            $returnChan=new chan(1);
            go(function () use ($subchannel,$platformid,$checkfield,$returnChan){
                //未添加子平台
                $this->db->begin();
                //获取已添加的子平台ID并锁全表
                $_subchannellist = $this->getList('', 'sp_id','for update');
                $subchannellist  = $_subchannellist ? array_column($_subchannellist, 'sp_id') : [];

                //添加子平台数据
                $insertData['sp_subchannel'] = addslashes(trim($subchannel));
                $insertData['sp_pid']        = $platformid;
                $insertData['sp_createtime'] = date('Y-m-d H:i:s');;
                $subplatformid = $this->insert($insertData);
                array_push($subchannellist, $subplatformid);

                //更新game_config
                //查询所有游戏
                $gamelist = (new GameRecord())->getList('', 'game_number');
                $status   = true;

                if (empty($gamelist)) {
                    $this->db->rollback();
                    $returnChan->push(['code' => '400', 'msg' => '游戏记录表为空！']);
                    return ['code' => '400', 'msg' => '游戏记录表为空！'];
                }

                foreach ($gamelist as $gamekey => $gamevalue) {
                    $tableInfoModel = new TableInfo();
                    $tableInfoModelDb =$tableInfoModel->init('db_game', 'game_config_' . $gamevalue['game_number']);
                    //检测游戏配置表是否存在
                    $tableexit = $tableInfoModel->checkTableExit();

                    if (count($tableexit) <= 0) {
                        $this->db->rollback();
                        $returnChan->push(['code' => '400', 'msg' => "未找到游戏配置表：game_config_{$gamevalue['game_number']}！"]);
                        return ['code' => '400', 'msg' => "未找到游戏配置表：game_config_{$gamevalue['game_number']}！"];
                    }

                    //查询表字段是否存在
                    if ($checkfield) {
                        $fieldexit = $tableInfoModel->checkFieldExit('subchannel');
                        if ($fieldexit[0]['count'] <= 0) {
                            //表新增字段
                            $tableInfoModelDb->query("alter table game_config_" . $gamevalue['game_number'] . " add subchannel varchar(255) DEFAULT NULL COMMENT '子平台ID[int,int,int,...]'");
                        }
                    }
                    //更新game_config数据
                    $subchannel = json_encode($subchannellist,true);
                    $resultnum  = $tableInfoModel->update("channelId='{$platformid}'", ['subchannel' => $subchannel]);
                    if ($resultnum === false) {
                        $status = false;
                    }
                    $tableInfoModelDb->release();
                }

                if ($subplatformid !== false && $status == true) {
                    $this->db->commit();
                    $returnChan->push(['code' => '1', 'msg' => '更新用户数据成功！','data'=>$subplatformid]);
                    return ['code' => '1', 'msg' => '更新用户数据成功！','data'=>$subplatformid];
                } else {
                    $this->db->rollback();
                    $returnChan->push(['code' => '400', 'msg' => '更新用户数据失败！']);
                    return ['code' => '400', 'msg' => '更新用户数据失败！'];
                }
            });

        }

    }
}