<?php

namespace app\common\model;

use think\Model;

/**
 * GroupSentInfo模型类
 * @author wenhao
 *
 */
class GroupSentInfo extends Model
{
    protected $name = 'group_sent_info';

    public function getCreateTimeAttr($value)
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

    /**
     * 获取器, 【转换公众号的名字】
     */
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