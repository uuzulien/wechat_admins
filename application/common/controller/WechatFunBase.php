<?php
namespace app\common\controller;


use app\common\constant\CacheKeyConstant;
use app\common\exception\JsonException;
use app\common\tool\Curl;
use app\common\tool\CurlFile;
use app\common\tool\Wlog;
use think\facade\Log;

/**
 * 微信主动接口公用控制器
 * Class WechatFunBase
 * @package app\common\controller
 */
class WechatFunBase extends Base
{
    /**
     * 使用授权码获取授权信息
     * @param $auth_code 授权码
     * @return 详细文档请见:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/api/authorization_info.html#%E8%AF%B7%E6%B1%82%E5%9C%B0%E5%9D%80
     *
     */
    public static function api_query_auth($auth_code)
    {
        //获取到令牌
        $COMPONENT_ACCESS_TOKEN = self::getComponentToken();
        $config = config('wechat.wechat_open');
        $request_url = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token={$COMPONENT_ACCESS_TOKEN}";
        $data = [
            'component_appid' => $config['appid'],
            'authorization_code'=>$auth_code,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        return $arrayData;
    }


    /**
     * 获取授权方的账号基本信息
     * @param $authorizer_appid 授权方的appid
     * @return 详细文档请见:https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/api/api_get_authorizer_info.html#%E8%AF%B7%E6%B1%82%E5%9C%B0%E5%9D%80
     */
    public static function api_get_authorizer_info($authorizer_appid)
    {
        //获取到令牌
        $COMPONENT_ACCESS_TOKEN = self::getComponentToken();
        $config = config('wechat.wechat_open');
        $request_url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token={$COMPONENT_ACCESS_TOKEN}";
        $data = [
            'component_appid' => $config['appid'],
            'authorizer_appid'=>$authorizer_appid,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        return $arrayData;
    }


    /**
     * 获取呆调用公众号接口 令牌
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @return 详细文档请见：https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/api/api_authorizer_token.html#%E8%AF%B7%E6%B1%82%E5%9C%B0%E5%9D%80
     */

    public static function api_authorizer_token($authorizer_appid,$authorizer_refresh_token)
    {
        $redis   = self::get_redis();
        //获取要存储到的key
        $key = CacheKeyConstant::AUTHORIZER_ACCESS_TOKEN.$authorizer_appid;
        if(!empty($redis->get($key))){
            $authorizer_access_token = $redis->get($key);
        }else {
            //获取到令牌
            $COMPONENT_ACCESS_TOKEN = self::getComponentToken();
            $config = config('wechat.wechat_open');
            $request_url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token={$COMPONENT_ACCESS_TOKEN}";
            $data = [
                'component_appid' => $config['appid'],
                'authorizer_appid'=>$authorizer_appid,
                'authorizer_refresh_token'=>$authorizer_refresh_token
            ];
            $result = Curl::send($request_url, json_encode($data), 'post');
            $arrayData = json_decode($result,true);
            if(empty($arrayData['authorizer_access_token'])){
                Wlog::write('wechatFunLog',"api_authorizer_token_error接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}");
                return '';
            }
            $authorizer_access_token = $arrayData['authorizer_access_token'];
            $redis->set($key, $authorizer_access_token);
            //设置失效时间
            $redis->expire($key,7000);

        }
        return $authorizer_access_token;
    }

    /**
     * 新增其他类型永久素材
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $fileurl 文件的url 例如：/var/www/upload/xx.jpg
     * @param $type 文件的类型. image video 等. 具体见微信官方类型.(以微信官方的媒体文件类型为准)
     */
    public static function wechat_media_upload($authorizer_appid,$authorizer_refresh_token,$fileurl,$type)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$access_token}&type={$type}";
        $result = CurlFile::curlFile($url,$fileurl);
        if(!empty($result['errcode'])){
            Wlog::write('wechatFunLog',"wechat_media_upload接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$result['errmsg']}");
        }
        return $result;
    }

