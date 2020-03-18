<?php
return [
    /****************adminUsers表*********************/
    'adminUsers_status' => [
        0 => '禁用',
        1 => '启用',
    ],
    /****************adminUsers表结束*********************/




    /****************autoReplyInfo表*********************/
    'autoReplyInfo_reply_type' => [
        1=>[
            'type' => 'text',
            'name' => '文字'
        ],
        2=>[
            'type' => 'img',
            'name' => '图片'
        ],
        3=>[
            'type' => 'voice',
            'name' => '音频'
        ],
        4=>[
            'type' => 'news',
            'name' => '图文'
        ],

    ],
    'autoReplyInfo_type' => [
        1 => '关键字回复',
        2 => '关注自动回复',
        3 => '统一回复',
    ],
    /****************autoReplyInfo表结束*********************/



    /****************groupSentInfo表*********************/
    'groupSentInfo_group_sent_type' => [
        1 => '主动群发',
    ],
    'groupSentInfo_group_sent_tag_type' => [
        1 => '公众号官方标签',
    ],
    //本地的发送结果0:未发送 1:发送中 2:发送成功 3:发送失败具体的成功与否 要根据 sent_status字段来判断 因为这个字段是公众号那边返回的
    'groupSentInfo_bendi_sent_status' => [
        0 => '未发送',
        1 => '发送中',
        2 => '发送成功',
        3 => '发送失败',
    ],
    /****************groupSentInfo表结束*********************/


    /****************serviceMessageList表*********************/
    'serviceMessageList_task_type' => [
        1 => '站点链接',
        2 => '外部链接'
    ],
    'serviceMessageList_sent_status' => [
        0 => '未发送',
        1 => '发送中',
        2 => '发送成功',
        3 => '发送失败',
    ],
    'serviceMessageList_site_type' => [
        1 => '掌读',
        2 => '掌中云',
        3 => '网易',
        4 => '火烧云',
        5 => '阳关',
        6 => '滕文',
        7 => '掌文',
        8 => '追书云',
        9 => '文鼎',
        10 => '阅文',
    ],
    /****************serviceMessageList表结束*********************/


    /****************templateMessageInfo表*********************/
    'templateMessageInfo_group_tag_type' => [
        1 => '公众号官方标签',
    ],
    'templateMessageInfo_sent_status' => [
        0 => '未发送',
        1 => '发送中',
        2 => '发送成功',
        3 => '发送失败',
    ],
    /****************templateMessageInfo表结束*********************/

    /****************wechatEmpowerInfo表*********************/
    'wechatEmpowerInfo_is_get_user' => [
        1 => '同步',
        2 => '未同步',
    ]
    /****************wechatEmpowerInfo表结束*********************/
];