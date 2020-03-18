<?php
namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\AdminModel;
use app\common\model\ServiceMessageListModel;
use app\common\model\TemplateMessageInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\Request;

/**
 * 群发模板消息控制器
 * Class WechatTemplateMsgController
 * @package app\admin\controller
 */
class WechatTemplateMsgController extends AdminBase
{
    /**
     * 列表.
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
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
        $handle_name = request()->post('handle_name');
        $sent_status = request()->post('sent_status');

        /****************搜公众号****************/
        if(!empty($gzh_name)){
            $where[] = ['nick_name','like',"{$gzh_name}%"];
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
            'list_rows' => ($limit <= 0 || $limit > 20) ? 20 : $limit,
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
            $lists = TemplateMessageInfoModel::where($wechat_where)->whereTime('sent_time', 'between', [$starttime, $endtime])->append(['wechat_nick_name','handle_user_name'])->order($order)->paginate($paginate);
        }else{
            $lists = TemplateMessageInfoModel::where($wechat_where)->append(['wechat_nick_name','handle_user_name'])->order($order)->paginate($paginate);
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
        $sent_time = $request->post('sent_time');//发送时间
        $group_sent_interval_time = $request->post('group_sent_interval_time');//发送的间隔时间/秒
        $group_tag_type = $request->post('group_tag_type'); //群发标签类型 1:公众号官方标签
        $wechat_public_num_id = $request->post('wechat_public_num_id'); //微信公众号那边的标签id
        $template_id = $request->post('template_id'); //模板id
        $template_var_content = $request->post('template_var_content'); //模板的变量内容 这里前端传json格式
        $touser = $request->post('touser'); //测试的用户openid 这里前端传了id 需要后端解析
        $redirect_url = $request->post('redirect_url');//跳转的url
        $template_name = $request->post('template_name'); //模板的名字


        if(empty($wechat_public_num_id) || empty($template_var_content) || empty($group_tag_type) || empty($sent_time)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);

        }
        //将$msg_content换为数组
        $template_var_content = json_decode($template_var_content,true);

        //{"first_value":"签到成功","keyword1_value":"12221212112","keyword2_value":"测试名字","keyword3_value":"一月一号","remark_value":"rema回rk参数"}
        //这边需要在处理一下前端所上传过来的格式
        $newContnet = [];
        foreach ($template_var_content as $k => $v) {
            $in = explode('_',$k);
            if($in[1] == 'value'){

                if(isset($newContnet[$in[0]]['color'])){
                    $newContnet[$in[0]]['value'] = $v;
                }else{
                    $newContnet[$in[0]] = [
                        'value' => $v
                    ];
                }

            }else if($in[1] == 'color'){
                if(isset($newContnet[$in[0]]['value'])){
                    $newContnet[$in[0]]['color'] = $v;
				}else{
                    $newContnet[$in[0]] = [
                        'color' => $v
                    ];
                }
            }
        }


        //往后延迟五分钟
//        $sent_time = strtotime($sent_time);//发送时间 把前端传过来的格式解析成时间戳
        $sent_time = strtotime('+2minute',strtotime($sent_time));//发送时间 把前端传过来的格式解析成时间戳
        //如果不为空就是去测试.
//
//        if(!empty($touser)){
//
//            /**************将前端传过来的id转换为openid*************/
//            $touser = WechatUserInfoModel::field('openid,appid')->find($touser);
//            if(empty($touser)){
//                return error_result('','选择的id不存在.');
//            }
//            //如果用户的appid和当前操作的appid不同 那么报错
//            if($appid != $touser['appid']){
//                return error_result('','输错id了吧~.');
//            }
//            $touser = $touser['openid'];
//            /***************结束*********************/
//
//
//            $seedResult = WechatFunBase::send_template_msg($appid,$authorizer_refresh_token,$touser,$template_id,$redirect_url,$newContnet);
//            if(!empty($seedResult['errcode'])){
//                return error_result('','发送失败',$seedResult);
//            }
//            if($seedResult){
//                return success_result('发送成功',$seedResult);
//            }
//        }

        if(!empty($touser)){
            $testSentResult = $this->testSent($touser,$appid,$authorizer_refresh_token,$template_id,$redirect_url,$newContnet);
            return $testSentResult;
        }

