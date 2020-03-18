<?php
namespace app\command;

use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\model\ServiceMessageListModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 客服消息发送.
 * Class GetUserOenid
 * @package app\common\command
 */
class ServiceMsgSent extends Command
{
    protected function configure()
    {
        $this->setName('serviceMsgSent')
            ->setDescription('seet wechat service message to all ');
    }
    protected function execute(Input $input, Output $output)
    {
        //查找出未发送 并且时间小于当前时间的
        $noSentInfo = ServiceMessageListModel::where(['sent_status'=>0])
            ->whereTime('sent_time','<',time())
            ->order('sent_time','desc')
            ->limit(3)
            ->select();
        //更新状态
        if(!empty($noSentInfo)){
            $updateIds = [];
            foreach($noSentInfo as $v){
                array_push($updateIds,$v['id']);
            }
            $newstr = join(',',$updateIds);
            ServiceMessageListModel::where('id','in',$newstr)
                ->setField('sent_status',1);
        }
        //用来保存已经查询过的公众号刷新令牌
        $appid_authorizer_refresh_token = [];
        foreach ($noSentInfo as $gzhinfokey => $gzhinfovalue) {
            //查出当前归属于当前公众号的用户
            $userinfo = WechatUserInfoModel::field('openid,nickname')
                ->where(['appid'=>$gzhinfovalue['appid']])
                ->whereTime('active_time','>',strtotime('-48hour',time()))
                ->where(['subscribe'=>1])
                ->select();

            //查询当前openid的刷新令牌
            //先查询本地的数组有没有存储 已经查询过的。
            if(isset($appid_authorizer_refresh_token[$gzhinfovalue['appid']])){
                $authorizer_refresh_token = $appid_authorizer_refresh_token[$gzhinfovalue['appid']];
            }else{
                $authorizer_refresh_token = WechatEmpowerInfoModel::field('authorizer_refresh_token')
                    ->where(['auth_appid'=>$gzhinfovalue['appid']])
                    ->find()['authorizer_refresh_token'];
                $appid_authorizer_refresh_token[$gzhinfovalue['appid']] = $authorizer_refresh_token;
            }

            $sentSuccessNum = 0;
            foreach ($userinfo as $userkey => $userval) {
                //根据openid 一个个的去发送.
                if($gzhinfovalue['msg_type'] == 'text'){
                    $contents = $gzhinfovalue['msg_content'];
                    $contents = str_replace("wechatusername",$userval['nickname'],$contents);
                }else{
                    $contents = unserialize($gzhinfovalue['msg_content']);
                }
                $result = WechatFunBase::sent_service_msg($gzhinfovalue['appid'],$authorizer_refresh_token,$gzhinfovalue['msg_type'],$userval['openid'],$contents);
                if($result === true){
                    $sentSuccessNum++;
                }
            }

            /*//要发送的东西
            if($gzhinfovalue['msg_type'] == 'text'){
                $contents = $gzhinfovalue['msg_content'];
            }else{
                $contents = unserialize($gzhinfovalue['msg_content']);
            }


            $sentSuccessNum = 0;
            foreach ($userinfo as $userkey => $userval) {
                $contents = str_replace("{wechatusername}",$userval['nickname'],$contents);
                $result = WechatFunBase::sent_service_msg($gzhinfovalue['appid'],$authorizer_refresh_token,$gzhinfovalue['msg_type'],$userval['openid'],$contents);
                if($result === true){
                    $sentSuccessNum++;
                }
            }*/

            //1.修改群发表中的发送成功字段.
            $nowidinfo = ServiceMessageListModel::find($gzhinfovalue['id']);
            $nowidinfo->sent_status = 2;
            $nowidinfo->sent_num = $sentSuccessNum;
            $nowidinfo->save();
        }
    }
}