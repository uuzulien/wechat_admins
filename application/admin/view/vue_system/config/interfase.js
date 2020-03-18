import Vue from 'vue';

Vue.prototype.$interfase = {
    //回调凭据及发起
    'ewpower' : '/admin/index/index.html',//授权
    'get_auth_code' : '/admin/get_auth_code',//获取授权二维码

    //登录
    'login' : '/admin/login',//后台登录
    'logout' : '/admin/logout',//后台登出

    //公用
    'syn_wechat' : '/admin/material_manage_synchronousUpdate',//同步微信素材
    'titleMenuList' : '/admin/get_title_menu',//头部导航
    'menuList' : '/admin/get_menu',//侧栏导航
    'setMenuGroup' : '/admin/set_now_menu_group',//设置当前头部导航，以更新侧栏导航
    'checkhtmlaTag' : '/admin/check_html_a_tag',//检测html的a标签
    'getMsgSentResult' : '/admin/get_msg_sent_result',//该功能是获取存储在redis中的 群发客服/模板消息的发送结果.

    //system
    'user_list' : '/admin/get_handle_people_list',//操作员列表
    'user_del' : '/admin/delete_handle_people',//操作员删除
    'user_update' : '/admin/update_handle_people',//操作员编辑
    'user_add' : '/admin/add_handle_people',//操作员添加
    'set_user_status' : '/admin/updateStatus_handle_people',//操作员启用弃用
    'group_add' : '/admin/user_group_add',//小组添加
    'get_user_group_list' : '/admin/get_user_group_list',//获取小组
    'get_user_group_leader_list' : '/admin/get_user_group_leader_list',//获取小组长
    'get_user_group_lilst' : '/admin/get_user_group_lilst',//获取小组长

    //微信
    'get_yestoday_info':'/admin/wechat_public_number_info',//获取公众号首页统计
    'get_empower_wechat_list' : '/admin/get_auth_gzh_list',//获取公众号列表
    'set_use_wechat' : '/admin/set_use_wechat',//设置当前使用公众号
    'auto_reply_list' : '/admin/auto_reply_list',//自动回复列表
    'auto_reply_del' : '/admin/auto_reply_delete',//自动回复数据删除
    'set_auto_reply_status' : '/admin/auto_reply_updateStatus',//设置自动回复状态
    'auto_reply_add' : '/admin/auto_reply_add',//自动回复添加
    'get_material_list' : '/admin/material_manage_list',//获取素材列表
    'get_user_group_wehcats' : '/admin/get_auth_gzh_list',//获取授权公众号列表
    'get_wechat_user_tag_list' : '/admin/get_wechat_tag_list',//获取微信公众号标签列表
    'add_send_wechat_all_msg' : '/admin/group_sent_add',//群发新增
    'send_all_msg_list' : '/admin/group_sent_list',//群发列表
    'send_all_msg_update' : '/admin/group_sent_update',//群发列表
    'show_wechat_img' : '/admin/show_wechat_img',//展示不可使用的微信图片
    'del_material' : '/admin/material_manage_delete',//删除素材
    'get_service_msg_list' : '/admin/group_sent_service_list',//获取群发客服列表
    'del_service_msg' : '/admin/group_sent_service_delete',//删除群发客服信息
    'del_all_msg' : '/admin/group_sent_delete',//删除群发信息
    'set_service_msg' : '/admin/group_sent_service_sent',//设置群发客服消息
    'edit_service_msg' : '/admin/group_sent_service_edit',//调整群发客服消息
    'group_sent_service_today_sent_info' : '/admin/group_sent_service_today_sent_info',//当天群发客服消息的情况下查询
    'group_sent_template_today_sent_info' : '/admin/group_sent_template_today_sent_info',//当天群发模板消息的情况下查询
    'group_sent_today_sent_info' : '/admin/group_sent_today_sent_info',//当天群发消息的情况下查询
    'get_template_msg_list' : '/admin/group_sent_template_list',//获取群发模板消息列表
    'get_template' : '/admin/get_wechat_template_list',//获取群发模板
    'add_template_msg' : '/admin/group_sent_template_sent',//添加群发模板消息
    'del_template_msg' : '/admin/group_sent_template_delete',//添加群发模板消息
    'get_fans_list' : '/admin/wechat_user_list',//获取粉丝列表
    'switch_wechat_group' : '/admin/switch_wechat_group',//公众号换组
    'get_wechat_fans_new_list' : '/admin/switch_wechat_group',//获取微信新的粉丝（重新抓取）
    'get_msg_info' : '/admin/getinfo_for_idandtable',//为获取发送消息的内容
    'set_service_img_url' : '/admin/set_service_img_url',//为获取发送消息的内容
    'wechat_platform_list' : '/admin/wechat_platform_list',//获取平台列表
    'wechat_platform_handle' : '/admin/wechat_platform_handle',//修改、添加平台信息
    'wechat_platform_del' : '/admin/wechat_platform_del',//删除站点平台信息
    'set_wechat_platform' : '/admin/set_wechat_platform',//设置微信平台
}

Vue.prototype.$interfase_api = {
    'ewpower' : '/admin/system/menuadd.html',
}

Vue.prototype.$paListSize = 20;
