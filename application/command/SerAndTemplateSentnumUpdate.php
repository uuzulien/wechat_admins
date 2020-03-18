<?php
namespace app\command;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use app\common\model\ServiceMessageListModel;
use app\common\model\TemplateMessageInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SerAndTemplateSentnumUpdate extends Command
{
    protected function configure()
    {
        $this->setName('serAndTemplateSentnumUpdate')
            ->setDescription('update sent num of the service message and template message ');
    }

    protected function execute(Input $input, Output $output)
    {
        $redis = Base::get_redis();
        //客服消息的key
        $serviceMsgKey = CacheKeyConstant::SERVICE_MSG.'*';
        //模板消息的key
        $templateMsgKey = CacheKeyConstant::TEMPLATE_MSG.'*';
        //获取客服消息所有存储的信息
        $serviceContent = $redis->keys($serviceMsgKey);
        //获取模板消息所有存储的信息
        $templateContent = $redis->keys($templateMsgKey);

        //要修改的客服消息发送字段的id集合。用于批量修改
        $updateOfServiceIdArr = [];
        //要修改的模板消息发送字段的id集合。用于批量修改
        $updateOfTemplateIdArr = [];
        foreach ($serviceContent as $serviceval) {
            $serviceredisval = $redis->get($serviceval);
            $servicerediskeyArr = explode('_',$serviceval);
            $serviceredisvalArr = explode('_',$serviceredisval);
            //如果最后一次修改时间是一分钟之前 就代表已经执行完了 更新成功次数.
            if($serviceredisvalArr[1] < strtotime('-1minute')){
                //记录要更新的id
                array_push($updateOfServiceIdArr,[
                    'sent_num' => $serviceredisvalArr[0],
                    'id' => $servicerediskeyArr[2],
                    'sent_status' => 2,
                    'real_sent_time' => time(),
                ]);
                $redis->del($serviceval);
            }
        }
        foreach ($templateContent as $templateval) {
            $templateredisval = $redis->get($templateval);
            $templaterediskeyArr = explode('_',$templateval);
            $templateredisvalArr = explode('_',$templateredisval);
            //如果最后一次修改时间是一分钟之前 就代表已经执行完了 更新成功次数.
            if($templateredisvalArr[1] < strtotime('-1minute')){
                //记录要更新的id
                array_push($updateOfTemplateIdArr,[
                    'sent_num' => $templateredisvalArr[0],
                    'id' => $templaterediskeyArr[2],
                    'sent_status' => 2,
                    'real_sent_time' => time(),
                ]);
                $redis->del($templateval);
            }
        }

        //数据库相关处理:
        //客服消息发送人数的更改
        if(!empty($updateOfServiceIdArr)){
            $servicetable = new ServiceMessageListModel();
            $servicetable->saveAll($updateOfServiceIdArr);
        }
        //moban 消息发送人数的更改
        if(!empty($updateOfTemplateIdArr)){
            $templatetable = new TemplateMessageInfoModel();
            $templatetable->saveAll($updateOfTemplateIdArr);
        }
    }
}