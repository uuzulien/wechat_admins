<?php
namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\UserMaterialInfoModel;
use think\Db;
use think\Request;

/**
 * 公众号管理的首页
 * Class WechatHomeController
 * @package app\admin\controller
 */
class WechatHomeController extends AdminBase
{
    /**
     * 统计
     */
    public function index(Request $request)
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        //获取用户的增减数据
        $usersummary = WechatFunBase::getusersummary($appid,$authorizer_refresh_token);
        if(empty($usersummary['list'])){
            return error_result('','获取用户增减数据失败',$usersummary);
        }
        //获取累计用户数据
        $usercumulate = WechatFunBase::getusercumulate($appid,$authorizer_refresh_token);
        if(empty($usercumulate['list'])){
            return error_result('','获取累计用户数据失败',$usercumulate);
        }

        $res = [];
        $res["usersummary"] = $usersummary['list'];
        $res["usercumulate"] = $usercumulate['list'];
        return success_result('成功',$res);
    }

    /**
     * 根据id、表名获取详细信息接口
     */

    public function getInfoForIdAndTable(Request $request)
    {
        $id = $request->post('id/d',0,'trim');
        $type = $request->post('type/d',0,'trim');
        if(empty($id) || empty($type)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        //3：group_sent_info 4：service_message_list 5：template_message_info
        switch ($type) {
            case 3:
                $tablename = 'group_sent_info';
                $field = 'group_sent_type,group_sent_tag_type,wechat_public_num_id,sent_time,reply_type,reply_content,appid';
                break;
            case 4:
                $tablename = 'service_message_list';
                $field = 'task_type,msg_type,msg_content,sent_time,sent_group_type,site_type';
                break;
            case 5:
                $tablename = 'template_message_info';
                $field = 'template_name,template_id,sent_time,group_sent_interval_time,group_tag_type,wechat_public_num_id,template_var_content,redirect_url';
                break;
        }
        if(empty($tablename) || empty($field)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $findResult = Db::name($tablename)->field($field)->find($id);
        //普通群发。
        if($type == 3){
            if($findResult['reply_type'] == 'image'){
                $link_url = UserMaterialInfoModel::where(['media_id'=>$findResult['reply_content']])->value('link_url');
                $findResult['material_url'] = $link_url;
            }
            if($findResult['reply_type'] == 'mpnews'){
                $newsInfo = UserMaterialInfoModel::field('news_thumb_url,news_title')->where(['media_id'=>$findResult['reply_content']])->select();
                $findResult['news_data'] = $newsInfo;
            }
            if($findResult['reply_type'] == 'voice'){
                $voiceinfo = UserMaterialInfoModel::field('voice_name,create_time')->where(['media_id'=>$findResult['reply_content']])->find();
                $findResult['voice_name'] = $voiceinfo['voice_name'] ?? '';
                $findResult['create_time'] = $voiceinfo['create_time'] ?? '';
            }
        }
        if($type == 4){
            if($findResult['msg_type'] == 'news'){
                $findResult['msg_content'] = unserialize($findResult['msg_content']);
            }
        }
        if($type == 5){
            $findResult['template_var_content'] = unserialize($findResult['template_var_content']);
        }
        if(empty($findResult)){
            return error_result(ErrorCode::DATA_NOT);
        }
        return success_result('成功',$findResult);
    }
}