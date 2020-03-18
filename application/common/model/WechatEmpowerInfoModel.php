<?php
namespace app\common\model;

use think\Model;

/**
 * GzhInfo模型类
 * @author wenhao
 *
 */
class WechatEmpowerInfoModel   extends Model
{
    protected $name = 'wechat_empower_info';

    public function getCreateTimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }

    /**
     * 计算活跃粉丝数量的获取器
     * @param $value
     * @param $data
     * @return float|string
     */
    public function getActiveFansCountAttr($value,$data)
    {
        $count = WechatUserInfoModel::where(['appid'=>$data['auth_appid']])
            ->whereTime('active_time','>',strtotime('-24hour'))
            ->count();
        return $count;
    }


    public static function saveWechatEmpowerUseUserId($ids,$userid)
    {
        $saveAllDatas = [];
        foreach ($ids as $k => $v) {
            array_push($saveAllDatas,[
                'id'=>$v,
                'use_user_id'=>$userid
            ]);
        }
        if(!empty($saveAllDatas)){
            $wechatEmpowerInfoModel = new WechatEmpowerInfoModel();
            $wechatEmpowerInfoModel->saveAll($saveAllDatas);
        }
    }
}