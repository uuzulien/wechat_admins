<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\model\UserMaterialInfoModel;
use think\Request;


/**
 * 素材管理控制器
 * Class MaterialManageController
 * @package app\admin\controller
 */
class MaterialManageController extends AdminBase
{


    /**
     * 素材列表
     * @param Request $request
     * @return \think\Response
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $file_type = $request->post('file_type','','trim');
        $appid = session('wechat')['auth_appid'];
        if(empty($file_type) || empty($appid)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $where = [];
        $order = 'id DESC';
        switch ($file_type) {
            case 'image':
                $where[] = ['file_type', '=', 'image'];
                break;
            case 'voice':
                $where[] = ['file_type', '=', 'voice'];
                break;
            case 'video':
                $where[] = ['file_type', '=', 'video'];
            case 'news':
                $where[] = ['file_type', '=', 'news'];
                break;
        }
        //只查当前公众号的.:
        $where[] = ['appid', '=', $appid];
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => $limit <= 0 ? 20 : $limit,
        ];
        $lists = UserMaterialInfoModel::where($where)->order($order)->paginate($paginate);
        //如果类型是图文的话,那么重新组合一下这个数组.
        $newlists = [];
        if($file_type == 'news'){
            foreach ($lists->items() as $k => $v) {
                $newlists[$v['media_id']]['media_id'] = $v['media_id'];
                $newlists[$v['media_id']]['data'][] = $v;
            }
        }else{
            $newlists = $lists->items();
        }
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = array_values($newlists);
        return success_result('成功',$res);
    }


    /**
     * 同步更新 微信素材库的东西
     * @postParam type 素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）
     */
    public function synchronousUpdate(Request $request)
    {
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $type = $request->post('type');
        if(empty($appid) || empty($authorizer_refresh_token) || empty($type)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }

        $materialtable = new UserMaterialInfoModel();
        switch ($type) {
            case 'image':
                $imageinfo = WechatFunBase::batchget_material($appid,$authorizer_refresh_token,'image');
                if(!empty($imageinfo['errcode'])){
                    return error_result('','获取失败',$imageinfo['item']);
                }
                $saveDataArrs = $this->mergeImageArr($imageinfo['item'],$appid);
                break;
            case 'video':
                break;
            case 'voice':
                $voiceinfo = WechatFunBase::batchget_material($appid,$authorizer_refresh_token,'voice');
                if(!empty($voiceinfo['errcode'])){
                    return error_result('','获取失败',$voiceinfo['item']);
                }
                $saveDataArrs = $this->mergeVoiceArr($voiceinfo['item'],$appid);
                break;
            case 'news':
                $newsinfo = WechatFunBase::batchget_material($appid,$authorizer_refresh_token,'news');
                if(!empty($newsinfo['errcode'])){
                    return error_result('','获取失败',$newsinfo['item']);
                }
                $saveDataArrs = $this->mergeNewsArr($newsinfo['item'],$appid);
                break;
        }
        //进行同步的操作.
        $result = $materialtable->synchronousWechat($saveDataArrs,$appid,$type);
        if($result){
            return success_result('同步成功');
        }
    }

    public function mergeNewsArr($newsinfo,$appid)
    {
        $saveDataArrs = [];
        foreach ($newsinfo as $k => $v) {
            foreach ($v['content']['news_item'] as $kk => $vv) {
                $tpmArr['media_id'] = $v['media_id'];
                $tpmArr['news_create_time'] = $v['content']['create_time'];
                $tpmArr['news_update_time'] = $v['content']['update_time'];
                $tpmArr['news_title'] = $vv['title'];
                $tpmArr['news_author'] = $vv['author'];
                $tpmArr['news_digest'] = $vv['digest'];
                $tpmArr['news_content'] = $vv['content'];
                $tpmArr['news_content_source_url'] = $vv['content_source_url'];
                $tpmArr['news_thumb_media_id'] = $vv['thumb_media_id'];
                $tpmArr['news_show_cover_pic'] = $vv['show_cover_pic'];
                $tpmArr['news_url'] = $vv['url'];
                $tpmArr['news_thumb_url'] = $vv['thumb_url'];
                $tpmArr['news_order'] = $kk;
                $tpmArr['file_type'] = 'news';
                $tpmArr['create_time'] = time();
                $tpmArr['user_id'] = session('user')['id'];
                $tpmArr['appid'] = $appid;
                $tpmArr['is_tongbu'] = 1;
                array_push($saveDataArrs,$tpmArr);
            }
        }

        return $saveDataArrs;
    }


    public function mergeImageArr($imageinfo,$appid)
    {
        $saveDataArrs = [];
        foreach ($imageinfo as $k=>$v) {
            $tpmArr['media_id'] = $v['media_id'];
            $tpmArr['user_id'] = session('user')['id'];
            $tpmArr['link_url'] = $v['url'];
            $tpmArr['file_type'] = 'image';
            $tpmArr['appid'] = $appid;
            $tpmArr['is_tongbu'] = 1;
            $tpmArr['create_time'] = time();
            array_push($saveDataArrs,$tpmArr);
        }
        return $saveDataArrs;
    }
    public function mergeVoiceArr($voiceinfo,$appid)
    {
        $saveDataArrs = [];
        foreach ($voiceinfo as $k=>$v) {
            $tpmArr['media_id'] = $v['media_id'];
            $tpmArr['user_id'] = session('user')['id'];
            $tpmArr['file_type'] = 'voice';
            $tpmArr['appid'] = $appid;
            $tpmArr['is_tongbu'] = 1;
            $tpmArr['voice_name'] = $v['name'];
            $tpmArr['create_time'] = time();
            array_push($saveDataArrs,$tpmArr);
        }
        return $saveDataArrs;
    }




    /**
     * 素材删除
     * @param Request $request
     */

    public function delete(Request $request)
    {
        $id = $request->post('id/d',0);
        $appid = session('wechat')['auth_appid'];
        $authorizer_refresh_token = session('wechat')['authorizer_refresh_token'];
        $media_id = $request->post('media_id');
        if(empty($id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $nowfileinfo = UserMaterialInfoModel::field('media_id')->where(['appid'=>$appid])->find($id);
        if(empty($nowfileinfo)){
            return error_result(ErrorCode::DATA_NOT);
        }
        if($nowfileinfo['media_id'] != $media_id){
            return error_result('','媒体id不匹配');
        }
        //删除自己服务器的
        $result = UserMaterialInfoModel::where(['media_id'=>$media_id])
            ->where(['appid'=>$appid])
            ->delete();
        if($result){
            //删除微信那边的
            $delWechatMaterialResult = WechatFunBase::del_material($media_id,$appid,$authorizer_refresh_token);
            if($delWechatMaterialResult === false){
                return error_result('','删除失败',$delWechatMaterialResult);
            }
            return success_result('删除成功');
        }
    }
}