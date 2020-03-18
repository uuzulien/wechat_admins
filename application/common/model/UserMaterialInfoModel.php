<?php
namespace app\common\model;

use app\common\exception\JsonException;
use think\Db;
use think\facade\Log;
use think\Model;

/**
 * 用户素材表
 * UserMaterialInfoModel模型类
 * @author muyufeng
 *
 */
class UserMaterialInfoModel   extends Model
{
    protected $name = 'user_material_info';


    public function synchronousWechat ($saveDataArrs,$appid,$type)
    {
        Db::startTrans();
        try{
            self::where(['file_type'=>$type])
                ->where(['is_tongbu'=>1])
                ->where(['appid'=>$appid])
                ->delete();
            //添加之前删除之前同步的
            $result = self::saveAll($saveDataArrs);
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            Log::error($e->getMessage());
            throw new JsonException('',$e->getMessage());

        }

        return $result;

    }
}