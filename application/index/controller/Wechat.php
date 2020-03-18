<?php
namespace app\index\controller;

use app\common\constant\CacheKeyConstant;
use app\common\controller\IndexBase;
use app\common\controller\WechatFunBase;
use app\common\exception\JsonException;
use app\common\model\AdminModel;
use app\common\model\AutoReplyInfoModel;
use app\common\model\GroupSentInfo;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use app\common\tool\ResponseMes;
use think\facade\Log;
use think\Request;

class Wechat extends IndexBase{
    public function index()
    {

    }

    public function empower()
    {
        try{
            //获取传送数据
            $info = file_get_contents('php://input');
            //获取调试参数
            $get_info = $this->request->get();
            //获取配置
            $config = config('wechat.wechat_open');
            //初始化
            $code = new \WxBizMsgCrypt($config['token'],$config['encodingAesKey'],$config['appid']);
            //解码参数
            $ticket = '';
            $data = $code->decryptMsg($get_info['msg_signature'],$get_info['timestamp'],$get_info['nonce'],$info,$ticket);
            if($data == 0){
                $ticket_data = json_decode(json_encode(simplexml_load_string($ticket, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                $user = self::get_api_token('','empower',true);
                if($user){
                    //存在信息则更新
                    self::update_api_token('','empower',$ticket_data['ComponentVerifyTicket'],true);
                }else{
                    //不存在则创建
                    self::make_api_token('empower',$ticket_data['ComponentVerifyTicket'],true);
                }
            }
        }catch (\Exception $e){
            file_put_contents('log.log',print_r($e->getMessage().'msg',true),FILE_APPEND);
        }
        return 'success';
    }

    /**
     * 用户授权之后回调过来= -
     * @author wenhao
     * @param Request $request
     */
    public function notify(Request $request)
    {
        try{
            //获取授权的用户组组别
            $user_group = $request->param('user_group');
            if(empty($user_group) && $user_group != 0){
                throw new JsonException(1,'授权必须有声明是哪个用户组所授权');
            }
            //获取到授权码
            $auth_code = $request->param('auth_code');
            //获取到授权过期时间.(秒)
            $expires_in = $request->param('expires_in');

            //根据授权码获取授权方的appid
            $queryAuthResult = WechatFunBase::api_query_auth($auth_code);
            $authorizer = $queryAuthResult['authorization_info'];
            if(empty($authorizer)){
                return false;
            }
            $authorizer_appid = $authorizer['authorizer_appid'];
            //获取授权方的基本信息(所有都要进行入库操作.)
            $api_get_authorizer_all_info = WechatFunBase::api_get_authorizer_info($authorizer_appid);
            $api_get_authorizer_info = $api_get_authorizer_all_info['authorizer_info'];

            if(empty($api_get_authorizer_info)){
                return false;
            }


            //实例化gzhinfo表
            $gzhinfo = new WechatEmpowerInfoModel();
            //此处要查询是否已经授权过了.  (如果没授权过,添加. 否则修改.)
            $nowgzhinfo = $gzhinfo::field('id')->where(['auth_appid'=>$authorizer_appid])->find();
            //如果不存在这条信息
            if(!empty($nowgzhinfo)){
                $gzhinfo = $nowgzhinfo;
            }
            $gzhinfo->nick_name = $api_get_authorizer_info['nick_name'] ?? '';
            $gzhinfo->head_img = $api_get_authorizer_info['head_img'] ?? '';
            $gzhinfo->service_type_info = $api_get_authorizer_info['service_type_info']['id'] ?? '';
            $gzhinfo->verify_type_info = $api_get_authorizer_info['verify_type_info']['id'] ?? '';
            $gzhinfo->user_name = $api_get_authorizer_info['user_name']['id'] ?? '';
            $gzhinfo->principal_name = $api_get_authorizer_info['principal_name'] ?? '';
            $gzhinfo->alias = $api_get_authorizer_info['alias'] ?? '';
            $gzhinfo->business_info = json_encode($api_get_authorizer_info['business_info']) ?? '';
            $gzhinfo->qrcode_url = $api_get_authorizer_info['qrcode_url'] ?? '';
            $gzhinfo->user_group = $user_group ?? '';
            $gzhinfo->auth_code = $auth_code ?? '';
            $gzhinfo->auth_appid = $authorizer_appid ?? '';
            $gzhinfo->guoqi_time = strtotime("+{$expires_in}second");
            $gzhinfo->create_time = time();
            $gzhinfo->authorizer_refresh_token = $authorizer['authorizer_refresh_token'] ?? '';
            $gzhinfo->func_info = json_encode($api_get_authorizer_all_info['authorization_info']['func_info']) ?? '';
            $gzhinfo->save();

            $adminuser = AdminModel::field('id')->find(session('user')['id']);
            $adminuser->last_use_wechat_id = $gzhinfo->id;
            $adminuser->save();


            $data = [
                'id' =>$gzhinfo->id,
                'authorizer_refresh_token' =>$authorizer['authorizer_refresh_token'],
                'auth_appid' =>$authorizer_appid
            ];
            session('wechat',$data);
            header("Location: http://wechatadmins.weijuli8.com/#/empower");
        }catch (\Exception $e){
            file_put_contents('wenlog.log',print_r($e->getMessage().'msg',true),FILE_APPEND);
        }
    }

    /**
     * 消息与事件接收URL
     */

    public function msg_notify($appid = '')
    {
        //获取配置
        $config = config('wechat.wechat_open');
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['token'];
        $appId = $config['appid'];
        $timeStamp = request()->param('timestamp');
        $nonce = request()->param('nonce');
        $msg_sign = request()->param('msg_signature');
        $pc = new \WxBizMsgCrypt($token,$encodingAesKey,$appId);
        //获取到微信推送过来post数据（xml格式）
        $postArr = file_get_contents("php://input");	//接受xml数据
        $msg = '';
        $errCode= $pc->decryptMsgs($msg_sign, $timeStamp, $nonce, $postArr,$msg);
        Log::error('解析后的msg：'.$msg);
        if($errCode == 0){
            $postObj =simplexml_load_string($msg,'SimpleXMLElement',LIBXML_NOCDATA);

            if(empty($postObj)){
                return 'success';
            }
            /*******************记录活跃用户**********************/
            //用户id
            $userOpenid = $postObj->FromUserName;
            if(!empty($userOpenid)){
                //执行记录方法.
                $this->recordActiveFans($userOpenid,$timeStamp);
            }
            /***********************结束*****************************/
            //判断该数据包是否是订阅（用户关注）的事件推送
            if(strtolower($postObj -> MsgType) == 'event'){
                //如果是关注subscribe事件
                if(strtolower($postObj->Event == 'subscribe')){
                    //这里进行关注后的相关操作.
                    $this->guanzhuGzh($appid,$postObj->FromUserName,$timeStamp);


                    $cont = AutoReplyInfoModel::where(['appid'=>$appid])
                        ->where(['type'=>2])
                        ->where(['status'=>1])
                        ->find();
                    $reply_type = $cont['reply_type'];
                    switch ($reply_type) {
                        case 1:
                            echo ResponseMes::responseText($postObj,$cont['text_reply']);
                            break;
                        case 2:
                            echo ResponseMes::responseImg($postObj,$cont['mediaid_reply']);
                            break;
                        case 3:
                            echo ResponseMes::responseVioce($postObj,$cont['mediaid_reply']);
                            break;
                        case 4:
                            echo ResponseMes::responseNews($postObj,$cont['tuwen_reply']);
                            break;
                    }
                }
                //事件推送群发结果
                if(strtolower($postObj->Event == 'MASSSENDJOBFINISH')){
                    //保存群发的推送结果.
                    $msgId = $postObj->MsgID;
                    $status = $postObj->Status;
//                    $TotalCount = $postOb?j->TotalCount;
                    $SentCount = $postObj->SentCount;
                    $ErrorCount = $postObj->ErrorCount;
                    $sentinfo = GroupSentInfo::where(['appid'=>$appid])
                        ->where(['wechat_msg_id'=>$msgId])
                        ->find();
                    $sentinfo->sent_status = $status;
                    $sentinfo->sent_num = $SentCount;
                    $sentinfo->sent_error_num = $ErrorCount;
                    $sentinfo->save();
                }
                //取消关注公众号事件
                if( strtolower( $postObj->Event ) == 'unsubscribe' )
                {
                    //取消关注的话就把他的状态改一下子
                    WechatUserInfoModel::updateUsersubscribeStatus($appid,$postObj->FromUserName);
                }
            }

            //用户发送关键字的时候，回复图文消息
            if(strtolower($postObj-> MsgType) == 'text') {
                //当微信用户发送关键字，公众号回复对应内容
//                $appid = strval($postObj->ToUserName);
                $keyword = strval(trim($postObj->Content));
                //查询出关键字对应在表中的内容
                $keywordTableInfo = AutoReplyInfoModel::whereRaw("FIND_IN_SET('$keyword',keyword)")
                    ->where(['appid' => $appid])
                    ->where(['status'=>1])
                    ->find();
                //如果没有对应关键字, 那么就查找统一回复
                if (empty($keywordTableInfo)) {
                    $keywordTableInfo = AutoReplyInfoModel::where(['appid' => $appid])
                        ->where(['type' => 3])
                        ->where(['status'=>1])
                        ->find();
                    //如果也没用统一回复,那么就返回false (什么也不回复)
                    if(empty($keywordTableInfo)){
                        return;
                    }
                }
                $reply_type = $keywordTableInfo['reply_type'];
                switch ($reply_type) {
                    case 1:
                        echo ResponseMes::responseText($postObj, $keywordTableInfo['text_reply']);
                        break;
                    case 2:
                        echo ResponseMes::responseImg($postObj, $keywordTableInfo['mediaid_reply']);
                        break;
                    case 3:
                        echo ResponseMes::responseVioce($postObj, $keywordTableInfo['mediaid_reply']);
                        break;
                    case 4:
                        echo ResponseMes::responseNews($postObj, $keywordTableInfo['tuwen_reply']);
                        break;

                }
            }
        }
    }


    /**
     * 关注公众号的方法.
     */
    public function guanzhuGzh($appid,$openid,$timeStamp)
    {
        //根据公众号appid获取到刷新令牌
        $authorizer_token = WechatEmpowerInfoModel::field('authorizer_refresh_token')->where(['auth_appid'=>$appid])->find()['authorizer_refresh_token'];
        if(empty($authorizer_token)){
            return false;
        }
        //获取用户的详细信息
        $wxUserInfo = WechatFunBase::get_wechat_user_info($appid,$authorizer_token,$openid);
        $nowUserInfo = WechatUserInfoModel::field('id')
            ->where(['openid'=>$openid])
            ->where(['appid'=>$appid])
            ->find();
        if(empty($nowUserInfo)){
            $nowUserInfo = new WechatUserInfoModel();
        }
        $nowUserInfo->subscribe = $wxUserInfo['subscribe'] ?? '';
        $nowUserInfo->openid = $wxUserInfo['openid'] ?? '';
        $nowUserInfo->nickname = $wxUserInfo['nickname'] ?? '';
        $nowUserInfo->sex = $wxUserInfo['sex'] ?? '';
        $nowUserInfo->city = $wxUserInfo['city'] ?? '';
        $nowUserInfo->country = $wxUserInfo['country'] ?? '';
        $nowUserInfo->province = $wxUserInfo['province'] ?? '';
        $nowUserInfo->language = $wxUserInfo['language'] ?? '';
        $nowUserInfo->headimgurl = $wxUserInfo['headimgurl'] ?? '';
        $nowUserInfo->subscribe_time = $wxUserInfo['subscribe_time'] ?? '';
        $nowUserInfo->unionid = $wxUserInfo['unionid'] ?? '';
        $nowUserInfo->remark = $wxUserInfo['remark'] ?? '';
        $nowUserInfo->groupid = $wxUserInfo['groupid'] ?? '';
        $nowUserInfo->tagid_list = $wxUserInfo['tagid_list'] ?? '';
        $nowUserInfo->subscribe_scene = $wxUserInfo['subscribe_scene'] ?? '';
        $nowUserInfo->qr_scene = $wxUserInfo['qr_scene'] ?? '';
        $nowUserInfo->qr_scene_str = $wxUserInfo['qr_scene_str'] ?? '';
        $nowUserInfo->appid = $appid ?? '';
        $nowUserInfo->active_time = $timeStamp ?? time();
        $nowUserInfo->create_time = time();
        $nowUserInfo->save();
    }


    /**
     * 记录活跃粉丝
     * @param $openid //用户的openid
     * @param $time //交互的时间（时间戳）
     */
    private function recordActiveFans($openid,$time)
    {
        //将时间戳转换为秒数。$time
        $time = strtotime(date('Y-m-d H:i',$time));

        $redis = self::get_redis();
        $key = CacheKeyConstant::WECHAT_ACTIVE_FANS;
        //存储到redis中。
        $redis->HMSET($key,["{$openid}"=>$time]);

//        //要进行判断的数量
//        $checkCount = 100;
//        //检测当前的字段中是否超过了{$checkCount}个,如果超过了{$checkCount}个 那么就进行存库操作.
//        $count = $redis->HLEN($key);
//        if($count >= $checkCount){
//            //获取该appid下的所有openid
//            $allOpenidInfo = $redis->HGETALL($key);
//            $active_time_sql_str = "case openid";
//            $openids_sql_str = '';
//            foreach ($allOpenidInfo as $openid=>$activetime) {
//                $openids_sql_str .= "'{$openid}'".',';
//                $active_time_sql_str .= " WHEN '{$openid}' THEN {$activetime} ";
//            }
//            $active_time = $active_time_sql_str.= "END";
//            $openids = rtrim($openids_sql_str,',');
//            $updateResult = Db::execute("update wechat_user_info set active_time={$active_time} where openid in ({$openids})");
//            $redis->delete($key);
//        }
    }

}
