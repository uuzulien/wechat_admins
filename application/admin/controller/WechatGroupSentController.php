<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\AdminModel;
use app\common\model\GroupSentInfo;
use app\common\model\ServiceMessageListModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\Request;

/**
 * 微信群发消息控制器
 * Class WechatGroupSentController
 * @package app\admin\controller
 */
class WechatGroupSentController extends AdminBase
{

    public function index(Request $request)
    {
        $where = [];
        $wechat_where=[];
        $order = 'create_time DESC';
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        $user_info  = session('user');

        $starttime = request()->post('start_time');
        $endtime = request()->post('end_time');

        $gzh_name = request()->post('gzh_name');
        $handle_name = request()->post('handle_name');
        $bendi_sent_status = request()->post('bendi_sent_status');


        /****************搜公众号****************/
        if(!empty($gzh_name)){
            $where[] = ['nick_name','like',"{$gzh_name}%"];
        }
        /****************结束****************/
        /****************搜发送状态****************/
        if(!empty($bendi_sent_status) || $bendi_sent_status === '0'){
            $wechat_where[] = ['bendi_sent_status','=',$bendi_sent_status];
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
        $wechat_where[] = ['appid','in',$appid];
        if(!empty($starttime) && !empty($endtime)){
            $lists = GroupSentInfo::where($wechat_where)->whereTime('sent_time', 'between', [$starttime, $endtime])
                ->order($order)
                ->append(['wechat_nick_name','handle_user_name'])
                ->paginate($paginate);
        }else{
            $lists = GroupSentInfo::where($wechat_where)
                ->order($order)
                ->append(['wechat_nick_name','handle_user_name'])
                ->paginate($paginate);
        }
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = $lists->items();
        return success_result('成功',$res);
    }


    /**
     * fason
     * @param Request $request
     * @return \think\Response
     * @throws \app\common\exception\JsonException
     */
    public function groupSent(Request $request)
    {
        //获取本地存储的appid
        $auth_appid = session('wechat')['auth_appid'];
        //获取本地存储的appid的刷新令牌
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        //群发方式 1:主动群发
        $group_sent_type = $request->post('group_sent_type');
        //群发标签类型 1:公众号官方标签
        $group_sent_tag_type = $request->post('group_sent_tag_type');
        //微信公众号那边的标签id
        $wechat_public_num_id = $request->post('wechat_public_num_id');
        $sent_time = $request->post('sent_time',0);
        if(empty($sent_time)){
            $sent_time = strtotime('+2minute');
        }else{
            $sent_time = strtotime('+2minute',strtotime($sent_time));//发送时间 把前端传过来的格式解析成时间戳
        }
        //群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
        $reply_type = $request->post('reply_type');
        //这里是回复内容. 可以是文字 也可以是媒体id
        $reply_content = $request->post('reply_content');
        //双引号变为单引号(传递给微信的时候需要)
        $reply_content = str_replace('"',"'",$reply_content);
        $handle_user_id = session('user')['id'];
        //..如果is_test == 1 代表是测试. 那么就对接群发客服的接口
        $is_test = $request->post('is_test');
        //用户的id
        $touser = $request->post('touser');


        //...如果is_test == 1 代表是测试. 那么就对接wechat的 seed(根据用户openid发送) 接口 此处测试不进行入库操作.
        if($is_test == 1){
            $sentResult = $this->testSent($touser,$auth_appid,$authorizer_refresh_token,$reply_type,$reply_content);
            return $sentResult;
        }

        //2.将发送数据进行本地入库
        $groupsentinfotable = new GroupSentInfo();
        $groupsentinfotable->group_sent_type = $group_sent_type;
        $groupsentinfotable->group_sent_tag_type = $group_sent_tag_type;
        $groupsentinfotable->wechat_public_num_id = $wechat_public_num_id;
        $groupsentinfotable->sent_time = $sent_time;
        $groupsentinfotable->reply_type = $reply_type;
        $groupsentinfotable->reply_content = $reply_content;
        $groupsentinfotable->appid = $auth_appid;
        $groupsentinfotable->create_time = time();
        $groupsentinfotable->handle_user_id = $handle_user_id;
        $result = $groupsentinfotable->save();
        if($result) {
            return success_result('发送成功');
        }
    }


    public function edit(Request $request)
    {
        $id = $request->post('id');
        //获取本地存储的appid
        $auth_appid = session('wechat')['auth_appid'];
        //获取本地存储的appid的刷新令牌
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $group_sent_type = $request->post('group_sent_type');
        $group_sent_tag_type = $request->post('group_sent_tag_type');
        //标签id
        $wechat_public_num_id = $request->post('wechat_public_num_id');
        //群发的消息类型，图文消息为mpnews，文本消息为text，语音为voice，音乐为music，图片为image，视频为video，卡券为wxcard
        $reply_type = $request->post('reply_type');
        $reply_content = $request->post('reply_content');
        //双引号变为单引号(传递给微信的时候需要)
        $reply_content = str_replace('"',"'",$reply_content);
        $handle_user_id = session('user')['id'];
        //..如果is_test == 1 代表是测试. 那么就对接wechat的 seed(根据用户openid发送) 接口
        $is_test = $request->post('is_test');
        //用户的id
        $touser = $request->post('touser');
        $sent_time = $request->post('sent_time');
        /*********检测***********/
        if(empty($id) || empty($group_sent_type) || empty($group_sent_tag_type) || empty($reply_type)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        /*********结束************/


        //修改时间
        if(empty($sent_time)){
            $sent_time = strtotime('+2minute');
        }else{
            $sent_time = strtotime('+2minute',strtotime($sent_time));//发送时间 把前端传过来的格式解析成时间戳
        }

        //2.查找出要修改的数据
        $groupsentinfotable = GroupSentInfo::field('id,appid,bendi_sent_status')->find($id);
        if(empty($groupsentinfotable)){
            return error_result(ErrorCode::DATA_NOT);
        }
        if($groupsentinfotable['appid'] != $auth_appid){
            return error_result('','公众号不匹配');
        }
        if($groupsentinfotable['bendi_sent_status'] != 0){
            return error_result('500','此条信息已经发送,禁止修改');
        }

        //...如果is_test == 1 代表是测试. 那么就对接wechat的 seed(根据用户openid发送) 接口 此处测试不进行入库操作.
        if($is_test == 1){
            $sentResult = $this->testSent($touser,$auth_appid,$authorizer_refresh_token,$reply_type,$reply_content);
            return $sentResult;
        }

        $groupsentinfotable->group_sent_type = $group_sent_type;
        $groupsentinfotable->group_sent_tag_type = $group_sent_tag_type;
        $groupsentinfotable->wechat_public_num_id = $wechat_public_num_id;
        $groupsentinfotable->sent_time = $sent_time;
        $groupsentinfotable->reply_type = $reply_type;
        $groupsentinfotable->handle_user_id = $handle_user_id;
        $groupsentinfotable->reply_content = $reply_content;
        $result = $groupsentinfotable->save();
        if($result) {
            return success_result('修改成功');
        }
    }

    private function testSent($touser,$auth_appid,$authorizer_refresh_token,$reply_type,$reply_content)
    {
        $touser = WechatUserInfoModel::field('openid,appid')->find($touser);
        if(empty($touser)){
            return error_result('','选择的id不存在.');
        }
        //如果用户的appid和当前操作的appid不同 那么报错
        if($auth_appid != $touser['appid']){
            return error_result('','输错id了吧~.');
        }
        $touser = $touser['openid'];
        //这个数组 调取群发客服接口的时候不需要。
        $touserArr =[];
        array_push($touserArr,$touser,'');
        $seedResult = WechatFunBase::sent_service_msg($auth_appid,$authorizer_refresh_token,$reply_type,$touser,$reply_content);
        if($seedResult['errcode'] != 0){
            return error_result('','发送失败',$seedResult);
        }
        return success_result('发送成功',$seedResult);
    }

    /**
     * 删除接口
     */

    public function delete(Request $request)
    {
        $id = $request->post('id');
        $findResult = GroupSentInfo::field('appid')->find($id);
        //获取本地存储的appid
        $auth_appid = session('wechat')['auth_appid'];
        if($findResult['appid'] != $auth_appid){
            return error_result('','该信息不是当前公众号的');
        }
        $result = $findResult->delete();
        if($result){
            return success_result('删除成功');
        }
    }

    /**
     * 获取当天发送的信息, 包含 发送成功的、失败的、未发送的数量
     * @author wh
     * @date 2020年01月13日09:59:35
     * @param Request $request
     * @return \think\Response
     */
    public function getTodaySentInfo(Request $request)
    {
        if(!$request->isPost()){
            return error_result(ErrorCode::NOT_NETWORK);
        }
        $groupSentMessageModel = new GroupSentInfo();
        $todayNoSentCount = $groupSentMessageModel->where(['bendi_sent_status'=>0])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentSuccessCount = $groupSentMessageModel->where(['bendi_sent_status'=>2])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentErrorCount = $groupSentMessageModel->where(['bendi_sent_status'=>3])
            ->whereTime('sent_time','d')
            ->count();
        $res = [];
        $res['todayNoSentCount'] = $todayNoSentCount;
        $res['todaySentSuccessCount'] = $todaySentSuccessCount;
        $res['todaySentErrorCount'] = $todaySentErrorCount;
        return success_result('查询成功',$res);
    }

}