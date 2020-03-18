<?php

namespace app\common\model;

use think\Model;

/**
 * wechat_user_info模型类
 * @author wenhao
 *
 */
class WechatUserInfoModel extends Model
{
    protected $name = 'wechat_user_info';

    public function getSubscribeTimeAttr($value)
    {
        if(empty($value)){
            return $value;
        }
        return date('Y-m-d H:i:s',$value);
    }
    /**
     * 取消关注.(取消关注的时候调用, 其他不可调用.)
     */
    public static function updateUsersubscribeStatus($appid,$openid)
    {
        $result = self::where(['openid'=>$openid])
            ->where(['appid'=>$appid])
            ->update(['subscribe'=>0,'subscribe_time'=>time()]);
        return $result;
    }

}