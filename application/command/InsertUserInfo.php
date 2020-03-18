<?php
namespace app\command;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\exception\JsonException;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\facade\Log;

/**
 * 获取公众号所有关注的用户列表.
 * @package app\common\command
 */
class InsertUserInfo extends Command
{
    protected function configure()
    {
        $this->setName('insertUserInfo')
            ->setDescription('command:InsertUserInfo');
    }
    protected function execute(Input $input, Output $output)
    {
        //初始化redis
        $redis   = Base::get_redis();
        //redis队列的键名
        $key = CacheKeyConstant::WECHAT_OPENID_LIST;
        // 循环该appid下所有数据 出栈并调用微信接口批量查询用户信息
        for($i = 0; $i < 10; $i++){
            $redis_data = $redis->rPop($key);
            if(empty($redis_data)){
                continue;
            }
            //将出列的数据转换为数组形式
            $redis_data = json_decode($redis_data,true);
            //取他的键, (appid)
            $auth_appid = array_keys($redis_data)[0];
            //获取键下的值, 也就是 N个openid (是个数组)
            $openids = array_values($redis_data)[0];
            //讲N个openid合并成一个数组.
            $openids = array_reduce($openids,'array_merge',[]);
            //定义一个临时变量， 失败的时候存储到这个变量中, 之后在重新处理入列.
            $tmp = [];
            //获取authorizer_refresh_token;(刷新令牌s)
            $authorizer_refresh_token  = WechatEmpowerInfoModel::where(['auth_appid'=>$auth_appid])->value('authorizer_refresh_token');
            //每次取出100条openid
            for($J = 0; $J < ceil( count($openids)/100);$J++){
                //切割一百个openid
                $openids_each = array_slice($openids,$J*100,100);

                //把他每一个openid切割成一个数组 并修改其键(供微信使用.)
                foreach ($openids_each as $k=>$v) {
                    $openids_each[$k] = [
                        'openid' => $v,
                    ];
                }
                dump($openids_each);

                //获取到一百个openid,发起请求,获取用户详细信息 验证并加入数据库
                $result = $this -> get_user_info($auth_appid,$authorizer_refresh_token,$openids_each);
                if($result === false){
                    file_put_contents('getwechatuserinfoErrortoappid.log',print_r($auth_appid.'->>>'.date('Y-m-d H:i:s').'\r\n',true),FILE_APPEND);
                }
//                if($result){
//                    //添加成功一条 之后可以做相对应的业务逻辑, 比如 成功每一次 都记录下.
//                }else{
//                    //先临时定义一个数组 然后把失败的先加入到数组(以入列的格式) foreach结束之后 把他再次入列
//                    array_push($tmp,$result);
//                }
            }
        }
    }


    /**
     * 调用微信接口 查询用户详细信息 存入数据库
     * @param $auth_appid
     * @param $authorizer_refresh_token
     * @param $openids_each
     * @return boolean
     * @throws JsonException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    private function get_user_info($auth_appid,$authorizer_refresh_token,$openids_each)
    {
        //获取用户详细信息 100个openid
        $userlist = WechatFunBase::batch_get_wechat_user_info($auth_appid,$authorizer_refresh_token,$openids_each);
        if(!empty($userlist['errcode'])){
            return false;
        }
        $user_infos = $userlist['user_info_list'];
        if($user_infos){
            foreach($user_infos as $user_info_k=>$user_info) {
                // 比对现有用户信息
                $nowUserInfo = WechatUserInfoModel::field('id')
                    ->where(['openid' => $user_info['openid']])
                    ->find();
                //判断是否请求成功,判断该用户是否有效用户
                if ($user_info['subscribe'] == 0) {
                    continue;
                }
                if (empty($nowUserInfo)) {
                    $nowUserInfo = new WechatUserInfoModel();
                }else {
                    continue;
                }

                try{
                    //加入数据表
                    $nowUserInfo->subscribe = $user_info['subscribe'] ?? '';
                    $nowUserInfo->openid = $user_info['openid'] ?? '';
                    $nowUserInfo->nickname = $user_info['nickname'] ?? '';
                    $nowUserInfo->sex = $user_info['sex'] ?? '';
                    $nowUserInfo->city = $user_info['city'] ?? '';
                    $nowUserInfo->country = $user_info['country'] ?? '';
                    $nowUserInfo->province = $user_info['province'] ?? '';
                    $nowUserInfo->language = $user_info['language'] ?? '';
                    $nowUserInfo->headimgurl = $user_info['headimgurl'] ?? '';
                    $nowUserInfo->subscribe_time = $user_info['subscribe_time'] ?? '';
                    $nowUserInfo->unionid = $user_info['unionid'] ?? '';
                    $nowUserInfo->remark = $user_info['remark'] ?? '';
                    $nowUserInfo->groupid = $user_info['groupid'] ?? '';
                    $nowUserInfo->tagid_list = $user_info['tagid_list'] ?json_encode($user_info['tagid_list']): '';
                    $nowUserInfo->subscribe_scene = $user_info['subscribe_scene'] ?? '';
                    $nowUserInfo->qr_scene = $user_info['qr_scene'] ?? '';
                    $nowUserInfo->qr_scene_str = $user_info['qr_scene_str'] ?? '';
                    $nowUserInfo->appid = $auth_appid ?? '';
                    $nowUserInfo->create_time = time();
                    $res = $nowUserInfo->save();
                    if(!$res){
                        file_put_contents('wenhaoceshi999.log',print_r($user_info['openid'].date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
                    }
                }catch (\Exception $e){
                    file_put_contents('wenhaoceshi.log',print_r($e->getMessage().date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
                }
                /*Log::error('写入数据库数据分析↓:');
                Log::error($nowUserInfo);
                Log::error('写入数据库数据分析↑:');*/

            }
        }else{
            //如果获取失败 记录失败的 openids和appid
            file_put_contents('getwechatuserinfoErrortoOpenData.log',print_r($auth_appid.'->>>>>>>>>'.json_encode($openids_each).'->>>'.date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
            return false;
        }
        sleep(1);
        return true;
    }

}