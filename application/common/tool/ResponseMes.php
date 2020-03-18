<?php
namespace app\common\tool;

use think\Controller;

class ResponseMes extends Controller
{
    public static function responseText($postObj,$content)
    {
        //获取配置
        $config = config('wechat.wechat_open');
        $template ="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            </xml>";
        $fromUser = $postObj ->ToUserName;
        $toUser   = $postObj -> FromUserName;
        $time     = time();
        $msgType  = 'text';
        $res =sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['token'];
        $appId = $config['appid'];
        $pc = new \WXBizMsgCrypt ($token, $encodingAesKey, $appId );
        $encryptMsg = '';
        $errCode =$pc->encryptMsg($res,$_GET ['timestamp'], $_GET ['nonce'], $encryptMsg);
        if($errCode ==0){
            $res = $encryptMsg;
        }
        return $res;
    }
    public static function responseImg($postObj,$content)
    {
        //获取配置
        $config = config('wechat.wechat_open');
        $template ="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Image>
                <MediaId><![CDATA[%s]]></MediaId>
            </Image>
            </xml>";
        $fromUser = $postObj ->ToUserName;
        $toUser   = $postObj -> FromUserName;
        $time     = time();
        $msgType  = 'image';
        $res =sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['token'];
        $appId = $config['appid'];
        $pc = new \WXBizMsgCrypt ($token, $encodingAesKey, $appId );
        $encryptMsg = '';
        $errCode =$pc->encryptMsg($res,$_GET ['timestamp'], $_GET ['nonce'], $encryptMsg);
        if($errCode ==0){
            $res = $encryptMsg;
        }
        return $res;
    }
    public static function responseVioce($postObj,$content)
    {
        //获取配置
        $config = config('wechat.wechat_open');
        $template ="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Voice>
                <MediaId><![CDATA[%s]]></MediaId>
            </Voice>
            </xml>";
        $fromUser = $postObj ->ToUserName;
        $toUser   = $postObj -> FromUserName;
        $time     = time();
        $msgType  = 'voice';
        $res =sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['token'];
        $appId = $config['appid'];
        $pc = new \WXBizMsgCrypt ($token, $encodingAesKey, $appId );
        $encryptMsg = '';
        $errCode =$pc->encryptMsg($res,$_GET ['timestamp'], $_GET ['nonce'], $encryptMsg);
        if($errCode ==0){
            $res = $encryptMsg;
        }
        return $res;
    }
    public static function responseNews($postObj,$arr)
    {
        //获取配置
        $config = config('wechat.wechat_open');
        $toUser     = $postObj -> FromUserName;
        $fromUser   = $postObj -> ToUserName;
        $template  ="<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <ArticleCount>".count($arr)."</ArticleCount>
            <Articles>";
        foreach($arr as $k=>$v){
            $template.="<item>
            <Title><![CDATA[".$v['title']."]]></Title>
            <Description><![CDATA[".$v['des']."]]></Description>
            <PicUrl><![CDATA[".$v['img']."]]></PicUrl>
            <Url><![CDATA[".$v['link']."]]></Url>
            </item>";
        }
        $template.="</Articles>
            </xml>";
        $time     = time();
        $msgType  = 'news';
        $res =sprintf($template,$toUser,$fromUser,$time,$msgType);
        $encodingAesKey = $config['encodingAesKey'];
        $token = $config['token'];
        $appId = $config['appid'];
        $pc = new \WXBizMsgCrypt ($token, $encodingAesKey, $appId );
        $encryptMsg = '';
        $errCode =$pc->encryptMsg($res,$_GET ['timestamp'], $_GET ['nonce'], $encryptMsg);
        if($errCode ==0){
            $res = $encryptMsg;
        }
        return $res;
    }
}