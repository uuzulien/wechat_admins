<?php
namespace app\common\controller;


/**
 * 基础类
 * @author muyufeng
 */
class ApiBase   extends Base {

    public function initialize()
    {
        parent::initialize();
        header('Access-Control-Allow-Origin:http://localhost:8080');
        header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,PATCH,OPTIONS');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Headers:Content-Type, X-ELEME-USERID, X-Eleme-RequestID, X-Shard,X-Shard, X-Eleme-RequestID,X-Adminid,X-Token');
    }

}