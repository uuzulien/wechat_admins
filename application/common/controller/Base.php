<?php
namespace app\common\controller;
use app\common\constant\CacheKeyConstant;
use app\common\tool\Curl;
use think\Controller;
use think\cache\driver\Redis;

include_once EXTEND_PATH.'wechat/wechat.class.php';

/**
 * 基础类
 * @author muyufeng
 */
class Base   extends Controller{

    protected static $cache_instance = [];
    private static $EMPOWER_TOKEN = 'ca0199f97068427c362d111eda6652a4';

    public  static function get_redis($redis_name="cache_use_redis")
    {
        if (!isset(self::$cache_instance[$redis_name])) {
            $Redis_config = Config('redis.');
            $resis_server  = new Redis();
            //pconnect
            $resis_server->connect($Redis_config['host'],$Redis_config['port'],$Redis_config['timeout']);
            $resis_server->auth($Redis_config['password']);
            $resis_server->select($Redis_config['select']);
            self::$cache_instance[$redis_name] = $resis_server;
        }
        return self::$cache_instance[$redis_name];
    }

    /**
     * 生成api 唯一 token
     */
    public  static function make_api_token($hash_name,$data=[],$is_empower=false)
    {
        $redis   = self::get_redis();
        $key=random_string();
        if($is_empower){
            $key = self::$EMPOWER_TOKEN;
        }
        if(!$data){
            $data=false;
        }
        $data=serialize($data);

        //尝试10 次
        $_time=0;
        $token_ok=false;
        while ($_time<10) {
            //存在就跳过
            //hGet  value|false
            if(!$redis->hGet($hash_name,$key))
            {
                //设置 不存在创建,存在就覆盖
                $redis->hSet($hash_name, $key, $data);
                $token_ok=true;
                $_time=100;
            }
            else{
                $key=random_string();
                $_time++;
            }
        }
        if(!$token_ok)
        {
            return false   ;
        }
        return $key;
        //向名称为h的hash中添加元素key1—>hello
        //hGet
        //$redis->hGet('h', 'key1');
        //返回名称为h的hash中key1对应的value（hello）
        //hLen
        //$redis->hLen('h');
        //返回名称为h的hash中元素个数
        //hDel
        //$redis->hDel('h', 'key1');
        //删除名称为h的hash中键为key1的域
        //hKeys
        //$redis->hKeys('h');
        //返回名称为key的hash中所有键
        //hVals
        //$redis->hVals('h')
        //返回名称为h的hash中所有键对应的value
        //hGetAll
        //$redis->hGetAll('h');
        //返回名称为h的hash中所有的键（field）及其对应的value
        //hExists
        //$redis->hExists('h', 'a');
        //名称为h的hash中是否存在键名字为a的域
    }

    /**
     * 检查 api 唯一token
     */
    public  static function get_api_token($token, $hash_name,$is_iempower=false)
    {
        $redis   = self::get_redis();
        if($is_iempower){
            $token = self::$EMPOWER_TOKEN;
        }
        $val = $redis->hGet($hash_name,$token);
        if($val){
            $val = unserialize($val);
        }else{
            $val=false;
        }
        return $val;
    }

    /**
     * 检查 根据 token 更新 cache
     */
    public  static function update_api_token($token, $hash_name,$data=[],$is_pwoer=false)
    {
        $redis   = self::get_redis();
        $data=serialize($data);
        if($is_pwoer){
            $token = self::$EMPOWER_TOKEN;
        }
        $redis->hSet($hash_name, $token, $data);
        return $token;
    }

    /**
     * 获取令牌
     * @return array|mixed
     */
    public static function getComponentToken()
    {
        $redis   = self::get_redis();
        $key = CacheKeyConstant::COMPONENT_TOKEN;
        //redis不为空就取redis的。
        if(!empty($redis->get($key))){
            $component_access_token = $redis->get($key);
        }else{
            //请求的url
            $request_url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
            $config = config('wechat.wechat_open');
            //ticket获取
            $ticket = self::get_api_token('','empower',true);
            $data = [
                'component_appid'=>$config['appid'],
                'component_appsecret'=>$config['appsecret'],
                'component_verify_ticket' =>$ticket,
            ];
            $result = Curl::send($request_url,json_encode($data),'post');
            //将结果转换为数组
            $arrayResult = json_decode($result,true);
            //取其中的access_token
            $component_access_token =$arrayResult['component_access_token'] ?? '';
            $redis->set($key, $component_access_token);
            //设置失效时间
            $redis->expire($key,6600);
        }
        return $component_access_token;
    }

