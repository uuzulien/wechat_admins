<?php

namespace app\common\model;

use think\facade\Config;
use think\Model;

/**
 * service_message_list模型类
 * @author wenhao
 *
 */
class ServiceMessageListModel extends Model
{
    protected $name = 'service_message_list';



    public function getCreateTimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }

    public function getRealSentTimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }

    public function getSentTimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }

    public function getWechatNickNameAttr($value,$data)
    {
        $name = WechatEmpowerInfoModel::field('nick_name')
            ->where(['auth_appid'=>$data['appid']])
            ->find();
        return $name['nick_name'] ?? '';
    }
    public function getHandleUserNameAttr($value,$data)
    {
        $name = AdminModel::field('name')
            ->find($data['handle_user_id']);
        return $name['name'] ?? '';
    }

    public function getSiteTypeNameAttr($value,$data)
    {
        $name = WechatPlatform::field('name')
            ->find($data['site_type']);
        return $name['name'] ?? '';
    }
    public function getTaskTypeNameAttr($value,$data)
    {
        $name = Config::get('dictionary.serviceMessageList_task_type')[$data['task_type']];
        return $name ?? '';
    }
}