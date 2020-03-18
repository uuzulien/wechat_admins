<?php
namespace app\admin\controller;

use app\common\controller\ApiBase;
use app\common\model\AdminModel;
use app\common\model\AdminRolesModel;
use app\common\model\AdminRolesPowerModel;
use app\common\model\WechatEmpowerInfoModel;

class Auth extends ApiBase{

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
        $role_info = $role_model::field('name,power_ids')->where(['id'=>$user_info['power_group'],'status'=>1])->find();
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
        //记录最后使用时间
        $admin_model::where(['id'=>$user_info['id']])->update(['last_use_datetime'=>time()]);
        //获取最后一次使用的公众号信息
        $wechat_model = new WechatEmpowerInfoModel();
        $wechat_info = $wechat_model->where(['id'=>$user_info['last_use_wechat_id']])->field('id,auth_appid,authorizer_refresh_token')->find();
        if($wechat_info) {
            $wechat_info = $wechat_info->toArray();
            session('wechat',$wechat_info);
        }else{
            $where = [];
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
            //---当前账号没有最后一次使用的微信id，从组内安排一个
            $wechat_info = $wechat_model->where($where)->field('id,auth_appid,authorizer_refresh_token')->find();
            if($wechat_info) {
                $wechat_info = $wechat_info->toArray();
                //记录微信id信息并存入缓存
                $admin_model::where(['id'=>$user_info['id']])->update(['last_use_wechat_id'=>$wechat_info['id']]);
                session('wechat',$wechat_info);
            }
        }

        //更换字段
        if(!empty($user_info['last_use_wechat_id'])){
            $name = WechatEmpowerInfoModel::field('nick_name')->find($user_info['last_use_wechat_id']);
            $user_info['nick_name'] = $name['nick_name'] ?? '';
        }
        //存入session
        session('user',$user_info);
        session('role',$role_info);
        session('power',$power_info);
        return success_result('OK',$user_info);
    }

    public function login_out(){
        session('user',null);
        session('role',null);
        session('power',null);
        return success_result();
    }
}