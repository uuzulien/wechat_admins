<?php
namespace app\admin\controller;

use app\common\constant\CacheKeyConstant;
use app\common\controller\AdminBase;
use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\ServiceMessageListModel;
use app\common\model\TemplateMessageInfoModel;
use app\common\model\UserMaterialInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatPlatform;
use app\common\tool\UploadFile;
use mysql_xdevapi\Result;
use think\App;
use think\facade\Env;
use think\Request;

class CommonController extends AdminBase
{
    public function __construct(App $app = null)
    {
        parent::__construct($app);
    }


    /**
     * 获取公众号的固定功能. 固定接口
     */
    /*public function getFieldGzhFunction()
    {
        $lists = self::get_gzh_field_function_list();
        return success_result('成功',$lists);
    }*/


    /**
     * 单文件上传.  字段为file、isUploadWechat
     * @param Request $request
     * @return \think\Response
     */
    public function uploadFile(Request $request)
    {
        //这里是前端传过来的, 如果传1 那么就代表需要上传到微信公众号那边. 传0的话 仅仅存储本地.
        $isUploadWechat = $request->post('isUploadWechat');
        $filetype = $request->post('filetype');
        $fileext  = $request->post('fileext');
        $appid = session('wechat')['auth_appid'];
        $uploadfile = $request->file('file');
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        if(!is_numeric($isUploadWechat) || empty($filetype) || empty($fileext) || empty($uploadfile)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $result = UploadFile::upload_file($uploadfile,$filetype,$fileext,'500000',$isUploadWechat,$appid,$authorizer_refresh_token);
        if($result){
            return success_result('上传成功',$result);
        }
    }
    /**
     * 显示微信的图片.
     */
    public function showWechatImg(Request $request)
    {
        $url = $request->param('url');
        if(empty($url)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        header('content_type:image/jpeg');
        echo file_get_contents($url);
    }
    /*
     * 获取字典
     */
    public function getDictionary(Request $request)
    {

        $dictionaryArr = config('dictionary.');
        return success_result('获取成功',$dictionaryArr);
    }

    //检测a标签内 的href是否有http
    public function checkATag(Request $request)
    {
        $str = $request->post('str');
        //站点
        $site_type = $request->post('site_type',0,'int');
        //任务类型
        $task_type = $request->post('task_type',1,'int');
        $reg1="/<a .*?>.*?<\/a>/";
        $aarray = [];
        preg_match_all($reg1,$str,$aarray);
        foreach ($aarray[0] as $k => $v) {
            //第一个href的位置
            $start = strpos($v,'href');
            //查出href后 第一个引号的位置,
            $yin1First = strpos($v,'"',$start);
            //查出引号后第二个引号的位置.
            $yin2First = strpos($v,'"',$yin1First+1);
            $checkdata = substr($v,$yin1First+1,$yin2First - $yin1First - 1);
            //如果去除两边空格还为空的话 那么就代表里面没内容 返回就可以了
            if(empty(trim($checkdata))){
                return error_result('','有空的html 禁止提交');
            }
            //-------------检测对应站点url------------
            $wechat_platform = new WechatPlatform();
            //获取当前使用公众号所属平台
            $wechat = session('wechat');
            //站点链接
            if($task_type == 1){
                $platform = WechatEmpowerInfoModel::where(['id'=>$wechat['id']])->field('platform_id')->find();
                $platform_id = $platform['platform_id'];
                //外部链接
            } elseif ($task_type == 2) {
                $platform_id = $site_type;
            }
            //存在所属平台信息，进行匹配
            if(!empty($platform_id)){
                //获取对应平台的限制信息
                $url_limit = $wechat_platform->field('url')->where(['id'=>$platform_id,'status'=>1])->find();
                if($url_limit && $url_limit['url']){
                    //进行url限制匹配
                    $checkUrlArr = explode(',',$url_limit['url']);
                    //当前文本判定结果
                    $is_content_ok = false;
                    foreach ($checkUrlArr as $value) {
                        $check_url = strpos($checkdata,$value);
                        if($check_url !== false){
                            $is_content_ok = true;
                            break;
                        }
                    }
                    if($is_content_ok === false){
                        //没有检查到对应的字符，返回错误
                        return error_result('','检测到非对应平台的url包含，请重新设定链接！');
                    }
                }
            }
            //-------------检测对应站点url--end----------
        }
        return success_result('成功');
    }

    /**
     * 清楚appid的调用频率。 一月十次
     * @param Request $request
     */
    public function appidClearQuota(Request $request)
    {
        //获取要更改的appid
        $appid = $request->post('appid');
        $appidinfo = WechatEmpowerInfoModel::field('authorizer_refresh_token')->where(['auth_appid'=>$appid])->find();
        if(empty($appidinfo)){
            return error_result('','appid不存在');
        }
        $autorizer_refresh_token = $appidinfo['authorizer_refresh_token'];
        $result = WechatFunBase::clear_quota($appid,$autorizer_refresh_token);
        if($result === true){
            return success_result('清除成功');
        }else{
            return error_result('','清除失败.');
        }
    }

    /**
     * 根据msg_id 获取发送结果
     * @param Request $request
     * @return \think\Response
     */
    public function searchGroupSentStatus(Request $request)
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $msg_id = $request->post('msg_id/d',0);
        if(empty($msg_id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $result = WechatFunBase::search_group_sent_status($appid,$authorizer_refresh_token,$msg_id);
        if(!empty($result['errcode'])){
            return error_result('','获取失败.',$result);
        }else{
            return success_result('获取成功',$result);
        }
    }


    /**
     * 该功能是获取存储在redis中的 群发客服/模板消息的发送结果.
     * @author wh
     * @createdate 2020年01月09日10:52:44
     * @updatedate 2020年01月10日14:14:47 wh
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMsgSentResult(Request $request)
    {
        $redis   = self::get_redis();
        //获取要查看的类型  群发客服(qfkf)/群发模板(qfmb)
        $type = $request->post('type','','string');
        if(empty($type)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $result = [];
        if($type == 'qfkf'){
            //客服消息的key
            $serviceMsgKey = CacheKeyConstant::SERVICE_MSG.'*';
            //获取客服消息所有存储的信息
            $serviceContent = $redis->keys($serviceMsgKey);
            foreach ($serviceContent as $serviceval) {
                $serviceredisval = $redis->get($serviceval);
                $servicerediskeyArr = explode('_',$serviceval);
                $serviceredisvalArr = explode('_',$serviceredisval);
                $gzh_appid = ServiceMessageListModel::field('appid')->find($servicerediskeyArr[2])['appid'];
                $gzh_nick_name = WechatEmpowerInfoModel::where(['auth_appid' => $gzh_appid])->value('nick_name');
                array_push($result,[
                    'gzh_nick_name' =>$gzh_nick_name,
                    'sent_num' =>$serviceredisvalArr[0],
                    'last_time' =>date('Y-m-d H:i:s',$serviceredisvalArr[1]),
                ]);
            }
        }elseif ($type == 'qfmb') {
            //客服消息的key
            $templateMsgKey = CacheKeyConstant::TEMPLATE_MSG.'*';
            //获取客服消息所有存储的信息
            $templateContent = $redis->keys($templateMsgKey);
            foreach ($templateContent as $templateval) {
                $templateredisval = $redis->get($templateval);
                $templaterediskeyArr = explode('_',$templateval);
                $templateredisvalArr = explode('_',$templateredisval);
                $gzh_appid = TemplateMessageInfoModel::field('appid')->find($templaterediskeyArr[2])['appid'];
                $gzh_nick_name = WechatEmpowerInfoModel::where(['auth_appid' => $gzh_appid])->value('nick_name');
                array_push($result,[
                    'gzh_nick_name' =>$gzh_nick_name,
                    'sent_num' =>$templateredisvalArr[0],
                    'last_time' =>date('Y-m-d H:i:s',$templateredisvalArr[1]),
                ]);
            }
        }


        return success_result('获取成功',$result);
    }

}
