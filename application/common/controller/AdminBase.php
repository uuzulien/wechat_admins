<?php
namespace app\common\controller;


use app\common\enums\ErrorCode;
use app\common\exception\JsonException;

/**
 * 基础类
 * @author muyufeng
 */
class AdminBase   extends Base {

    public function initialize()
    {
        parent::initialize();
        header('Access-Control-Allow-Origin:http://localhost:8080');
        header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,PATCH,OPTIONS');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Headers:Content-Type, X-ELEME-USERID, X-Eleme-RequestID, X-Shard,X-Shard, X-Eleme-RequestID,X-Adminid,X-Token');
        if(is_null(session('user')) || '' === session('user')){
            echo "请登录";
            die();
        }
    }

    /**
     * 检测用户的权限(是否组长或者超级用户.)
     * @author  wh
     * @date 2019年12月24日18:12:12
     * @throws JsonException
     */
    public function checkUserPermissions()
    {
        //验证用户权限
        $user = session('user');
        //限制只有组长和管理员才能进行区分平台
        if($user['creater_id'] != 1 && $user['creater_id'] != 0){
            throw new JsonException(0,'当前用户无权限进行操作');
//            return error_result('','当前用户无权限进行区分操作');
        }
    }

}