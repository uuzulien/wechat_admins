<?php
namespace app\command;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class GetActiveFansAndSave extends Command
{
    protected function configure()
    {
        $this->setName('getActiveFansAndSave')
            ->setDescription('get active fans and insert db');
    }

    protected function execute(Input $input, Output $output)
    {

        $redis = Base::get_redis();
        $key = CacheKeyConstant::WECHAT_ACTIVE_FANS;
        //要进行判断的数量
        $checkCount = 50;
        //检测当前的字段中是否超过了{$checkCount}个,如果超过了{$checkCount}个 那么就进行存库操作.
        $count = $redis->HLEN($key);
        if($count >= $checkCount){
            //获取该appid下的所有openid
            $allOpenidInfo = $redis->HGETALL($key);
            $active_time_sql_str = "case openid";
            $openids_sql_str = '';
            $sum = 0;
            foreach ($allOpenidInfo as $openid=>$activetime) {
                $sum ++;
                $openids_sql_str .= "'{$openid}'".',';
                $active_time_sql_str .= " WHEN '{$openid}' THEN {$activetime} ";
                $redis->hDel($key,$openid);
                if($sum > 500){
                    break;
                }
            }
            $active_time = $active_time_sql_str.= "END";
            $openids = rtrim($openids_sql_str,',');
            $updateResult = Db::execute("update wechat_user_info set active_time={$active_time} where openid in ({$openids})");
            print_r($openids);
        }
    }
}