        $temmesgtable = new TemplateMessageInfoModel();
        $temmesgtable->appid = $appid;
        $temmesgtable->handle_user_id = $handle_user_id;
        $temmesgtable->template_id = $template_id;
        $temmesgtable->sent_time = $sent_time;
        $temmesgtable->group_tag_type = $group_tag_type;
        $temmesgtable->create_time = time();
        $temmesgtable->wechat_public_num_id = $wechat_public_num_id;
        $temmesgtable->redirect_url = $redirect_url;
        $temmesgtable->template_var_content = serialize($newContnet);
        $temmesgtable->group_sent_interval_time = $group_sent_interval_time;
        $temmesgtable->template_name = $template_name;
        $result = $temmesgtable->save();
        if($result) {
            return success_result('保存成功.');
        }

    }

        /**
         * 测试发送
         */
        private function testSent($touser,$appid,$authorizer_refresh_token,$template_id,$redirect_url,$newContnet)
    {
        /**************将前端传过来的id转换为openid*************/
        $touser = WechatUserInfoModel::field('nickname,openid,appid')->find($touser);
        if(empty($touser)){
            return error_result('','选择的id不存在.');
        }
        //如果用户的appid和当前操作的appid不同 那么报错
        if($appid != $touser['appid']){
            return error_result('','输错id了吧~.');
        }
        /***************结束*********************/
        foreach ($newContnet as $conentk => $conentv) {
            if(strpos($conentv['value'],'wechatusername') !== false){
                $newContnet[$conentk]['value'] = $touser['nickname'];
            }
        }

        $touser_id = $touser['openid'];
        $seedResult = WechatFunBase::send_template_msg($appid,$authorizer_refresh_token,$touser_id,$template_id,$redirect_url,$newContnet);
        if(!empty($seedResult['errcode'])){
            return error_result('','发送失败',$seedResult);
        }
        if($seedResult){
            return success_result('发送成功',$seedResult);
        }
    }

    /**
     * 获取微信模板标签
     */

    public function getWechatTemplateList(Request $request)
    {
        header('Access-Control-Allow-Origin:http://localhost:8080');
        header('Access-Control-Allow-Methods:GET,POST,PUT,DELETE,PATCH,OPTIONS');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Headers:Content-Type, X-ELEME-USERID, X-Eleme-RequestID, X-Shard,X-Shard, X-Eleme-RequestID,X-Adminid,X-Token');
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $listsinfo = WechatFunBase::get_all_private_template($appid,$authorizer_refresh_token);
//        $test = '{"template_list": [{"template_id": "iPk5sOIt5X_flOVKn5GrTFpncEYTojx6ddbt8WYoV5s","title": "领取奖金提醒","primary_industry": "IT科技","deputy_industry": "互联网|电子商务","content": "{{first.DATA}}↵签到序列码：{{keyword1.DATA}}↵签到人：{{keyword2.DATA}}↵签到时间：{{keyword3.DATA}}↵{{remark.DATA}}","example": "您已提交领奖申请\n\n领奖金额：xxxx元\n领奖时间：2013-10-10 12:22:22\n银行信息：xx银行(尾号xxxx)\n到账时间：预计xxxxxxx\n\n预计将于xxxx到达您的银行卡"}]}';
//        $lists = json_decode($test,true)['template_list'];
        if(!empty($listsinfo['errcode'])){
            return error_result('','获取失败',$listsinfo);
        }
        $lists = $listsinfo['template_list'];
        if(!empty($lists)){
            foreach ($lists as $k => $v) {
                preg_match_all('#{{([\s\S]*?).DATA}}#',$v['content'],$newArr);
                $lists[$k]['variable'] = $newArr[1];
            }
        }

        return success_result('获取成功',$lists);

    }


    /**
     * 删除接口
     */

    public function delete(Request $request)
    {
        $id = $request->post('id');

        $result = TemplateMessageInfoModel::destroy($id);
        if($result){
            return success_result('删除成功');
        }
    }

    /**
     * 获取当天发送的信息, 包含 发送成功的、失败的、未发送的数量
     * @author wh
     * @date 2020年01月10日14:10:09
     * @param Request $request
     * @return \think\Response
     */
    public function getTodaySentInfo(Request $request)
    {
        if(!$request->isPost()){
            return error_result(ErrorCode::NOT_NETWORK);
        }
        $templateMessageModel = new TemplateMessageInfoModel();
        $todayNoSentCount = $templateMessageModel->where(['sent_status'=>0])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentSuccessCount = $templateMessageModel->where(['sent_status'=>2])
            ->whereTime('sent_time','d')
            ->count();
        $todaySentErrorCount = $templateMessageModel->where(['sent_status'=>3])
            ->whereTime('sent_time','d')
            ->count();
        $res = [];
        $res['todayNoSentCount'] = $todayNoSentCount;
        $res['todaySentSuccessCount'] = $todaySentSuccessCount;
        $res['todaySentErrorCount'] = $todaySentErrorCount;
        return success_result('查询成功',$res);
    }
}