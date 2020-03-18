<?php
namespace app\common\model;

use think\Model;

/**
 * Admin模型类
 * @author muyufeng
 *
 */
class AdminModel   extends Model
{
    protected $name = 'admin_users';

    /**
     * powergroup字段获取器
     * @param $value
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPowerGroupAttr($value)
    {
        if(!empty($value)){
            $adminroleinfo = AdminRolesModel::withAttr('power_ids', function($value, $data) {
                $idsInfo = AdminRolesPowerModel::field('id,name,group_id')->where('id','in',$value)->where('group_id','neq',0)->select()->toArray();
                $newArr = self::getMergeRoleArr($idsInfo);
                return $newArr;
            })->find($value);
            return $adminroleinfo;
        }elseif ($value ==0){
            $idsInfo = AdminRolesPowerModel::field('id,name,group_id')->select()->toArray();
            $newArr = self::getMergeRoleArr($idsInfo);
            return $newArr;

        }
        return $value;
    }


//    /**
//     * user_group 字段获取器
//     * @param $value
//     * @return array
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function getUserGroupAttr($value)
//    {
//        if(!empty($value)){
//            $allinfo = WechatEmpowerInfoModel::field('nick_name,user_group')->where(['user_group'=>$value])->select()->toArray();
//            return $allinfo;
//        }
//
//        return $value;
//    }



    public function getCreateTimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }
    public function getLastUseDatetimeAttr($value)
    {
        if(!empty($value)){
            $value = date('Y-m-d H:i:s',$value);
        }
        return $value;
    }


    /**
     * 合并上下级数组-
     * @param $arr 要更改的数组
     * @param $super 上级id
     */
    public static function getMergeRoleArr($arr,$super = 0)
    {
        $oneData = AdminRolesPowerModel::where(['group_id'=>$super])->select();
        $newArr = [];
        foreach ($oneData as $k => $v)  {
            if($v['group_id'] == $super){
                $newArr[$k] = [
                    'id' => $v['id'],
                    'name'=>$v['name'],
                ];
            }
            foreach ($arr as $kk=>$vv) {
                if($vv['group_id'] == $v['id']){
                    $newArr[$k]['lower'][] = $vv;
                }
            }
        }

        return $newArr;
    }
}