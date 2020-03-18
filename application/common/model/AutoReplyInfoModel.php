<?php
namespace app\common\model;

use think\Model;

/**
 * 自动回复表
 * AutoReplyInfoModel模型类
 * @author muyufeng
 *
 */
class AutoReplyInfoModel   extends Model
{
    protected $name = 'auto_reply_info';


    public function getTuwenReplyAttr($value)
    {
        $newsInfo = UserMaterialInfoModel::where(['media_id'=>$value])->select();
        if(!empty($newsInfo)){
            $newArr = [];
            foreach ($newsInfo as $k=>$v) {
                $tmpArr['title'] = $v['news_title'];
                $tmpArr['img'] = $v['news_thumb_url'];
                $tmpArr['des'] = $v['news_digest'];
                $tmpArr['link'] = $v['news_url'];
                array_push($newArr,$tmpArr);
            }
            return $newArr;
        }

        return $value;
    }
}