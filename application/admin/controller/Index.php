<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\exception\JsonException;
use app\common\model\AdminModel;
use app\common\model\AdminRolesModel;
use app\common\model\AdminRolesPowerModel;
use app\common\model\GroupInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatPlatform;
use think\Request;

class Index extends AdminBase
{
    public function index()
    {
        return $this->fetch('vue_system/dist/index');
    }

    public function login(){
        $username = $this->request->post('username','','trim');
        $password = $this->request->post('password','','trim');
        if(!$username || !$password){
            return error_result('','缺少信息');
        }
        //初始化
        $admin_model = new AdminModel();
        $role_model = new AdminRolesModel();
        $power_model = new AdminRolesPowerModel();
        //查询管理员用户
        $field = 'id,creater_id,username,password,name,power_group,user_group,last_use_wechat_id';
        $user_info = $admin_model::field($field)->where(['username'=>$username,'status'=>1])->find();
        if(!$user_info || md5($password) != $user_info->password){
            return error_result('','用户信息不匹配');
        }
        //转换数组
        $user_info = $user_info->getData();
        //获取权限组
        $role_info = $role_model::field('name,power_ids')->where(['id'=>$user_info['power_group'],'status'=>1])->find()->toArray();
        //获取权限
        if($role_info){
            if($role_info['power_ids'] == 0){
                //管理员所有权限
                $power_info = $power_model::field('id,name,group_id,controller,action,url,api_url')->select()->toArray();
            }else{
                //除管理员外人员权限
                $power_info = $power_model::field('id,name,group_id,controller,action,url,api_url')->whereIn('id',$role_info['power_ids'])->select()->toArray();
            }
            if(!$power_info){
                return error_result('','权限异常');
            }
        }else{
            return error_result('','权限异常');
        }
        //获取最后一次使用的公众号信息
        $wechat_model = new WechatEmpowerInfoModel();
        $wechat_info = $wechat_model->where(['id'=>$user_info['last_use_wechat_id'],'status'=>1])->field('id,auth_appid,authorizer_refresh_token')->find();
        if($wechat_info) {
            $wechat_info = $wechat_info->toArray();
            session('wechat',$wechat_info);
        }
        //存入session
        session('user',$user_info);
        session('role',$role_info);
        session('power',$power_info);
        return success_result();
    }

    /**
     * 获取头部menu
     * @return \think\Response
     */
    public function get_title_menu(){
        $power_list = session('power');
        $result = [];
        if($power_list) {
            foreach ($power_list AS $power_key => $power_val){
                if($power_val['group_id'] == 0){
                    array_push($result,$power_val);
                }
            }
        }else{
            return error_result('','权限异常');
        }
        return success_result('OK',$result);
    }

    /**
     * 设置当前menu所属模块
     * @return \think\Response
     */
    public function set_now_menu_group(){
        if(is_ajax_post()){
            $id = $this->request->post('id',0,'int');
            session('group_menu_id',$id);
            return success_result();
        }else{
            return error_result('','传递异常');
        }
    }

    /**
     * 获取当前menu列
     * @return \think\Response
     */
    public function get_menu(){
        $power_list = session('power');
        $result = [];
        $group_menu_id = session('group_menu_id');
        if($power_list){
            //从当前用户缓存的侧栏列表中获取侧栏信息
            foreach ($power_list AS $power_key => $power_val){
                if($power_val['group_id'] == $group_menu_id){
                    array_push($result,$power_val);
                }
            }
            return success_result('OK',$result);
        }else{
            return error_result('','权限异常');
        }
    }

    /**
     * 设置当前用户使用的公众号
     * @return \think\Response
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function set_use_wechat(){
        $user = session('user');
        $id = $this->request->post('id','','int');
        if($id){
            //更新用户最后一次使用的公众号id
            $wechat_model = new WechatEmpowerInfoModel();
            $wechat_info = $wechat_model->where(['id'=>$id])->field('id,auth_appid,authorizer_refresh_token')->find();
            if($wechat_info){
                $wechat_info = $wechat_info->toArray();
                if(AdminModel::where(['id'=>$user['id']])->update(['last_use_wechat_id'=>$id])){
                    session('wechat',$wechat_info);
                    return success_result('OK');
                }else{
                    return error_result('','更新失败');
                }
            }else{
                return error_result('','不存在公众号');
            }
        }else{
            return error_result('','参数错误');
        }
    }

    //
    public function get_role_list()
    {
        $lists = AdminRolesModel::where('power_ids','neq',0)->select();
        return success_result('OK',$lists);
    }


    /**
     * 获取用户组
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_user_group_list(){
        $user = session('user');
        $is_all = $this->request->post('is_all',false);
        if($user){
            if(!$is_all){
                if($user['creater_id'] == 0){
                    $result = GroupInfoModel::field('id,group_name')->select();
                }else{
                    $result = GroupInfoModel::field('id,group_name')->where(['leader_id'=>$user['creater_id']])->select();
                }
            }else{
                $result = GroupInfoModel::field('id,group_name')->select();
            }
        }else{
            return error_result('请登录');
        }
        return success_result('OK',$result);
    }

    /**
     * 小组长选择
     * @return \think\Response
     */
    public function get_user_group_leader_list(){
        $result = AdminModel::field('id,name')->where(['creater_id'=>1])->select();
        return success_result('OK',$result);
    }

