<?php
namespace app\common\tool;

use think\Controller;
use think\facade\App;

/**
 * 自己封装一套日志写入.
 * Class Wlog
 * @package app\common\tool
 */
class Wlog extends Controller
{
    /**
     * @param $dir 要写入的目录
     * @param $msg  要写入的内容
     */
    public static function write($dir,$msg = '')
    {
        //生成dir的完整路径
        $dirPath = App::getRuntimePath()."/{$dir}/";
        //获取今天的日期
        $todayTime = date('Ym');
        //获取二级目录的完整路径。
        $twodirPath =$dirPath.'/'.$todayTime.'/';
        //检测目录是否存在,不存在就创建。
        if(!is_dir($dirPath)){
            mkdir($dirPath);
        }
        if(!is_dir($twodirPath)){
            mkdir($twodirPath);
        }
        $filename = date('d').'.log';
        $writeFilePath = $twodirPath.'/'.$filename;
        file_put_contents($writeFilePath,print_r($msg.'->>>>>>>>>'.date('Y-m-d H:i:s').PHP_EOL,true),FILE_APPEND);
    }
}