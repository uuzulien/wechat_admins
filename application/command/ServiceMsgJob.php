<?php
namespace app\command;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\tool\Wlog;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\cache\driver\Redis;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Config;

class ServiceMsgJob extends Command
{
    protected function configure()
    {
        $this->setName('serviceMsgJob')
            ->setDescription('serviceMsg receive.');
    }

    protected function execute(Input $input, Output $output)
    {
        $ququename = 'ServiceMsgJob';
        $connection = new AMQPStreamConnection('localhost', 5672, Config::get('rabbitmqinfo.username'), Config::get('rabbitmqinfo.password'));
        $channel = $connection->channel();

        $channel->queue_declare($ququename, false, true, false, false);

        $redis = Base::get_redis();
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $callback = function($msg) use ($redis){
            $data = json_decode($msg->body,true);
            $result = WechatFunBase::sent_service_msg($data['appid'],$data['authorizer_refresh_token'],$data['msg_type'],$data['openid'],$data['content']);
            //成功记录 id的成功次数。
            $key = CacheKeyConstant::SERVICE_MSG.$data['id'];
            if($result === true){
                $rediskeyinfo = $redis->get($key);
                if($rediskeyinfo){
                    $rediskeyinfo = explode('_',$rediskeyinfo);
                    $newcount = $rediskeyinfo[0] + 1;
                    $newvalue = $newcount.'_'.time();
                    $redis->set($key,$newvalue);
                }else{
                    $newvalue = '1_'.time();
                    $redis->set($key,$newvalue);
                }
            }else{
                //就算失败也要记录发送为0的状态.  这里一定要加if判断 如果他没有记录过才记录一次 否则不能在记录了
                $rediskeyinfo = $redis->get($key);
                if(!$rediskeyinfo){
                    $newvalue = '0_'.time();
                    $redis->set($key,$newvalue);
                }
                Wlog::write('serviceMsgQueueErrorLog',"客服消息id>>>>>{$data['id']}>>>>>>>>{$data['appid']}>>>>>{$data['openid']}>>>>>".json_encode($result));
                echo "发送失败~!@~!";
            }
            echo " [x] Done", "\n";
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($ququename, '', false, false, false, false, $callback);

        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}