    /**
     * 获取预授权码
     */
    public static function get_pre_auth_code()
    {
        //获取到令牌
        $COMPONENT_ACCESS_TOKEN = self::getComponentToken();
        $config = config('wechat.wechat_open');
        $data = [
            'component_appid' => $config['appid'],
        ];
        $request_url = "https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token={$COMPONENT_ACCESS_TOKEN}";
        $result = Curl::send($request_url, json_encode($data), 'post');
        $arrayResult = json_decode($result,true);
        $pre_auth_code = $arrayResult['pre_auth_code'] ?? '';
        return $pre_auth_code;
    }

    /**
     * 获取微信群发消息时候的不同类型需要的不同请求数据.
     * @param string $msgtype  群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
     * @param string $tag_id 要群发的标签id
     * @param string $media_id 媒体id
     */
    public static function getSeedAllPostData($msgtype,$tag_id,$content)
    {
        if (empty($tag_id)) {
            //如果标签id为空的话 代表全部发送.
            $is_to_all = true;
        }else{
            $is_to_all = false;
        }
        switch ($msgtype) {
            case 'mpnews':
                $data = [
                    'filter' => [
                        'is_to_all' => $is_to_all,
                        'tag_id' => $tag_id,
                    ],
                    'mpnews' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'mpnews',
                    'send_ignore_reprint' => 0
                ];
                break;
            case 'text':
                $data = [
                    'filter' => [
                        'is_to_all' => $is_to_all,
                        'tag_id' => $tag_id,
                    ],
                    'text' => [
                        "content" => urlencode($content)
                    ],
                    'msgtype' => 'text',
                ];
                break;
            case 'voice':
                $data = [
                    'filter' => [
                        'is_to_all' => $is_to_all,
                        'tag_id' => $tag_id,
                    ],
                    'voice' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'voice',
                ];
                break;
            case 'music':
                break;
            case 'image':
                $data = [
                    'filter' => [
                        'is_to_all' => $is_to_all,
                        'tag_id' => $tag_id,
                    ],
                    'image' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'image',
                ];
                break;
            case 'mpvideo':
                $data = [
                    'filter' => [
                        'is_to_all' => $is_to_all,
                        'tag_id' => $tag_id,
                    ],
                    'mpvideo' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'mpvideo',
                ];
                break;
            case 'wxcard':
                break;
        }

        return $data;
    }


    /**
     * 获取微信群发消息(根据openid)时候的不同类型需要的不同请求数据.
     * @param string $msgtype  群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
     * @param string $tag_id 要群发的标签id
     * @param string $media_id 媒体id
     */
    public static function getSeedPostData($msgtype,$touser,$content)
    {
        switch ($msgtype) {
            case 'mpnews':
                $data = [
                    'touser' => $touser,
                    'mpnews' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'mpnews',
                    'send_ignore_reprint' => 0,
                    'clientmsgid' => time()
                ];
                break;
            case 'text':
                $data = [
                    'touser' => $touser,
                    'text' => [
                        "content" => $content
                    ],
                    'msgtype' => 'text',
                    'clientmsgid' => time()
                ];
                break;
            case 'voice':
                $data = [
                    'touser' => $touser,
                    'voice' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'voice',
                    'clientmsgid' => time()
                ];
                break;
            case 'music':
                break;
            case 'image':
                $data = [
                    'touser' => $touser,
                    'image' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'image',
                    'clientmsgid' => time()
                ];
                break;
            case 'mpvideo':
                $data = [
                    'touser' => $touser,
                    'mpvideo' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'mpvideo',
                    'clientmsgid' => time()
                ];
                break;
            case 'wxcard':
                break;
        }

        return $data;
    }


    public static function getSentServicePostData($msgtype,$touser,$content)
    {
        switch ($msgtype) {
            case 'news':
                $data = [
                    'touser' => $touser,
                    'msgtype' => 'news',
                    'news' => [
                        "articles" => [
                            $content
                        ]
                    ],
                ];
                break;
            case 'text':
                $data = [
                    'touser' => $touser,
                    'text' => [
                        "content" => $content
                    ],
                    'msgtype' => 'text',
                ];
                break;
            case 'voice':
                $data = [
                    'touser' => $touser,
                    'voice' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'voice',
                ];
                break;
            case 'music':
                break;
            case 'image':
                $data = [
                    'touser' => $touser,
                    'image' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'image',
                ];
                break;
            case 'mpvideo':
                break;
            case 'wxcard':
                break;
            case 'mpnews':
                $data = [
                    'touser' => $touser,
                    'mpnews' => [
                        "media_id" => $content
                    ],
                    'msgtype' => 'mpnews',
                ];
                break;
        }

        return $data;
    }

}

