<?php
namespace app\index\controller;

use app\common\constant\CacheKeyConstant;
use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\model\ServiceMessageListModel;
use app\common\model\WechatEmpowerInfoModel;
use app\common\tool\Wlog;
use think\facade\Log;
use think\facade\Cache;
use think\Request;

class Index extends Base
{
    public function index(Request $request)
    {
        $redis   = self::get_redis();
        if($request->get('action') == 'selectSentResult'){
            //客服消息的key
            $serviceMsgKey = CacheKeyConstant::SERVICE_MSG.'*';
            //获取客服消息所有存储的信息
            $serviceContent = $redis->keys($serviceMsgKey);
            halt($serviceContent);
            foreach ($serviceContent as $serviceval) {
                $serviceredisval = $redis->get($serviceval);
                $serviceredisvalArr = explode('_',$serviceredisval);
                dump('id为:'.$serviceval.'>>>>'.$serviceredisvalArr[0]);
            }
            die;
        }
        $abc = 'aaa';
        if($abc != 'abc'){
            Wlog::write('testlog','测试专用.'.date('Y-m-d H:i:s'));
        }
        $data = $redis->get('component_token');
        dump($data);die;
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    public function getitem()
    {
        $where = [];
        $paginate = 15;
        $order = 'create_time DESC';
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
        $res["list"] = $lists->toArray();
        foreach ($res['list']['data'] as $key => $value)
        {
            if($value['sent_status'] === 1){
                ServiceMessageListModel::destroy($value['id']);
            }
        }
        return success_result('删除成功');

    }
    
}
