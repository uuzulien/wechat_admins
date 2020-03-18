<?php
namespace app\command;


use app\common\controller\WechatFunBase;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 检测用户的数量是否对的上, 如果对不上的话把微信公众号的is_get_user 改为 0 然后等待 getopenid定时任务重新爬取粉丝.
 * Class CheckUserCount
 * @package app\command
 */
class CheckUserCount extends Command
{
    protected function configure()
    {
        $this->setName('checkUserCount')
            ->setDescription('check user count is success or error. if error so agein get user for wechat');
    }

    protected function execute(Input $input, Output $output)
    {
        //查出已经获取过用户的公众号
        $gzhlists = WechatEmpowerInfoModel::field('id,authorizer_refresh_token,auth_appid,nick_name')
            ->where(['is_get_user'=>1])
            ->where(['user_group'=>3])
            ->select();
        foreach ($gzhlists as $k => $v) {
            $user_list = WechatFunBase::get_wechat_user_list($v['auth_appid'],$v['authorizer_refresh_token']);
            //获取关注该公众号的用户总数
            if(!isset($user_list['total']) || empty($user_list['total'])){
                $output->writeln("错误错误错误了啊啊啊啊1：{$v['nick_name']}");
                continue;
            }
            $total = (int)$user_list['total'] ?? '';
            $output->writeln("粉丝总数量：{$total}");
            if(empty($total || $total <=0)){
                $output->writeln("错误错误错误了啊啊啊啊：{$v['nick_name']}");
                continue;
            }
            if(!is_numeric($total)){
                $output->writeln("不是数字的内容：{$total},appid为：{$v['nick_name']}");
                continue;
            }
            //获取该公众号已经拉取的粉丝总数
            $count = WechatUserInfoModel::where(['appid'=>$v['auth_appid']])
                ->where(['subscribe'=>1])
                ->count();
            //如果相差数量大于三 那么就得重新拉取了
            $cha = $total - $count;
            if($cha > 2){
                file_put_contents('needAgeingetuser.log',print_r($v['auth_appid'].'->>>>>>>>>'.$v['nick_name'].'->>>>>>>>>>'.'相差了'.$cha.'->>>>>>>>>>>'.date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
                $nowwecahtinfo = WechatEmpowerInfoModel::field('id')->find($v['id']);
                $nowwecahtinfo->is_get_user = 0;
                $nowwecahtinfo->save();
            }
            $output->writeln("{$v['nick_name']}已经执行完毕,相差了:{$cha}");
        }
    }
}