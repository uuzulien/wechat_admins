<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('think', function () {
    return 'hello,ThinkPHP5!';
});

Route::get('hello/:name', 'index/hello');

Route::get('Show$','Show/To_show');

return [
    '[admin]' => [
        /*************系统管理部分start***************/
        'login' => 'admin/auth/login',//登录
        'logout' => 'admin/auth/login_out',//登出
        'get_menu' => 'admin/index/get_menu',//获取左侧导航
        'get_title_menu' => 'admin/index/get_title_menu',//获取头部导航
        'set_now_menu_group' => 'admin/index/set_now_menu_group',//设置当前头部导航
        'get_role_list' => 'admin/index/get_role_list',//获取角色组列表
        'set_use_wechat'=>'admin/index/set_use_wechat',//设置当前使用公众号
        'get_user_group_leader_list' => 'admin/index/get_user_group_leader_list',//获取小组长
        'user_group_add' => 'admin/index/user_group_add',//添加小组
        'group_add' => 'admin/index/group_add',//获取小组长
        'get_user_group_lilst' => 'admin/index/get_user_group_list',//获取用户组
        /*************end***************/


        /*********公共api**********/
        'get_gzh_functionlist' => 'admin/CommonController/getFieldGzhFunction',//获取公众号固定的功能
        'upload_file' => 'admin/CommonController/uploadFile',//上传文件(单个)
        'show_wechat_img' => 'admin/CommonController/showWechatImg',//显示微信不可引用的图片
        'get_dictionary' => 'admin/CommonController/getDictionary',//获取全部字典
        'check_html_a_tag' => 'admin/CommonController/checkATag',//检测a标签
        'appid_clear_quota' => 'admin/CommonController/appidClearQuota',//appid的调用评率清0一个月十次
        'search_group_sent_status_for_msg_id' => 'admin/CommonController/searchGroupSentStatus',//根据msg_id获取发送结果
        'get_msg_sent_result' => 'admin/CommonController/getMsgSentResult',//该功能是获取存储在redis中的 群发客服/模板消息的发送结果.
        'getinfo_for_idandtable' => 'admin/WechatHomeController/getInfoForIdAndTable',//根据id和表名获取信息
        /*********结束**********/

        /*********打开微信公众号管理首页统计.**********/
        'wechat_public_number_info' => 'admin/WechatHomeController/index',//统计
        /*********结束**********/

        /*********公众号相关**********/
        'get_auth_code' => 'admin/WechatPowerController/getAuthQrcodeUrl',//获取授权二维码的url地址. //wx的
        'get_auth_gzh_list'=>'admin/WechatPowerController/getAuthGzhList',//获取授权公众号的列表
        'get_wechat_user_info'=>'admin/WechatPowerController/getWechatUserInfo',//抓取当前公众号的粉丝
        'switch_wechat_group'=>'admin/WechatPowerController/switchWechatGroup',//切换公众号的组
        'get_nowser_wechat_publicnum_list' => 'admin/WechatPowerController/getNowUserWechatPublicNumList', //获取当前用户归属组的公众号列表
        'get_wechat_tag_list' => 'admin/WechatPowerController/getWechatTagList',//获取微信公众号的标签列表
        'create_wechat_tag' => 'admin/WechatPowerController/createWechatTag',//获取微信公众号的标签列表
        'wechat_platform_list' => 'admin/Index/wechat_platform',//获取公众号对应平台列表
        'wechat_platform_handle' => 'admin/Index/wechat_platform_handle',//微信公众号对应平台添加或调整
        'wechat_platform_status' => 'admin/Index/wechat_platform_status',//调整微信公众号对应平台的状态
        'wechat_platform_del' => 'admin/Index/wechat_platform_del',//调整微信公众号对应平台的状态
        'set_wechat_platform' => 'admin/Index/set_wechat_platform',//设置微信公众号的对应平台
        /*********结束**********/



        /*********操作员管理相关**********/
        'get_handle_people_list'=>'admin/HandlePeopleController/index',//获取操作员列表
        'add_handle_people'=>'admin/HandlePeopleController/add',//添加操作员
        'update_handle_people'=>'admin/HandlePeopleController/update',//修改操作员
        'delete_handle_people'=>'admin/HandlePeopleController/delete',//删除操作员
        'updateStatus_handle_people'=>'admin/HandlePeopleController/disableOrOpen',//修改操作员账号状态
        'get_info_forid'=>'admin/HandlePeopleController/getInfoForId',//根据id获取详细信息.
        /*********结束**********/

        /*********自动回复相关**********/
        'auto_reply_list'=>'admin/AutoReplyController/index',//自动回复的列表
        'auto_reply_add'=>'admin/AutoReplyController/add',//自动回复的添加
        'auto_reply_delete'=>'admin/AutoReplyController/delete',//自动回复条目的删除
        'auto_reply_update'=>'admin/AutoReplyController/update',//自动回复条目的修改
        'auto_reply_updateStatus'=>'admin/AutoReplyController/disableOrOpen',//修改自动回复id的状态
        /*********结束**********/

        /*********素材管理相关**********/
        'material_manage_list'=>'admin/MaterialManageController/index',//素材管理列表
        'material_manage_delete'=>'admin/MaterialManageController/delete',//素材管理删除
        'material_manage_synchronousUpdate'=>'admin/MaterialManageController/synchronousUpdate',//素材同步
        /*********结束**********/

        /*********群发相关**********/
        'group_sent_list'=>'admin/WechatGroupSentController/index',//群发列表接口
        'group_sent_add'=>'admin/WechatGroupSentController/groupSent',//群发新增
        'group_sent_update'=>'admin/WechatGroupSentController/edit',//群发新增
        'group_sent_delete'=>'admin/WechatGroupSentController/delete',//群发删除
        'group_sent_today_sent_info'=>'admin/WechatGroupSentController/getTodaySentInfo',//群发客服消息当天的发送信息
        /*********结束**********/

        /*********群发客服消息相关**********/
        'group_sent_service_list'=>'admin/WechatServiceMsgController/index',//群发客服列表接口
        'group_sent_service_sent'=>'admin/WechatServiceMsgController/sent',//群发客服信息新增接口
        'group_sent_service_delete'=>'admin/WechatServiceMsgController/delete',//群发客服信息删除
        'group_sent_service_edit'=>'admin/WechatServiceMsgController/edit',//群发客服信息修改
        'group_sent_service_today_sent_info'=>'admin/WechatServiceMsgController/getTodaySentInfo',//群发客服消息当天的发送信息
        /*********结束**********/

        /*********群发模板消息接口**********/
        'get_wechat_template_list' => 'admin/WechatTemplateMsgController/getWechatTemplateList',//获取微信模板标签
        'group_sent_template_list'=>'admin/WechatTemplateMsgController/index',//群发客服列表接口
        'group_sent_template_sent'=>'admin/WechatTemplateMsgController/sent',//群发客服信息新增接口
        'group_sent_template_delete'=>'admin/WechatTemplateMsgController/delete',//群发客服信息删除接口
        'group_sent_template_today_sent_info'=>'admin/WechatTemplateMsgController/getTodaySentInfo',//群发模板消息当天的发送信息

        /*********结束**********/

        /*********公众号自定义菜单相关**********/
        'wechat_menu_list'=>'admin/WechatMenuController/getmenu',//获取微信公众号再用的菜单
        'wechat_menu_add'=>'admin/WechatMenuController/add',//更新当前公众号的菜单
        'set_service_img_url'=>'admin/WechatMenuController/set_service_img_url',//更新当前公众号的菜单
        /*********结束**********/


        /*********公众号粉丝相关**********/
        'wechat_user_list'=>'admin/WechatFansController/index',//微信公众号粉丝列表
        /*********结束**********/



    ],
    'index' => 'index/index/index',

    'wechat_empower' => 'index/wechat/empower',//接收授权信息地址
    'wechat_notify' => 'index/wechat/notify',//事件推送地址
    'wechat_msgnotify/:appid' => 'index/wechat/msg_notify',//事件推送地址
];
