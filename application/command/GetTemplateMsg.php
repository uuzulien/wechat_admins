<?php
namespace app\command;

use app\common\model\TemplateMessageInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;

class GetTemplateMsg extends Command
{
    protected function configure()
    {
        $this->setName('getTemplateMsg')
            ->setDescription('send');
    }

    protected function execute(Input $input, Output $output)
    {
        $ququename = 'TemplateMsgJob';
        $connection = new AMQPStreamConnection('localhost', 5672, Config::get('rabbitmqinfo.username'),Config::get('rabbitmqinfo.password'));
        $channel = $connection->channel();
        $channel->queue_declare($ququename, false, true, false, false);
        //查找出未发送 并且时间小于当前时间的
        $noSentInfo = TemplateMessageInfoModel::where(['sent_status'=>0])
            ->whereTime('sent_time','<',time())
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
            $template_var_content = $noSentInfo['template_var_content'];
            //为空的话跳出本次循环
            if(empty($template_var_content)){
                //1.修改群发表中的发送失败字段.
                $nowidinfo = TemplateMessageInfoModel::find($noSentInfo['id']);
                $nowidinfo->sent_status = 3;
                $nowidinfo->save();
                return;
            }
            //取出来的时候先反序列化
            $template_var_content = unserialize($template_var_content);
            //不是数组的话 跳出本次循环
            if(!is_array($template_var_content)){
                //1.修改群发表中的发送失败字段.
                $nowidinfo = TemplateMessageInfoModel::find($noSentInfo['id']);
                $nowidinfo->sent_status = 3;
                $nowidinfo->save();
                return;
            }
            /*****转换为微信需要的格式*******/
            foreach ($template_var_content as $conentk => $conentv) {
                if(strpos($conentv['value'],'wechatusername') !== false){
                    $template_var_content[$conentk]['value'] = $userval['nickname'];
                }
            }
            /*****************结束*******************/

            $jobData = [
                'id' => $noSentInfo['id'],
                'appid' => $noSentInfo['appid'],
                'authorizer_refresh_token' => $authorizer_refresh_token,
                'openid' => $userval['openid'],
                'template_id' =>$noSentInfo['template_id'],
                'redirect_url' =>$noSentInfo['redirect_url'],
                'content' => $template_var_content,
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
