<?php
namespace app\common\tool;

use app\common\controller\Base;
use app\common\controller\WechatFunBase;
use app\common\enums\ErrorCode;
use app\common\exception\JsonException;
use app\common\model\UserMaterialInfoModel;
use think\Controller;
use think\Db;
use think\facade\Env;
use think\facade\Log;

/**
 * 上传文件件 功能类 。(仅支持单个)
 * Class CurlFile
 * @package app\common\tool
 */
class UploadFile extends Controller
{
    /**
     * @param $uploadFile 上传的文件
     * @param $fileType 文件类型 image voice video
     * @param $exts 要验证的文件后缀
     * @param $size  要验证的文件大小
     * @param $isUploadWechat 是否要上传到微信媒体库 1上传 0不传
     * @param $appid 上传到哪个公众号的媒体- - 这里是appid来区分.
     * @return \think\Response
     */
    public static function upload_file($uploadFile,$fileType,$exts,$size,$isUploadWechat = 0,$appid = '',$authorizer_refresh_token='')
    {
        if (empty($fileType)) {
            //缺少文件类型.
            throw new JsonException(ErrorCode::DATA_VALIDATE_FAIL);
        }
        $config = [];
        if ($size > 0) {
            $config['size'] = $size;
        }
        if ($exts) {
            $config['ext'] = $exts;
        }
        $basepath = Env::get('root_path') . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        $filePath = (!empty($fileType) ? $fileType : "image") . DIRECTORY_SEPARATOR;
        $filepath = $basepath . $filePath;
        $wanzhengurl = '';
        Db::startTrans();
        try{
            //单图上传
            $info = $uploadFile->validate($config)->move($filepath);
            if (!$info) {
                throw new JsonException(100,$uploadFile->getError());
            }
            $saveName = $info->getSaveName();
            $filetypeandsavename = $fileType.DIRECTORY_SEPARATOR.$saveName;
            //完整的url 格式：/var/www/xxx.jpg
            $wanzhengurl = $filepath.$saveName;

            //需要存储到微信媒体库
            if($isUploadWechat == 1){
                $media_info = WechatFunBase::wechat_media_upload($appid,$authorizer_refresh_token,$wanzhengurl,$fileType);
                if(!$media_info){
                    return false;
                }
            }

            //进行素材入库;.
            $userMaterialTable = UserMaterialInfoModel::where(['media_id'=>$media_info['media_id']])->find();
            if(empty($mediainfo)){
                $userMaterialTable = new UserMaterialInfoModel();
            }
            $userMaterialTable->file_type = $fileType;
            $userMaterialTable->link_url = $media_info['url'] ?? '';
            $userMaterialTable->user_id = session('user')['id'] ?? '';
            $userMaterialTable->appid = $appid;
            $userMaterialTable->media_id = $media_info['media_id'] ?? '';
            $userMaterialTable->is_tongbu = 1;
            $userMaterialTable->create_time = time();
            $userMaterialTable->save();

            //如果已经存储到微信了 那么就把本地的删除了。。用微信的url
            //删除已经上传的文件.
            if(is_file($wanzhengurl)){
                unlink($wanzhengurl);
            }
            $res = [];
            $res['link_url'] = $media_info['url'];
            $res['file_type'] = $fileType;
            $res['media_id'] = $media_info['media_id'] ?? '';
            Db::commit();
            return $res;
        }catch (\Exception $e){
            //文件上传失败 回滚所有操作.
            Db::rollback();
            //删除已经上传的文件.
            if(is_file($wanzhengurl)){
                unlink($wanzhengurl);
            }
            Log::error('上传文件这边显示的错误信息为:'.$e->getMessage());
            throw new JsonException($e->getCode(),$e->getMessage());
        }

    }
}