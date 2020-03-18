<?php
namespace app\admin\controller;

use app\common\exception\JsonException;
use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\model\GroupInfoModel;
use app\common\model\WechatEmpowerInfoModel;
use think\Exception;
use think\Request;

/**
 * 微信公众号菜单 控制器.
 * Class WechatMenuController
 * @package app\admin\controller
 */
class WechatMenuController extends AdminBase
{

    public function getmenu()
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $menulist = WechatFunBase::get_current_selfmenu_info($appid,$authorizer_refresh_token);
        return success_result('获取成功',$menulist);
    }
    public function add(Request $request)
    {
        //前端传过来的menu 数组. 需要后端进行解析后在使用
        $menuArr = $request->post('menuArr');
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $result = WechatFunBase::create_wechat_public_num_menu($appid,$authorizer_refresh_token,$menuArr);
        return success_result('添加成功',$result);
    }

    /**
     * 统一更改公众号客服链接
     * @author lzp
     * @date 2019.12.11
     * @updatetime 2020年01月14日11:31:35 wh
     * @throws JsonException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function set_service_img_url(){
        $result_msg = '';
        if($this->request->post()){
            //检查权限
            $this->checkUserPermissions();

            $url = $this->request->post('url','','trim');
            $str = $this->request->post('string','客服','trim');
            $ids = $this->request->post('ids',[]);

            if(count($ids) == 0){
                return error_result('','请正确选择公众号');
            }
            //手动异常统一处理错误信息
            if(!$url){
                throw new JsonException(0,'参数错误');
            }
            //获取当前组下所有公众号
            $wechats = WechatEmpowerInfoModel::field('id,nick_name,auth_appid,authorizer_refresh_token')->where('id','in',$ids)->select()->toArray();
            if(!$wechats){
                throw new JsonException(0,'当前组所属没有公众号');
            }
            $errorWechatNames = '';
            //批量获取公众号菜单
            foreach ($wechats AS $wechat_info_keys => $wechat_info_val){
                $is_new = false;
                //发起请求，获取当前循环对应公众号的菜单
                $menu_info = WechatFunBase::get_current_selfmenu_info($wechat_info_val['auth_appid'],$wechat_info_val['authorizer_refresh_token']);
                if(!empty($menu_info['errcode'])){
                    $errorWechatNames.=$wechat_info_val['nick_name'].',';
//                    return error_result('','获取菜单出错',$menu_info);
                    continue;
                }
                //不存在菜单信息，不存在菜单按钮，不存在第二个菜单，第二个菜单不是多栏目菜单
                if(!$menu_info || !isset($menu_info['selfmenu_info']['button']) ||
                    !isset($menu_info['selfmenu_info']['button'][1]) || !isset($menu_info['selfmenu_info']['button'][1]['sub_button']) ||
                    !isset($menu_info['selfmenu_info']['button'][1]['sub_button']['list'])){
                    //-----待优化，跳过了获取不到公众号菜单的微信号，可返回对应的信息-----
                    $errorWechatNames.=$wechat_info_val['nick_name'].',';
                    continue;
                }
                //循环目标栏目
                foreach ($menu_info['selfmenu_info']['button'][1]['sub_button']['list'] AS $menu_key => $menu_val){
                    //存在匹配的目标字段，全等解决返回数字为0判断失误问题
                    if(strpos($menu_val['name'],$str) === intval(strpos($menu_val['name'],$str))){
                        //替换，调整公众号菜单更新依据，终止小循环
                        $menu_info['selfmenu_info']['button'][1]['sub_button']['list'][$menu_key]['url'] = $url;
                        $is_new = true;
                        break;
                    }
                }
                //根据更新依据判定是否执行更新
                if($is_new){
                    //清空确保无冗余
                    $new_menu = [];
                    //调整所需传递数据
                    $new_menu['button'] = $menu_info['selfmenu_info']['button'];
                    $new_menu['button'][1]['sub_button'] = null;
                    $new_menu['button'][1]['sub_button'] = $menu_info['selfmenu_info']['button'][1]['sub_button']['list'];
                    //更新执行
                    $result = WechatFunBase::create_wechat_public_num_menu($wechat_info_val['auth_appid'],$wechat_info_val['authorizer_refresh_token'],$new_menu);
                    if($result === false){
                        $errorWechatNames.=$wechat_info_val['nick_name'].',';
//                        return error_result('','更新菜单出错',$result);
                        continue;
                    }
                }else{
                    $errorWechatNames.=$wechat_info_val['nick_name'].',';
                }
            }
            if(!empty($errorWechatNames)){
                return error_result('',"{$errorWechatNames}没有成功更改");
            }
            return success_result('已全部更改');
        }else{
            throw new JsonException(0,'请求失败');
        }
    }
}