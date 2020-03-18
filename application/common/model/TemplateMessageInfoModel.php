<?php

namespace app\common\model;

use think\Model;

/**
 * stemplate_message_info模型类
 * @author wenhao
 *
 */
class TemplateMessageInfoModel extends Model
{
    protected $name = 'template_message_info';


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
}