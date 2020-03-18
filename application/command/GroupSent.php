<?php
namespace app\command;

use app\common\controller\WechatFunBase;
use app\common\model\GroupSentInfo;
use app\common\model\ServiceMessageListModel;
use app\common\model\WechatEmpowerInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 群发消息定时任务
 * Class GroupSent
 * @package app\command
 */
class GroupSent extends Command
{
    protected function configure()
    {
        $this->setName('groupSent')
            ->setDescription('seet wechat group message to all ');
    }
    protected function execute(Input $input, Output $output)
    {
        //查找出未发送 并且时间小于当前时间的
        $noSentInfo = GroupSentInfo::where(['bendi_sent_status'=>0])
            ->whereTime('sent_time','<',time())
            ->order('sent_time','desc')
            ->select();
        //更新状态
        if(!empty($noSentInfo)){
            $updateIds = [];
            foreach($noSentInfo as $v){
                array_push($updateIds,$v['id']);
            }
            $newstr = join(',',$updateIds);
            GroupSentInfo::where('id','in',$newstr)
                ->setField('bendi_sent_status',1);
        }
        //用来保存已经查询过的公众号刷新令牌
        $appid_authorizer_refresh_token = [];
        foreach ($noSentInfo as $gzhinfokey => $gzhinfovalue) {
            //1.修改群发表中的发送中字段.
//            $nowidinfo = GroupSentInfo::find($gzhinfovalue['id']);
//            $nowidinfo->bendi_sent_status = 1;
//            $nowidinfo->save();
            //查询当前appid的刷新令牌
            if(isset($appid_authorizer_refresh_token[$gzhinfovalue['appid']])){
                $authorizer_refresh_token = $appid_authorizer_refresh_token[$gzhinfovalue['appid']];
            }else{
                $authorizer_refresh_token = WechatEmpowerInfoModel::field('authorizer_refresh_token')
                    ->where(['auth_appid'=>$gzhinfovalue['appid']])
                    ->find()['authorizer_refresh_token'];
                $appid_authorizer_refresh_token[$gzhinfovalue['appid']] = $authorizer_refresh_token;
            }
//            $authorizer_refresh_token = WechatEmpowerInfoModel::field('authorizer_refresh_token')
//                ->where(['auth_appid'=>$gzhinfovalue['appid']])
//                ->find()['authorizer_refresh_token'];
            $seedAllResult = WechatFunBase::WechatPublicNumSendAll($gzhinfovalue['appid'],$authorizer_refresh_token,$gzhinfovalue['reply_type'],$gzhinfovalue['wechat_public_num_id'],$gzhinfovalue['reply_content']);
            $nowidinfo = GroupSentInfo::find($gzhinfovalue['id']);
            if($seedAllResult['errcode'] == 0){
                $nowidinfo->wechat_msg_id = $seedAllResult['msg_id'] ?? '';
                $nowidinfo->msg_data_id = $seedAllResult['msg_data_id'] ?? '';
                $nowidinfo->bendi_sent_status = 2;
                $nowidinfo->save();
            }else{
                $nowidinfo->bendi_sent_status = 3;
                $nowidinfo->save();
            }
        }
    }
}