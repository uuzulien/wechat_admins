<?php
namespace app\command;

use app\common\controller\WechatFunBase;
use app\common\model\ServiceMessageListModel;
use app\common\model\TemplateMessageInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;

/**
 * 每次获取一个要发送的内容, 建议定时任务 每十秒一次. 归属的队列名字:Servicemsgjob
 * Class GetServiceMsg
 * @package app\command
 */
class GetServiceMsg extends Command
{
    protected function configure()
    {
        $this->setName('getServiceMsg')
            ->setDescription('get service msg info and push redis');
    }

    protected function execute(Input $input, Output $output)
    {
        $ququename = 'ServiceMsgJob';
        $connection = new AMQPStreamConnection('localhost', 5672, Config::get('rabbitmqinfo.username'), Config::get('rabbitmqinfo.password'));
        $channel = $connection->channel();
        $channel->queue_declare($ququename, false, true, false, false);
        //查找出未发送 并且时间小于当前时间的
        $noSentInfo = ServiceMessageListModel::where(['sent_status'=>0])
            ->whereTime('sent_time','<',time())
            ->order('sent_time','desc')
            ->find();
        if(empty($noSentInfo)){
            return;
        }
        //更新状态
        $noSentInfo->sent_status = 1;
        $noSentInfo->save();
        //查出当前归属于当前公众号的用户
        $userinfo = WechatUserInfoModel::field('openid,nickname')
            ->where(['appid'=>$noSentInfo['appid']])
            ->whereTime('active_time','>',strtotime('-48hour',time()))
            ->where(['subscribe'=>1])
            ->select();
        if(count($userinfo) == 0){
            //更新状态
            $noSentInfo->sent_status = 3;
            $noSentInfo->save();
            return;
        }
        //查询当前openid的刷新令牌
        $authorizer_refresh_token = WechatEmpowerInfoModel::field('authorizer_refresh_token')
            ->where(['auth_appid'=>$noSentInfo['appid']])
            ->find()['authorizer_refresh_token'];
        foreach ($userinfo as $userkey => $userval) {
            //根据openid 一个个的去发送.
            if($noSentInfo['msg_type'] == 'text'){
                $contents = $noSentInfo['msg_content'];
                $contents = str_replace("wechatusername",$userval['nickname'],$contents);
            }else{
                $contents = unserialize($noSentInfo['msg_content']);
            }

            $jobData = [
                'id' => $noSentInfo['id'],
                'appid' => $noSentInfo['appid'],
                'authorizer_refresh_token' => $authorizer_refresh_token,
                'msg_type' => $noSentInfo['msg_type'],
                'openid' => $userval['openid'],
                'content' => $contents,
            ];
            $msg = new AMQPMessage(json_encode($jobData),
                array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
            );
            $channel->basic_publish($msg, '', $ququename);
        }
        $channel->close();
        $connection->close();

    }
}