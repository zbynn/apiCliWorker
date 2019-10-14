<?php
/**
 * author
 * Date: 2019/8/22 0022
 * Time: 下午 5:03
 */
namespace Controller;
use App\Dao\Dao;
use Controller\Base;

class Index extends Base {
    public function __construct()
    {
        parent::__construct(true);
    }

    public function index(){
//        $sql = "SELECT sum(sua_totalbet) as a from gs_statis_users_day";
//        $dao = Dao::getInstance('db_api');
//        $res=$dao->getDb()->query($sql);
//        $dao->getDb()->release();
//        $aaa['res'] = $res;
        return json_encode($this->requestResult);
    }
}