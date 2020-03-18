<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    // 定时任务： 获取用户的openid. 建议20分钟一次
    'getUserOpenid'	=>	'app\command\GetUserOpenid',
    // 插入用户。 建议40分钟一次.
    'insertUserInfo'    =>  'app\command\InsertUserInfo',
    // 定时任务:  检测用户的数量是否对的上
    'checkUserCount'	=>	'app\command\CheckUserCount',

    // 定时任务:  定时群发消息
    'groupSent'	=>	'app\command\GroupSent',
    //定时任务： 发送群发模板消息
    'templateMsgSent'	=>	'app\command\TemplateMsgSent',
    // 定时任务:  发送群发客服消息 ---2020年01月10日10:42:17 之后已弃用.
    'serviceMsgSent'	=>	'app\command\ServiceMsgSent',






    //获取活跃粉丝并且入库活跃时间
    'getActiveFansAndSave'	=>	'app\command\GetActiveFansAndSave',
    //更新群发客服和群发客服发送成功的人数.
    'serAndTemplateSentnumUpdate'    =>  'app\command\SerAndTemplateSentnumUpdate',

    /************有用到队列的**************/
    //定时任务:获取群发客服消息, 每次获取一条. 尽量十秒钟执行一次 队列名字：ServiceMsgJob
    'getServiceMsg'    =>  'app\command\GetServiceMsg',
    'serviceMsgJob'    =>  'app\command\ServiceMsgJob',

    'getTemplateMsg'	=> 'app\command\GetTemplateMsg',
    'templateMsgJob'	=> 'app\command\TemplateMsgJob',
    /************结束**************/

];
