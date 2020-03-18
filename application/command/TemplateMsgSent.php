<?php
namespace app\command;

use app\common\controller\WechatFunBase;
use app\common\model\TemplateMessageInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 群发模板消息的command定时任务
 * Class TemplateMsgSent
 * @package app\common\command
 */
class TemplateMsgSent extends Command
{
    protected function configure()
    {
        $this->setName('templateMsgSent')
            ->setDescription('seet wechat template message to all ');
    }

    protected function execute(Input $input, Output $output)
    {
        //查找出未发送 并且时间小于当前时间的
        $noSentInfo = TemplateMessageInfoModel::where(['sent_status'=>0])
            ->whereTime('sent_time','<',time())
            ->order('sent_time','desc')
            ->limit(1)
            ->select();
        //更新状态
        if(!empty($noSentInfo)){
            $updateIds = [];
            foreach($noSentInfo as $v){
                array_push($updateIds,$v['id']);
            }
            $newstr = join(',',$updateIds);
            TemplateMessageInfoModel::where('id','in',$newstr)
                ->setField('sent_status',1);
        }

        //用来保存已经查询过的公众号刷新令牌
        $appid_authorizer_refresh_token = [];
        foreach ($noSentInfo as $gzhinfokey => $gzhinfovalue) {
            //查出当前归属于当前公众号的用户
            $userinfo = WechatUserInfoModel::field('openid,nickname')
                ->where(['appid' => $gzhinfovalue['appid']])
//                ->whereTime('active_time','>',strtotime('-24hour',time()))
                ->where(['subscribe' => 1])
                ->select();

            //1.修改群发表中的发送中字段.
//            $nowidinfo = TemplateMessageInfoModel::find($gzhinfovalue['id']);
//            $nowidinfo->sent_status = 1;
//            $nowidinfo->save();


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
            foreach ($userinfo as $userkey => $uservalue) {
                $template_var_content = $gzhinfovalue['template_var_content'];
                //为空的话跳出本次循环
                if(empty($template_var_content)){
                    //1.修改群发表中的发送失败字段.
                    $nowidinfo = TemplateMessageInfoModel::find($gzhinfovalue['id']);
                    $nowidinfo->sent_status = 3;
                    $nowidinfo->save();
                    continue;
                }
                //取出来的时候先反序列化
                $template_var_content = unserialize($template_var_content);
                //不是数组的话 跳出本次循环
                if(!is_array($template_var_content)){
                    //1.修改群发表中的发送失败字段.
                    $nowidinfo = TemplateMessageInfoModel::find($gzhinfovalue['id']);
                    $nowidinfo->sent_status = 3;
                    $nowidinfo->save();
                    continue;
                }
                //根据openid 一个个的去发送.
                $newContnet = [];
                /*****转换为微信需要的格式*******/
                foreach ($template_var_content as $conentk => $conentv) {
                    if(strpos($conentv['value'],'wechatusername') !== false){
                        $template_var_content[$conentk]['value'] = $uservalue['nickname'];
                    }
                }
                /*****************结束*******************/

                $result = WechatFunBase::send_template_msg($gzhinfovalue['appid'],$authorizer_refresh_token,$uservalue['openid'],$gzhinfovalue['template_id'],$gzhinfovalue['redirect_url'],$template_var_content);
                if(empty($result['errcode'])){
                    $sentSuccessNum++;
                }
            }

            //1.修改群发表中的发送成功字段.
            $nowidinfo = TemplateMessageInfoModel::find($gzhinfovalue['id']);
            $nowidinfo->sent_status = 2;
            $nowidinfo->sent_num = $sentSuccessNum;
            $nowidinfo->save();
        }
    }
}