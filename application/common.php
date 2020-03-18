<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/*
 * 32位 唯一字符串
 */
function  random_string($raw_output=FALSE){
    if(function_exists("session_create_id")){
        $str= session_create_id();
    }
    else{
        $str =uniqid(microtime(true),true);
    }
    return $raw_output?md5($str,TRUE):md5($str);
}
//随机生成32位唯一字符串
function rand_32_string()
{
    $str =uniqid(microtime(true),true);
    return $str;
}

/**
 * 成功返回
 * @param string $msg
 * @param array $data
 * @param string $url
 * @return \think\Response
 */
function success_result($msg='',$data=[],$url=''){
    $result = [
        'msg' => $msg,
        'status' => '000',
        'data' => $data,
        'url' => $url,
    ];
    return response($result,200,[],'json');
}

/**
 * 失败返回
 * @param string $msg
 * @param array $data
 * @param string $url
 * @return \think\Response
 */
function error_result($status='',$msg='',$data=[],$url=''){
    if (is_array($status)) {
        $msg = isset($status['message']) ? $status['message'] : null;
        $status = isset($status['code']) ? $status['code'] : null;
    }
    $result = [
        'msg' => $msg,
        'status' => $status?$status:'100',
        'data' => $data,
        'url' => $url,
    ];
    return response($result,200,[],'json');
}

/**
 * 判断是否是post | ajax
 */
function is_ajax_post()
{
    return request()->isAjax() || request()->isPost();
}