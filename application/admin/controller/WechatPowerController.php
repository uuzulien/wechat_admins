<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\exception\JsonException;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatTagListModel;
use app\common\model\WechatUserInfoModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\facade\Log;
use think\Request;

class WechatPowerController extends AdminBase
{
    public function index()
    {
        return true;
    }

    public function empower(){

    }

    /**
     * 获取授权二维码的url地址. //wx的
     */
    public function getAuthQrcodeUrl()
    {
        $config = config('wechat.wechat_open');
        //appid
        $component_appid = $config['appid'];
        //获取授权码
        $pre_auth_code = self::get_pre_auth_code();
        //回调的url
        $user_group = session('user')['user_group'];
        $redirect_uri = config('app.app_host')."/wechat_notify/?user_group={$user_group}";
        //要授权的帐号类型：1 则商户点击链接后，手机端仅展示公众号、2 表示仅展示小程序，3 表示公众号和小程序都展示。如果为未指定，则默认小程序和公众号都展示。第三方平台开发者可以使用本字段来控制授权的帐号类型。
        $auth_type = 1;
        $request_url = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid={$component_appid}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_uri}&auth_type={$auth_type}";
        return success_result('成功','',$request_url);
    }

    /**
     * 获取已授权的公众号列表
     */

    public function getAuthGzhList()
    {
        $where = [];
        $order = 'id DESC';
        $name = request()->post('name', '','trim');
        if (!empty($name)) {
            $where[] = ['nick_name', 'like', $name . '%'];
        }
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => $limit <= 0 ? 20 : $limit,
        ];
        //获取权限区分
        $user = session('user');
        if($user['creater_id'] == 0){
            //管理员权限
        }else if($user['creater_id'] == 1){
            //组长权限
            $where[] = ['user_group','=',$user['user_group']];
        }else{
            //组员权限
            $where[] = ['user_group','=',$user['user_group']];
            $where[] = ['use_user_id','=',$user['id']];
            //获取已经同步过用户的
            $where[] = ['is_get_user', '=', 1];
        }
        $lists = WechatEmpowerInfoModel::field('id,nick_name,head_img,service_type_info,verify_type_info,user_group,qrcode_url,auth_appid')->where($where)->order($order)->append(['active_fans_count'])->paginate($paginate)->toArray();
        $res = [];
        $res["total"] = $lists['total'];
        $res["list"] = $lists['data'];

        return success_result('成功',$res);
    }


    /**
     * 获取当前用户归属组的公众号列表
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNowUserWechatPublicNumList()
    {
        $nowusergroupid = session('user')['user_group'];
        $lists = WechatEmpowerInfoModel::field('auth_appid,nick_name')->where(['user_group'=>$nowusergroupid])->select();
        return success_result('获取成功',$lists);
    }

    /**
     * 获取微信公众号的标签列表
     */
    public function getWechatTagList(Request $request)
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        //是否保存到数据库. 如果is_save的值为1的话就存储到数据库
        $is_save = $request->post('is_save');
        $lists = WechatFunBase::get_wechat_tag_list($appid,$authorizer_refresh_token);
        if(!empty($lists['errcode'])){
            return error_result('','获取失败',$lists);
        }
        $taglish = $lists['tags'];
        if(!empty($is_save) && $is_save == 1){
            $saveData = [];
            foreach ($taglish as $k => $v) {
                array_push($saveData,[
                    'tag_name' => $v['name'],
                    'tag_id' => $v['id'],
                    'handle_user_id' => session('user')['id'] ?? 0,
                    'appid' => $appid,
                    'create_time' => time()
                ]);
            }
            //添加之前先把他之前有的标签组删除
            $delresult = WechatTagListModel::where(['appid'=>$appid])->delete();
            $wechatTagList = new WechatTagListModel();
            $wechatTagList->saveAll($saveData);
        }
        return success_result('获取成功',$taglish);
    }

    /**
     * 创建微信标签
     */
    public function createWechatTag(Request $request)
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $tagName = $request->post('tagname');
        $lists = WechatFunBase::create_wechat_tag($appid,$authorizer_refresh_token,$tagName);
        if(!empty($lists['errcode'])){
            return error_result('','创建失败',$lists);
        }
        $tag = $lists['tag'];
        return success_result('创建成功',$tag);
    }
    /**
     * 抓取微信公众号的当前粉丝.
     * @param Request $request
     */
    public function getWechatUserInfo(Request $request)
    {
        //公众号的名字
        $gzhnickname = $request->post('nickname');
        //查找相同的公众号名字 并且 未爬过粉丝的.
        $gzhinfo = WechatEmpowerInfoModel::where(['nick_name'=>$gzhnickname])->where(['is_get_user'=>0])->find();
        if(empty($gzhinfo)){
            return error_result('','当前公众号已经爬取粉丝或公众号名字输入错误.');
        }

        $list = $this->getuseropenidlist($gzhinfo['auth_appid'],$gzhinfo['authorizer_refresh_token']);
        file_put_contents('shoudongokGzhAppidInfo.log',print_r($gzhinfo['auth_appid'].'->>>>>>>>>'.$gzhnickname.'->>>>>>>>>'.date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
        $gzhinfo->is_get_user = 1;
        $gzhinfo->save();
        //讲N个openid合并成一个数组.
        $openids = array_reduce($list,'array_merge',[]);
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
            //获取到一百个openid,发起请求,获取用户详细信息 验证并加入数据库
            $result = $this -> get_user_info($gzhinfo['auth_appid'],$gzhinfo['authorizer_refresh_token'],$openids_each);
        }
        return success_result('ok');
    }
    protected function getuseropenidlist($suth_appid,$authorizer_refresh_token,$next_openid = '')
    {
        //这个变量用于存放获取到的数据
        $resultArr = [];
        //用于统计获取了多少数据了
        $sum = 0;
        //先获取第一次
        $list = WechatFunBase::get_wechat_user_list($suth_appid,$authorizer_refresh_token,$next_openid);
        //存储获取到的下一个next_openid
        $next_openid = $list['next_openid'];
        $sum += $list['count'];
        //openid加到数组里面，
        array_push($resultArr,$list['data']['openid']);
        //总数减去已经获取的数量 如果不等于0的话 说明还有 继续获取就完事了
        if(($list['total'] - $sum) != 0){
            //先减去第一次获取的 1w个
            $nowTotal = $list['total'] - 10000;
            //公众号那边每次最多能获取一万个 所以 总数/1w 向上取整, 就是要循环的次数
            for ($i = 0; $i<ceil($nowTotal / 10000); $i++) {
                $list = WechatFunBase::get_wechat_user_list($suth_appid,$authorizer_refresh_token,$next_openid);
                $next_openid = $list['next_openid'];
                $sum += $list['count'];
                array_push($resultArr,$list['data']['openid']);
            }
        }
        return $resultArr;
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
                        file_put_contents('wenhaoceshi999shoudong.log',print_r($user_info['openid'].date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
                    }
                }catch (\Exception $e){
                    file_put_contents('wenhaoceshishoudong.log',print_r($e->getMessage().date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
                }

            }
        }else{
            //如果获取失败 记录失败的 openids和appid
            file_put_contents('getwechatuserinfoErrortoOpenDatashoudong.log',print_r($auth_appid.'->>>>>>>>>'.json_encode($openids_each).'->>>'.date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
            return false;
        }
        sleep(1);
        return true;
    }



    /**|
     * 切换公众号的组
     * @param Request $request
     */
    public function switchWechatGroup(Request $request)
    {
        //用户组id
        $user_group = $request->post('user_group');
        //公众号id1
        $wechat_id = $request->post('wechat_id');

        if(empty($user_group) || empty($wechat_id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }

        $wechatinfo = WechatEmpowerInfoModel::field('id')->find($wechat_id);
        $wechatinfo->user_group = $user_group;
        $wechatinfo->use_user_id = 0;
        $result = $wechatinfo->save();
        if($result){
            return success_result('切换成功');
        }

    }
}