    /**
     * 删除存在微信那边的永久素材
     * @param $media_id 媒体id
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     */
    public static function del_material($media_id,$authorizer_appid,$authorizer_refresh_token)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/material/del_material?access_token={$access_token}";
        $data = [
            'media_id'=>$media_id
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if($arrayData['errcode'] != 0){
            return false;
        }
        return true;
    }
    /**
     * 获取用户增减数据（getusersummary）
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $timespan 时间跨度. 这里如果填写1 就是今天减去一天. 也就是昨天. 最大为7(官方规定)
     * @return 微看信官方文档:https://developers.weixin.qq.com/doc/offiaccount/Analytics/User_Analysis_Data_Interface.html
     */
    public static function getusersummary($authorizer_appid,$authorizer_refresh_token,$timespan = 1)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/datacube/getusersummary?access_token={$access_token}";
        $begin_date = date('Y-m-d', strtotime("-{$timespan} day"));
        //这里默认写死为最大值(前一天)
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $data = [
            'begin_date'=>$begin_date,
            'end_date' =>$end_date
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"getusersummary接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }

    /**
     * 获取累计用户数据（getusercumulate）
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $timespan 时间跨度. 这里如果填写1 就是今天减去一天. 也就是昨天. 最大为7(官方规定)
     * @return 微信官方文档:https://developers.weixin.qq.com/doc/offiaccount/Analytics/User_Analysis_Data_Interface.html
     */
    public static function getusercumulate($authorizer_appid,$authorizer_refresh_token,$timespan = 1)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/datacube/getusercumulate?access_token={$access_token}";
        $begin_date = date('Y-m-d', strtotime("-{$timespan} day"));
        //这里默认写死为最大值(前一天)
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $data = [
            'begin_date'=>$begin_date,
            'end_date' =>$end_date
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"getusercumulate接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }


    /**
     * 根据标签进行群发【订阅号与服务号认证后均可用】
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param string $msgtype  群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
     * @param string $tag_id 要群发的标签id
     * @param string $content 这里可以填写媒体id或者内容.
     * @throws JsonException
     * @return 微信官方文档 :https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Batch_Sends_and_Originality_Checks.html#2
     */
    public static function WechatPublicNumSendAll($authorizer_appid,$authorizer_refresh_token,$msgtype = '',$tag_id = '',$content = '')
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token={$access_token}";
        $data = self::getSeedAllPostData($msgtype,$tag_id,$content);
        $result = Curl::send($request_url, urldecode(json_encode($data)), 'post');
        $arrayData = json_decode($result,true);
        if($arrayData['errcode'] != 0){
            Wlog::write('wechatFunLog',"WechatPublicNumSendAll接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }


    /**
     * 根据oppid列表进行群发【订阅号不可用, 服务号认证后可用】
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param string $msgtype  群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
     * @param array $touser 填写图文消息的接收者，一串OpenID列表，OpenID最少2个，最多10000个
     * @param $content 这里可以填写媒体id或者内容
     * @return 微信官方文档 :https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Batch_Sends_and_Originality_Checks.html#2
     */
    public static function WechatPublicNumSeed($authorizer_appid,$authorizer_refresh_token,$msgtype = '',$touser = [],$content)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token={$access_token}";
        $data = self::getSeedPostData($msgtype,$touser,$content);
        $result = Curl::send($request_url, json_encode($data,JSON_UNESCAPED_UNICODE), 'post');
        Log::error('请求WechatPublicNumSeed接口获取的对象数据：'.$result);
        $arrayData = json_decode($result,true);
        if($arrayData['errcode'] != 0){
            Wlog::write('wechatFunLog',"WechatPublicNumSeed接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }


    /**
     * 获取素材列表
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $type 素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     * @param $offset 从全部素材的该偏移位置开始返回，0表示从第一个素材 返回
     * @param $count 返回素材的数量，取值在1到20之间
     * @return 微信官方文档:https://developers.weixin.qq.com/doc/offiaccount/Asset_Management/Get_materials_list.html
     */
    public static function batchget_material($authorizer_appid,$authorizer_refresh_token,$type,$offset = 0,$count = 20)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token={$access_token}";
        $data = [
            'type' => $type,
            'offset' => $offset,
            'count' =>$count
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"batchget_material接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }

        return $arrayData;
    }


    /**
     * 微信公众号创建自定义菜单接口.
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $menu_data 这里是要设置菜单的数据，。
     */
    public static function create_wechat_public_num_menu($authorizer_appid,$authorizer_refresh_token,$menu_data)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
        $data = $menu_data;
        $result = Curl::send($request_url, json_encode($data,JSON_UNESCAPED_UNICODE), 'post');
        $arrayData = json_decode($result,true);
        if($arrayData['errcode'] != 0){
            Wlog::write('wechatFunLog',"create_wechat_public_num_menu接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
            return false;
        }
        return true;
    }


    /**
     * 获取微信公众号已经创建的标签.
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @return mixed
     * @throws JsonException
     */
    public static function get_wechat_tag_list($authorizer_appid,$authorizer_refresh_token)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token={$access_token}";
        $result = Curl::send($request_url, '', 'get');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"get_wechat_tag_list接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }

    /**
     * 创建标签.
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @return mixed
     * @throws JsonException
     */
    public static function create_wechat_tag($authorizer_appid,$authorizer_refresh_token,$tagName)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/tags/create?access_token={$access_token}";
        $data = [
            'tag' => [
                'name' => $tagName
            ],
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"create_wechat_tag接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }


    /**
     * 根据openid获取微信用户的详细信息
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $openId 微信用户的openid
     * @return mixed
     */
    public static function get_wechat_user_info($authorizer_appid,$authorizer_refresh_token,$openId)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);

        $request_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openId}&lang=zh_CN";
        $result = json_decode(Curl::send($request_url,'','get'),true);
        if(!empty($result['errcode'])){
            Wlog::write('wechatFunLog',"get_wechat_user_info接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$result['errmsg']}>>>>>>>{$result['errcode']}");
        }
        return $result;
    }
    /**
     * 批量获取用户的详细信息
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $user_list 微信用户的openid这里填写数组
     * @return mixed
     */
    public static function batch_get_wechat_user_info($authorizer_appid,$authorizer_refresh_token,$user_list = [])
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token={$access_token}";
        $data = [
            'user_list' => $user_list,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"batch_get_wechat_user_info接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");

        }
        return $arrayData;
    }



    /**
     * 获取微信公众号关注的用户列表
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $next_openid 第一个拉取的OPENID，不填默认从头开始拉取
     * @return mixed
     */
    public static function get_wechat_user_list($authorizer_appid,$authorizer_refresh_token,$next_openid = '')
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid={$next_openid}";
        $result = Curl::send($request_url, '', 'get');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"get_wechat_user_list接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }

    /**
     * 群发客服消息接口.
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $msgtype 发送的类型。
     * @param $touser 接受人的openid
     * @param $content 发送的内容。
     * @throws JsonException
     * @return 看微信官方文档：https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Service_Center_messages.html
     */

    public static function sent_service_msg($authorizer_appid,$authorizer_refresh_token,$msgtype,$touser,$content)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
        $data = self::getSentServicePostData($msgtype,$touser,$content);
        $result = Curl::send($request_url, json_encode($data,JSON_UNESCAPED_UNICODE), 'post');
        Log::error('请求sent_service_msg接口获取的对象数据：'.$result);
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            //如果错误代码不是45015（当前用户24小时未和公众号交互。）在记录。
            if($arrayData['errcode'] != 45015){
                Wlog::write('wechatFunLog',"sent_service_msg接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
            }
            return $arrayData;
        }
        return true;

    }


    /**
     * 获取所有模板列表
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     */
    public static function get_all_private_template($authorizer_appid,$authorizer_refresh_token)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$access_token}";
        $result = Curl::send($request_url, '', 'get');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"get_all_private_template接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }

