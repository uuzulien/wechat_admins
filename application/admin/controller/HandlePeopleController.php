<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\enums\ErrorCode;
use app\common\model\AdminModel;
use app\common\model\GroupInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use think\Request;

/**
 * 操作员管理控制器
 * Class HandlePeopleController
 * @package app\admin\controller
 */
class HandlePeopleController extends AdminBase
{
    /**
     * 操作员列表.
     */
    public function index()
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        $limit = request()->post('limit/d',20);
        $page = request()->post('page/d',1);
        $name = request()->post('username', '','trim');

        //检测当前用户是否是leader.
        $nowuserid = session('user')['id'];
        $leaderinfo = GroupInfoModel::field('id')->where(['leader_id'=>$nowuserid])->find();
        //如果当前用户不是组长. 那么就抛出异常.
        if(empty($leaderinfo)){
            return error_result(ErrorCode::NOT_NETWORK);
        }

        $where = [];
        $order = 'id DESC';
        if (!empty($name)) {
            $where[] = ['username', 'like', $name . '%'];
        }

        //如果不是管理员
        if($nowuserid != 1){
            //查出当前小组的
            $where[] = ['user_group', '=', $leaderinfo['id']];
        }
        //去除当前本人
        $where[] = ['id', 'neq', $nowuserid];
        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => $limit <= 0 ? 20 : $limit,
        ];
        $lists = AdminModel::field('id,username,name,user_group,status,create_time,power_group,last_use_datetime,creater_id')
            ->where($where)
            ->order($order)
            ->paginate($paginate);

        foreach ($lists->items() as $userkey => $userval ) {
            $gzhwhere = [];
            if($userval['creater_id'] == 0){
            }else if($userval['creater_id'] == 1){
                $gzhwhere[] = ['user_group','eq',$userval['user_group']];
            }else{
                $gzhwhere[] = ['use_user_id','eq',$userval['id']];
            }
            $gzhinfo = WechatEmpowerInfoModel::field('nick_name,user_group')->where($gzhwhere)->select()->toArray();
            $lists->items()[$userkey]['user_group'] = $gzhinfo;
        }
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = $lists->items();
        return success_result('成功',$res);
    }


    /**
     * 根据id获取详细信息
     * @param Request $request
     * @return \think\Response
     */
    public function getInfoForId(Request $request)
    {
        $id = $request->post('id/d',0);
        if(empty($id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $iduserinfo = AdminModel::find($id);
        if(empty($iduserinfo)){
            return error_result(ErrorCode::DATA_NOT);
        }
        return success_result('成功',$iduserinfo);
    }

    /**
     * 操作员添加
     */


    public function add(Request $request)
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        $username = $request->post('username');
        $password = $request->post('password');
        $name = $request->post('name');
        $status = $request->post('status/d',1);

        //获取前端传过来 要修改use_user_id的  公众号id。 是个数组
        $wechats = $request->post('wechats',[]);

        $power_group_id = $request->post('power_group_id') ?? session('user')['power_group'];
        if(!$power_group_id || !$username || !$password){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }

        if(AdminModel::where(['username'=>$username])->count() > 0){
            return error_result(ErrorCode::ADMIN_DATA_REPEAT);
        }
        $adminuser = new AdminModel();
        $adminuser->username = $username;
        $adminuser->password = md5($password);
        $adminuser->creater_id = session('user')['id'];
        $adminuser->name = $name;
        $adminuser->status = $status;
        $adminuser->power_group = $power_group_id;
        $adminuser->user_group = session('user')['user_group'];
        $adminuser->create_time = time();
        $result = $adminuser->save();

        if($result){
            WechatEmpowerInfoModel::saveWechatEmpowerUseUserId($wechats,$adminuser->id);
            return success_result('添加成功');
        }
    }


    /**
     * 操作员信息修改
     */

    public function update(Request $request)
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        //要修改的操作员id
        $id = $request->post('id');
        $username = $request->post('username');
        $password = $request->post('password');
        $name = $request->post('name');
        //获取前端传过来 要修改use_user_id的  公众号id。 是个数组
        $wechats = $request->post('wechats',[]);

        if(!$username || !$id){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $nowuserinfo = AdminModel::find($id);
        if(empty($nowuserinfo)){
            return error_result(ErrorCode::DATA_NOT);
        }
        $nowuserinfoid = AdminModel::field('id')->where(['username'=>$username])->find();
        if(!empty($nowuserinfoid['id']) && $nowuserinfoid['id'] != $id){
            return error_result(ErrorCode::ADMIN_DATA_REPEAT);
        }
        $nowuserinfo->username = $username;
        if(!empty($password)){
            $nowuserinfo->password = md5($password);
        }
        $nowuserinfo->name = $name;
        $nowuserinfo->update_time = time();
        $result = $nowuserinfo->save();
        if($result){
            WechatEmpowerInfoModel::saveWechatEmpowerUseUserId($wechats,$nowuserinfo->id);
            return success_result('修改成功');
        }
    }



    /**
     * 管理员禁用
     */
    public function disableOrOpen(Request $request)
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        $id = $request->post('id');
        if(!$id){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $AdminModel = AdminModel::field('status')->find($id);
        if($AdminModel['status'] == 0){
            $status = 1;
        }else{
            $status = 0;
        }
        $AdminModel->status = $status;
        $result = $AdminModel->save();
        if($result){
            return success_result('修改成功');
        }
    }



    /**
     * 操作员删除
     */

    public function delete(Request $request)
    {
        //检测用户是否满足权限
        $this->checkUserPermissions();
        $id = $request->post('id');
        $nowuserid = session('user')['id'];
        $leaderinfo = GroupInfoModel::field('id')->where(['leader_id'=>$nowuserid])->find();
        //如果当前用户不是组长. 那么就抛出异常.
        if(empty($leaderinfo)){
            return error_result(ErrorCode::NOT_NETWORK);
        }
        //如果删除用户的话,必须将当前用户之前的公众号use_user_id 改为0
        WechatEmpowerInfoModel::where(['use_user_id'=>$id])->update(['use_user_id'=>0]);
//        $result = AdminModel::destroy($id);
        //这边删除不要直接删除,以免他之前添加的群发消息查看不到.
        $result = AdminModel::where(['id'=>$id])->update(['status'=>0]);
        if($result){
            return success_result('删除成功');
        }
    }
}