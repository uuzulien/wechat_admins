<?php
namespace app\command;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;


/**
 * 获取公众号所有关注的用户列表.
 * Class GetUserOenid
 * @package app\common\command
 */
class GetUserOpenid extends Command
{
    protected function configure()
    {
        $this->setName('getUserOpenid')
            ->setDescription('get wechat public num all user');
    }

    protected function execute(Input $input, Output $output)
    {
        //获取合适的公众号
        $wechats = $this->get_wechats();
        dump($wechats);
        //遍历公众号并获取该公众号的粉丝openid
        $redis   = Base::get_redis();
        //redis队列的键名
        $key = CacheKeyConstant::WECHAT_OPENID_LIST;
        foreach ($wechats as $k => $v) {
            //循环拿到当前appid的所有openid. (这里记坑: 这里不可以用递归., 因为静态变量的数据会一直递增.)
            $list = $this->getuseropenidlist($v['auth_appid'],$v['authorizer_refresh_token']);
            /*$list = [
                [
                    'openid' => [
                        'openid1',
                        ...
                        'openid10000',
                    ]
                ],
            ];*/
            $redisdata = [
                $v['auth_appid'] => $list
            ];
            //将组合的数组 json格式化一下
            $redisdata = json_encode($redisdata);
            dump($redisdata);
            /*Log::error('获取到的jeson格式数据execute:$redisdata↓:');
            Log::error($redisdata);
            Log::error('获取到的jeson格式数据↑:');*/
            //根据授权公众号appid 通过回调函数 将数组保存到redis 队列中
            $redis->lPush($key, $redisdata);
            /*$redis_array = $redis->lRange($key, 0, -1);
            Log::error('获取到的redis中数据↓:');
            Log::error($redis_array);
            Log::error('获取到的redis中数据↑:');*/

        }
    }

    /**
     * 循环整合所有的openid
     * @param $suth_appid
     * @param $authorizer_refresh_token
     * @param string $next_openid
     * @return array
     * @throws \app\common\exception\JsonException
     */
    protected function getuseropenidlist($suth_appid,$authorizer_refresh_token,$next_openid = '')
    {
        //这个变量用于存放获取到的数据
        $resultArr = [];
        //用于统计获取了多少数据了
        $sum = 0;
        //先获取第一次
        $list = WechatFunBase::get_wechat_user_list($suth_appid,$authorizer_refresh_token,$next_openid);
        //存储获取到的下一个next_openid
        $next_openid = $list['next_openid'];
        $sum += $list['count'];
        //openid加到数组里面，
        array_push($resultArr,$list['data']['openid']);
        //总数减去已经获取的数量 如果不等于0的话 说明还有 继续获取就完事了
        if(($list['total'] - $sum) != 0){
            //先减去第一次获取的 1w个
            $nowTotal = $list['total'] - 10000;
            //公众号那边每次最多能获取一万个 所以 总数/1w 向上取整, 就是要循环的次数
            for ($i = 0; $i<ceil($nowTotal / 10000); $i++) {
                $list = WechatFunBase::get_wechat_user_list($suth_appid,$authorizer_refresh_token,$next_openid);
                $next_openid = $list['next_openid'];
                $sum += $list['count'];
                array_push($resultArr,$list['data']['openid']);
            }
        }
        return $resultArr;
    }


    /**
     *
     */
    public function get_wechats(){
        //查询50个公众号
        $wechatpublicnuminfo = WechatEmpowerInfoModel::field('authorizer_refresh_token,auth_appid')
            ->where('is_get_user','neq',1)
            ->order('id asc')
            ->limit(0,10)
            ->select();
        //更新使用状态
        if(!empty($wechatpublicnuminfo)){
            foreach($wechatpublicnuminfo as $v){
                //之后优化成批量更新
                $map['auth_appid'] = $v['auth_appid'];
                WechatEmpowerInfoModel::where('is_get_user','neq',1)
                    ->where($map)
                    ->order('id asc')
                    ->setField('is_get_user',1);
            }
        }
        return $wechatpublicnuminfo;
    }
}