    /**
     * 发送模板消息
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     * @param $touser 用户的openid
     * @param $template_id 模板id
     * @param $url 跳转链接
     * @param $data 数据. 这个是数组.
     * @return 见官方文档：https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html#5
     */
    public static function send_template_msg($authorizer_appid,$authorizer_refresh_token,$touser,$template_id,$url,$data)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $data = [
            'touser' => $touser,
            'template_id' => $template_id,
            'url' => $url,
            'data' => $data,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"send_template_msg接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }


    /**
     * 获取微信公众号的菜单.
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     */
    public static function get_current_selfmenu_info($authorizer_appid,$authorizer_refresh_token)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token={$access_token}";
        $result = Curl::send($request_url, '', 'get');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"get_current_selfmenu_info接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }

    /**
     * 公众号调用或第三方平台帮公众号调用对公众号的所有api调用（包括第三方帮其调用）次数进行清零：.
     * @param $authorizer_appid 授权方的appid
     * @param $authorizer_refresh_token 刷新临牌
     */
    public static function clear_quota($authorizer_appid,$authorizer_refresh_token)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/clear_quota?access_token={$access_token}";
        $data = [
            'appid' => $authorizer_appid,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if($arrayData['errcode'] != 0){
            Wlog::write('wechatFunLog',"clear_quota接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
            return false;
        }
        return true;
    }

    /**
     * 根据msg_id获取发送群发结果
     * @param $authorizer_appid
     * @param $authorizer_refresh_token
     * @param $msg_id
     * @return mixed
     */
    public static function search_group_sent_status($authorizer_appid,$authorizer_refresh_token,$msg_id)
    {
        $access_token = self::api_authorizer_token($authorizer_appid,$authorizer_refresh_token);
        $request_url = "https://api.weixin.qq.com/cgi-bin/message/mass/get?access_token={$access_token}";
        $data = [
            'msg_id' => $msg_id,
        ];
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayData = json_decode($result,true);
        if(!empty($arrayData['errcode'])){
            Wlog::write('wechatFunLog',"search_group_sent_status接口的错误：相关的appid:{$authorizer_appid}>>>>>>>{$arrayData['errmsg']}>>>>>>>{$arrayData['errcode']}");
        }
        return $arrayData;
    }
}