    /**
     * 添加小组
     * @return \think\Response
     */
    public function user_group_add(){
        $data = [];
        $data['group_name'] = $this->request->post('group_name','','trim');
        $data['leader_id'] = $this->request->post('leader_id',0,'int');
        if(count(array_filter($data)) != count($data)){
            return error_result('参数有误');
        }
        $group = new GroupInfoModel();
        if($group->where(['leader_id'=>$data['leader_id']])->find()){
            return error_result('','该用户已经是组长');
        }
        $data['create_time'] = time();
        if($group->insert($data)){
            //更新用户的小组
            AdminModel::where(['id'=>$data['leader_id']])->update(['user_group'=>$group->getLastInsID()]);
            return success_result('OK');
        }else{
            return error_result('','已有小组或添加失败');
        }
    }

    /**
     * 微信对应平台列表
     * @author lzp
     * @date 2019.12.13
     * @return \think\Response
     * @throws JsonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wechat_platform(){
        $platfrom = WechatPlatform::field('id,name,url')->where(['status'=>1])->select()->toArray();
        if(!$platfrom){
            return error_result('','不存在微信对应平台，请添加');
        }
        return success_result('OK',$platfrom);
    }

    /**
     * 微信对应平台调整处理
     * @author lzp
     * @date 2019.12.13
     * @return \think\Response
     */
    public function wechat_platform_handle(){
        //检测用户是否满足权限
        $this->checkUserPermissions();
        if($this->request->isPost()){
            $wechat_platform = new WechatPlatform();
            //接收参数
            $data = [
                'name' => $this->request->post('name','','trim'),
                'url' => $this->request->post('url','','trim'),
            ];
            //存在id则为更新，否则为添加
            if($this->request->post('id',0,'int')){
                $id =$this->request->post('id',0,'int');
                //更新
                $result = $wechat_platform->save($data,['id'=>$id]);
            }else{
                $data['create_time'] = time();
                //添加
                $result = $wechat_platform->insert($data);
            }
            if($result){
                return success_result('OK');
            }else{
                return error_result('','数据无需更新或添加失败');
            }
        }else{
            return error_result('','请求错误');
        }
    }

    /**
     * 微信对应平台的删除(根据id)
     * @author wh
     * @date 2019年12月24日11:53:48
     * @param Request $request
     * @return \think\Response
     */
    public function wechat_platform_del(Request $request)
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        if($this->request->isPost()){
            //要操作的id
            $id = $request->post('id',0,'int');
            //校验id的name是否正确
            $name = $request->post('name','','string');
            if(empty($id) || empty($name)){
                return error_result('','信息不完整');
            }
            $wechat_platform = new WechatPlatform();
            $idInfo = $wechat_platform->find($id);
            if($idInfo['name'] != $name){
                return error_result('','信息不正确');
            }else {
                $delResult = $idInfo->delete();
                if($delResult){
                    return success_result('OK');
                }
            }


        }else{
            return error_result('','请求错误');
        }
    }


    /**
     * 切换微信对应平台使用状态
     * @author lzp
     * @date 2019.12.13
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wechat_platform_status(){
        //检测用户是否满足权限
        $this->checkUserPermissions();
        if($this->request->isPost()){
            $id = $this->request->post('id',0,'int');
            if($id){
                //获取状态
                $status = WechatPlatform::where(['id'=>$id])->find();
                //转换状态值
                if($status){
                    $status->status = abs($status + -1);
                }
                $status->save();
                return success_result();
            }else{
                return error_result('','参数错误');
            }
        }else{
            return error_result('','请求错误');
        }
    }

    /**
     * 设置公众号对应平台
     * @author lzp
     * @date 2019.12.13
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_wechat_platform(){
        //检测用户是否满足权限
        $this->checkUserPermissions();
        if($this->request->isPost()){
            $platform_id = $this->request->post('platform_id',0,'int');
            $wechat_ids = $this->request->post('wechat_ids',[]);
            if(!$platform_id || !$wechat_ids){
                return error_result('','参数缺省');
            }
            //验证用户权限
            $user = session('user');
            //限制只有组长和管理员才能进行区分平台
            if($user['creater_id'] != 1 && $user['creater_id'] != 0){
                return error_result('','当前用户无权限进行区分操作');
            }
            //验证当前平台是否有效
            $is_platform = WechatPlatform::field('id')->where(['id'=>$platform_id,'status'=>1])->find();
            if(!$is_platform){
                return error_result('','当前平台暂时停用，不可划分至该平台');
            }
            //遍历所有公众号id，将这些id划分至对应平台之下
            foreach ($wechat_ids AS $id_key => $id_val){
                $wechat_info = WechatEmpowerInfoModel::update(['id'=>$id_val,'platform_id'=>$platform_id]);
            }
            return success_result('OK');
        }else{
            return error_result('','请求错误');
        }
    }

}
