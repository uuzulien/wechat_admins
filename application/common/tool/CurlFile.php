<?php
namespace app\common\tool;

use think\Controller;

/**
 * curl传输文件
 * Class CurlFile
 * @package app\common\tool
 */
class CurlFile extends Controller
{
    public static function curlFile($url,$fileurl)
    {
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_SAFE_UPLOAD,true);
        $data = ['media'=> new \CURLFile($fileurl)];
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_USERAGENT,"LENGGE");
        $result = curl_exec($curl);
        return json_decode($result,true);

    }
}