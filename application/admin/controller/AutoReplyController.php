<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\enums\ErrorCode;
use app\common\model\AutoReplyInfoModel;
use app\common\model\UserMaterialInfoModel;
use think\Request;

/**
 * 公众号自动回复控制器
 * Class AutoReplyController
 * @package app\admin\controller
 */
class AutoReplyController extends AdminBase
{
    public function index(Request $request)
    {
        $where = [];
        $order = 'id DESC';
        $limit = request()->post('limit/d', 20);
        $page = request()->post('page/d', 1);
        $appid = session('wechat')['auth_appid'];
        //分页配置
        $paginate = [
            'type' => 'bootstrap',
            'page' => $page,
            'list_rows' => ($limit <= 0 || $limit > 20) ? 20 : $limit,
        ];

        //当前公众号的
        $where[] = ['appid','=',$appid];
        $lists = AutoReplyInfoModel::where($where)->paginate($paginate);
        $res = [];
        $res["total"] = $lists->total();
        $res["list"] = $lists->items();
        return success_result('成功',$res);
    }

    /**
     * 添加回复信息
     * @param $type 回复种类 , 1:关键字回复/2:关注自动回复/3:统一回复
     * @param $reply_type 回复信息类型,1:文字/2:图片/3:音频/4:图文
     * @param Request $request
     */
    public function add(Request $request)
    {

        $autoReplyTable = new AutoReplyInfoModel();
        $type = $request->post('type/d',0);
        $reply_type = $request->post('reply_type/d',0);
        $appid = session('wechat')['auth_appid'];
        $mediaid_reply = $request->post('mediaid_reply');
        /**********************关键字回复的时候才会用到*********************/
        //关键字 如果多个关键字的话 存储格式为:   key1,key2,key3
        $keyword = $request->post('keyword');
        $text_reply = $request->post('text_reply');
        /**********************结束*********************/

        /**********************图文的时候会用到*********************/
        //标题
        $title = $request->post('title');
        //描述
        $des = $request->post('des');
        //链接
        $linkurl = $request->post('linkurl');
        //图片素材id
        $img_media_id = $request->post('mediaid_reply');
        //图片地址
        $imgurl = $request->post('imgurl');
        /**********************结束*********************/

        if(empty($type) || empty($reply_type) || empty($appid)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        switch ($type) {
            case 1:
                if(empty($keyword)){
                    return error_result(ErrorCode::DATA_VALIDATE_FAIL);
                }
                $autoReplyTable->keyword = $keyword;
                break;
        }
        if($reply_type == 1){
            $autoReplyTable->text_reply = $text_reply;
        }elseif ($reply_type == 2 || $reply_type == 3) {
            $autoReplyTable->mediaid_reply = $mediaid_reply;
        }elseif ($reply_type == 4) {
            if(empty($title) || empty($des)){
                return error_result(ErrorCode::DATA_VALIDATE_FAIL);
            }
            /********是图文的话 要添加到本地素材库中.*********/
            $sucaitable = UserMaterialInfoModel::field('id,media_id')->where(['news_title'=>$title])->find();
            $bendi_media_id = $sucaitable['media_id'];
            //如果相同标题存在的话 那么就不管了.
            if(empty($sucaitable)){
                //生成自己本地的媒体id
                $bendi_media_id = rand_32_string();
                $sucaitable = new UserMaterialInfoModel();
                $sucaitable->user_id = session('user')['id'];
                $sucaitable->create_time = time();
                $sucaitable->file_type = 'news';
                $sucaitable->appid = session('wechat')['auth_appid'];
                $sucaitable->media_id = $bendi_media_id;
                $sucaitable->news_title = $title;
                $sucaitable->news_digest = $des;
                $sucaitable->news_url = $linkurl;
                $sucaitable->news_thumb_media_id = $img_media_id;
                $sucaitable->news_thumb_url = $imgurl;
                $sucaitable->news_create_time = time();
                $sucaitable->is_tongbu = 0;
                $sucaitable->save();
            }
            /********************结束*****************/
            $autoReplyTable->tuwen_reply = $bendi_media_id;
        }
        $autoReplyTable->type = $type;
        $autoReplyTable->reply_type = $reply_type;
        $autoReplyTable->appid = $appid;
        $autoReplyTable->create_time = time();
        $result = $autoReplyTable->save();
        if($result){
            return success_result('添加成功');
        }

    }

    /**
     * 自动回复条目修改
     */

    public function update(Request $request)
    {
        //需要更改的id
        $id = $request->post('id');
        $type = $request->post('type');
        $reply_type = $request->post('reply_type');
        $appid = session('wechat')['auth_appid'];
        if(empty($type) || empty($reply_type) || empty($appid) || empty($id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }

        $autoReplyTable = AutoReplyInfoModel::field('id')->find($id);
        switch ($type) {
            case 1:
                //关键字 如果多个关键字的话 存储格式为:   key1,key2,key3
                $keyword = $request->post('keyword');
                if(empty($keyword)){
                    return error_result(ErrorCode::DATA_VALIDATE_FAIL);
                }
                $autoReplyTable->keyword = $keyword;
                break;
            default:
                $autoReplyTable->keyword = '';
        }
        if($reply_type == 1){
            $text_reply = $request->post('text_reply');
            $autoReplyTable->text_reply = $text_reply;
            $autoReplyTable->mediaid_reply = '';
            $autoReplyTable->tuwen_reply = '';
        }elseif ($reply_type == 2 || $reply_type == 3) {
            $mediaid_reply = $request->post('mediaid_reply');
            $autoReplyTable->mediaid_reply = $mediaid_reply;
            $autoReplyTable->text_reply = '';
            $autoReplyTable->tuwen_reply = '';
        }elseif ($reply_type == 4) {
            /********是图文的话 要添加到本地素材库中.*********/
            //标题
            $title = $request->post('title');
            //描述
            $des = $request->post('des');
            //链接
            $linkurl = $request->post('linkurl');
            //图片素材id
            $img_media_id = $request->post('media_id');
            //图片地址
            $imgurl = $request->post('imgurl');

            $bendi_media_id = $request->post('bendi_media_id');
            //是图文的话 要添加到素材库中.
            $sucaitable = UserMaterialInfoModel::where(['media_id'=>$bendi_media_id])->find();
            if(empty($sucaitable)){
                //生成自己本地的媒体id
                $bendi_media_id = rand_32_string();
                $sucaitable = new UserMaterialInfoModel();
            }
            $sucaitable->user_id = session('user')['id'];
            $sucaitable->file_type = 'news';
            $sucaitable->appid = session('wechat')['auth_appid'];
            $sucaitable->media = $bendi_media_id;
            $sucaitable->news_title = $title;
            $sucaitable->news_digest = $des;
            $sucaitable->news_url = $linkurl;
            $sucaitable->news_thumb_media_id = $img_media_id;
            $sucaitable->news_thumb_url = $imgurl;
            $sucaitable->news_update_time = time();
            $sucaitable->is_tongbu = 0;
            $sucaitable->save();
            /*****************结束******************/

            $autoReplyTable->tuwen_reply = $bendi_media_id;
            $autoReplyTable->text_reply = '';
            $autoReplyTable->mediaid_reply = '';
        }
        $autoReplyTable->type = $type;
        $autoReplyTable->reply_type = $reply_type;
        $autoReplyTable->appid = $appid;
        $autoReplyTable->update_time = time();
        $result = $autoReplyTable->save();
        if($result){
            return success_result('添加成功');
        }

    }

    /**
     * 启用或者禁用
     * @param Request $request
     * @return \think\Response
     */
    public function disableOrOpen(Request $request)
    {
        $id = $request->post('id/d',0);
        if(empty($id)){
            return error_result(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $autoReplyModel = AutoReplyInfoModel::field('status')->find($id);
        if(empty($autoReplyModel)){
            return error_result(ErrorCode::DATA_NOT);
        }
        if($autoReplyModel['status'] == 0){
            $status = 1;
        }else{
            $status = 0;
        }
        $autoReplyModel->status = $status;
        $result = $autoReplyModel->save();
        if($result){
            return success_result('修改成功');
        }
    }


    /**
     * 自动回复条目删除
     */

    public function delete(Request $request)
    {
        $id = $request->post('id/d');
        $appid = session('wechat')['auth_appid'];
        $autoinfo = AutoReplyInfoModel::find($id);
        if($autoinfo['appid'] != $appid){
            return error_result('','公众号错误');
        }
        $result = $autoinfo->delete();
        if($result){
            return success_result('删除成功');
        }
    }
}