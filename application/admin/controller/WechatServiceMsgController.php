<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\AdminModel;
use app\common\model\ServiceMessageListModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\Request;
use app\common\controller\Base;


/**
 * 微信客服群发消息控制器.
 * Class WechatServiceMsgController
 * @package app\admin\controller
 */
class WechatServiceMsgController extends AdminBase
{


    /**
     * 微信客服群发消息的列表方法.
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
//        $redis = Base::get_redis();
//        print_r($redis);die;
        $where = [];
        $wechat_where=[];
        $order = 'create_time DESC';
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        $appid = session('wechat')['auth_appid'];
        $user_info  = session('user');

        $starttime = request()->post('start_time');
        $endtime = request()->post('end_time');
        $gzh_name = request()->post('gzh_name');
        $platform_id = request()->post('platform_id');
        $handle_name = request()->post('handle_name');
        $sent_status = request()->post('sent_status');

        /****************搜公众号****************/
        if(!empty($gzh_name)){
            $where[] = ['nick_name','like',"{$gzh_name}%"];
        }
        /****************结束****************/
        /****************搜站点****************/
        if(!empty($platform_id)){
            $wechat_where[] = ['site_type','=',$platform_id];
        }
        /****************结束****************/
        /****************搜发送状态****************/
        if(!empty($sent_status) || $sent_status === '0'){
            $wechat_where[] = ['sent_status','=',$sent_status];
        }
        /****************结束****************/
        /****************搜操作人姓名****************/
        if(!empty($handle_name)){
            $ids = AdminModel::field('id')->where('name','like',"$handle_name%")->select()->toArray();
            $IdsArr = [];
            foreach($ids as $v){
                array_push($IdsArr,$v['id']);
            }
            $idsStr = join(',',$IdsArr);
            $wechat_where[] = ['handle_user_id','in',$idsStr];
        }
        /****************结束****************/

        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => $limit <= 0 ? 20 : $limit,
        ];
        //根据权限获取对应公众号
        if($user_info['creater_id'] == 0){
            //管理员权限
        }else if($user_info['creater_id'] == 1){
            //组长权限
            $where[] = ['user_group','=',$user_info['user_group']];
        }else{
            //组员权限
            $where[] = ['user_group','=',$user_info['user_group']];
            $where[] = ['use_user_id','=',$user_info['id']];
        }
        //获取公众号id
        $wechat_model = new WechatEmpowerInfoModel();
        $wechat_info = $wechat_model->where($where)->field('auth_appid')->select()->toArray();
        $appid = '';
        if($wechat_info){
            foreach ($wechat_info AS $wechet_key => $wechat_val){
                $appid .= $wechat_val['auth_appid'] . ',';
            }
            $appid = substr($appid,0,strlen($appid)-1);
        }
        //当前公众号的
        $wechat_where[] = ['appid','in',$appid];
        if(!empty($starttime) && !empty($endtime)){
            $lists = ServiceMessageListModel::where($wechat_where)->whereTime('sent_time', 'between', [$starttime, $endtime])->order($order)->append(['wechat_nick_name','handle_user_name','site_type_name','task_type_name'])->paginate($paginate);
        }else{
            $lists = ServiceMessageListModel::where($wechat_where)->order($order)->append(['wechat_nick_name','handle_user_name','site_type_name','task_type_name'])->paginate($paginate);
        }
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = $lists->items();
        return success_result('成功',$res);
    }


    /**
     * 发送信息保存的方法.
     * @param Request $request
     */
    public function sent(Request $request)
    {
        $appid = session('wechat')['auth_appid']; //公众号的appid
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $handle_user_id  = session('user')['id']; //操作人的id
        $task_type = $request->post('task_type'); //任务类型
        $msg_type = $request->post('msg_type'); //消息类型
        $msg_content = $request->post('msg_content'); //消息内容 这里前端传json格式
        $sent_time = $request->post('sent_time');//发送时间
        $sent_group_type = $request->post('sent_group_type');//要发送的群体类型
        $touser = $request->post('touser'); //测试的用户openid 这里前端传了id. 需要后端解析
        $site_type = $request->post('site_type'); //站点类型 1-掌读，2-掌中云，3-网易，4-火烧云，5-阳光，6-滕文，7-掌文，8-追书云，9-文鼎，10-阅文

        if(empty($task_type) || empty($msg_type) || empty($msg_content)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        //当非测试的时候发送时间为空阻止发送
        if(empty($touser) && empty($sent_time)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        //将$msg_content换为数组 并且序列化存入
        $msg_content = json_decode($msg_content,true);

        //这里可以改为全序列化存入, 但目前正在使用 不方便改.
        if($msg_type == 'text'){
            $msg_content = $msg_content['text'];
        }else if($msg_type == 'news'){
            $msg_content = serialize($msg_content);
        }
        //往后延迟2分钟
        $sent_time = strtotime('+2minute',strtotime($sent_time));//发送时间 把前端传过来的格式解析成时间戳
        //如果不为空就是去测试.
        if(!empty($touser)){
            $sentResult = $this->testSent($appid,$authorizer_refresh_token,$msg_type,$touser,$msg_content);
            return $sentResult;
        }

        $wechatservicemsgtable = new ServiceMessageListModel();
        $wechatservicemsgtable->appid = $appid;
        $wechatservicemsgtable->handle_user_id = $handle_user_id;
        $wechatservicemsgtable->task_type = $task_type;
        $wechatservicemsgtable->msg_type = $msg_type;
        $wechatservicemsgtable->msg_content = $msg_content;
        $wechatservicemsgtable->sent_time = $sent_time;
        $wechatservicemsgtable->sent_group_type = $sent_group_type;
        $wechatservicemsgtable->create_time = time();
        $wechatservicemsgtable->site_type = $site_type;
        $result = $wechatservicemsgtable->save();
        if($result) {
            return success_result('保存成功.');
        }

    }

    /**
     * @author wh
     * @date 2019年12月24日17:55:42
     * @param $auth_appid 要发送的appid
     * @param $authorizer_refresh_token appid的刷新令牌
     * @param $msg_type 消息类型
     * @param $touserid 要接受的openid
     * @param $msg_content 要发送的内容
     * @return \think\Response
     * @throws \app\common\exception\JsonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function testSent($auth_appid,$authorizer_refresh_token,$msg_type,$touserid,$msg_content)
    {
        /**************将前端传过来的id转换为openid*************/
        $touser = WechatUserInfoModel::field('openid,appid,nickname')->find($touserid);
        if(empty($touser)){
            return error_result('','选择的id不存在.');
        }
        //如果用户的appid和当前操作的appid不同 那么报错
        if($auth_appid != $touser['appid']){
            return error_result('','输错id了吧~.');
        }
        $touserid = $touser['openid'];
        /***************结束*********************/
        if($msg_type == 'text'){
            $msg_content = str_replace("wechatusername",$touser['nickname'],$msg_content);
            $seedResult = WechatFunBase::sent_service_msg($auth_appid,$authorizer_refresh_token,$msg_type,$touserid,$msg_content);
        }else if($msg_type == 'news'){
            $seedResult = WechatFunBase::sent_service_msg($auth_appid,$authorizer_refresh_token,$msg_type,$touserid,unserialize($msg_content));
        }
        if(!empty($seedResult['errcode'])){
            return error_result('','发送失败',$seedResult);
        }
        return success_result('发送成功',$seedResult);
    }



    public function edit(Request $request)
    {
        $id = $request->post('id');
        $handle_user_id  = session('user')['id']; //操作人的id
        $task_type = $request->post('task_type'); //任务类型
        $msg_type = $request->post('msg_type'); //消息类型
        $msg_content = $request->post('msg_content'); //消息内容
        $sent_time = $request->post('sent_time');//发送时间
        $sent_group_type = $request->post('sent_group_type');//要发送的群体类型
        $touser = $request->post('touser'); //测试的用户openid
        $site_type = $request->post('site_type'); //站点类型

        if(empty($task_type) || empty($msg_type) || empty($msg_content) || empty($sent_time) || empty($id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);

        }
        $appid = session('wechat')['auth_appid']; //公众号的appid
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $sent_time = strtotime($sent_time);//发送时间 把前端传过来的格式解析成时间戳

        //当非测试的时候发送时间为空阻止发送
        if(empty($touser) && empty($sent_time)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        //将$msg_content换为数组
        $msg_content = json_decode($msg_content,true);
        if($msg_type == 'text'){
            $msg_content = $msg_content['text'];
        }else if($msg_type == 'news'){
            $msg_content = serialize($msg_content);
        }
        //如果不为空就是去测试.
        if(!empty($touser)){
            $sentResult = $this->testSent($appid,$authorizer_refresh_token,$msg_type,$touser,$msg_content);
            return $sentResult;
        }


        $wechatservicemsgtable = ServiceMessageListModel::find($id);
        if(empty($wechatservicemsgtable)){
            return error_result(ErrorCode::DATA_NOT);

        }
        if($wechatservicemsgtable['sent_status'] != 0){
            return error_result('500','此条信息已经发送,禁止修改');
        }
        $wechatservicemsgtable->handle_user_id = $handle_user_id;
        $wechatservicemsgtable->task_type = $task_type;
        $wechatservicemsgtable->msg_type = $msg_type;
        $wechatservicemsgtable->msg_content = $msg_content;
        $wechatservicemsgtable->sent_time = $sent_time;
        $wechatservicemsgtable->sent_group_type = $sent_group_type;
        $wechatservicemsgtable->site_type = $site_type;
        $result = $wechatservicemsgtable->save();
        if($result) {
            return success_result('保存成功.');
        }

    }

    /**
     * 删除接口
     */

    public function delete(Request $request)
    {
        $id = $request->post('id');

        $result = ServiceMessageListModel::destroy($id);
        if($result){
            return success_result('删除成功');
        }
    }

    /**
     * 获取当天发送的信息, 包含 发送成功的、失败的、未发送的数量
     * @author wh
     * @date 2020年01月09日11:28:52
     * @updatedate 2020年01月11日09:57:06 author:wh
     * @param Request $request
     * @return \think\Response
     */
    public function getTodaySentInfo(Request $request)
    {
        if(!$request->isPost()){
            return error_result(ErrorCode::NOT_NETWORK);
        }
        $serviceMessageModel = new ServiceMessageListModel();
        /********************今日未发送的公众号数量******************/
        $todayNoSentCountOnText = $serviceMessageModel->where(['sent_status'=>0])
            ->where(['msg_type'=>'text'])
            ->whereTime('sent_time','d')
            ->count();
        $todayNoSentCountOnNews = $serviceMessageModel->where(['sent_status'=>0])
            ->where(['msg_type'=>'news'])
            ->whereTime('sent_time','d')
            ->count();
        /********************结束******************/
        /********************今日发送成功的公众号数******************/
        $todaySentSuccessCountOnText = $serviceMessageModel->where(['sent_status'=>2])
            ->where(['msg_type'=>'text'])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentSuccessCountOnNews = $serviceMessageModel->where(['sent_status'=>2])
            ->where(['msg_type'=>'news'])
            ->whereTime('sent_time','d')
            ->count();
        /********************结束******************/

        /********************今日发送失败的公众号数量******************/
        $todaySentErrorCountOnText = $serviceMessageModel->where(['sent_status'=>3])
            ->where(['msg_type'=>'text'])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentErrorCountOnNews = $serviceMessageModel->where(['sent_status'=>3])
            ->where(['msg_type'=>'news'])
            ->whereTime('sent_time','d')
            ->count();
        /********************结束******************/
        $res = [];
        $res['todayNoSentCount'] = $todayNoSentCountOnText+$todayNoSentCountOnNews."个,其中,文本包含{$todayNoSentCountOnText}个,图文包含{$todayNoSentCountOnNews}个";
        $res['todaySentSuccessCount'] = $todaySentSuccessCountOnText+$todaySentSuccessCountOnNews."个,其中,文本包含{$todaySentSuccessCountOnText}个,图文包含{$todaySentSuccessCountOnNews}个";
        $res['todaySentErrorCount'] = $todaySentErrorCountOnText+$todaySentErrorCountOnNews."个,其中,文本包含{$todaySentErrorCountOnText}个,图文包含{$todaySentErrorCountOnNews}个";
        return success_result('查询成功',$res);
    }

}