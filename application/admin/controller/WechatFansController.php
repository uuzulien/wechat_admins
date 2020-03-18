<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\WechatEmpowerInfoModel;
use app\common\model\WechatUserInfoModel;
use think\Db;
use think\Request;

/**
 * 粉丝管理
 * Class WechatFansController
 * @package app\amdin\controller
 */
class WechatFansController extends AdminBase
{
    /**
     * 粉丝列表
     * @param Request $request
     */
    public function index(Request $request)
    {
        //获取本地已经存储的微信公众号id
        $wechatId = session('wechat')['id'];
        //如果为空, 就return
        if(empty($wechatId)){
            return error_result('','没有选择公众号');
        }
        //查出当前公众号的详细信息
        $wechatinfo = WechatEmpowerInfoModel::field('auth_appid')->find($wechatId);
        //取出当前公众号的appid
        $auth_appid = $wechatinfo['auth_appid'] ?? '';
        //如果为空 代表公众号错误,
        if(empty($auth_appid)){
            return error_result('','该公众号有误.');
        }



        $where = [];
        $order = 'id DESC';
        //检索名字
        $name = request()->post('name', '','trim');
        if (!empty($name)) {
            $where[] = ['nickname', 'like', $name . '%'];
        }
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => $limit <= 0 ? 20 : $limit,
        ];

        //查询当前公众号的
        $where[] = ['subscribe','=',1];
        $where[] = ['appid','=',$auth_appid];
        $lists = WechatUserInfoModel::where($where)
            ->order($order)
            ->paginate($paginate);
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = $lists->items();
        return success_result('成功',$res);

    }
}