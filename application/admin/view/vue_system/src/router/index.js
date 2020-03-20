import Vue from 'vue';
import Router from 'vue-router';

Vue.use(Router);

var router = new Router({
    routes: [
        {
            path: '/',
            redirect: '/login'
        },
        {
            path: '/index',
            component: resolve => require(['../components/common/Home.vue'], resolve),
            meta: { requireAuth: true },
            children:[
                {
                    path: '/',
                    component: resolve => require(['../components/wechat/Index.vue'], resolve)
                },
                {
                    path: '/basetable',
                    component: resolve => require(['../components/page/BaseTable.vue'], resolve)
                },
                {
                    path: '/vuetable',
                    component: resolve => require(['../components/page/VueTable.vue'], resolve)     // vue-datasource组件
                },
                {
                    path: '/baseform',
                    component: resolve => require(['../components/page/BaseForm.vue'], resolve)
                },
                {
                    path: '/vueeditor',
                    component: resolve => require(['../components/page/VueEditor.vue'], resolve)    // Vue-Quill-Editor组件
                },
                {
                    path: '/markdown',
                    component: resolve => require(['../components/page/Markdown.vue'], resolve)     // Vue-Quill-Editor组件
                },
                {
                    path: '/upload',
                    component: resolve => require(['../components/page/Upload.vue'], resolve)       // Vue-Core-Image-Upload组件
                },
                {
                    path: '/basecharts',
                    component: resolve => require(['../components/page/BaseCharts.vue'], resolve)   // vue-schart组件
                },
                {
                    path: '/drag',
                    component: resolve => require(['../components/page/DragList.vue'], resolve)    // 拖拽列表组件
                },
                {
                    path: '/empower',
                    component: resolve => require(['../components/wechat/Empower.vue'], resolve)    // 公众号列表页
                },
                {
                    path: '/admin_user_list',
                    component: resolve => require(['../components/system/Admin_user_list.vue'], resolve)    // 操作员管理页
                },
                {
                    path: '/admin_user_edit',
                    component: resolve => require(['../components/system/Admin_user_edit.vue'], resolve)    // 操作员管理页
                },
                {
                    path: '/auto_return_msg',
                    component: resolve => require(['../components/wechat/Auto_return_msg.vue'], resolve)    // 自动回复列表
                },
                {
                    path: '/send_all_msg',
                    component: resolve => require(['../components/wechat/msg/all/Send_all_msg.vue'], resolve)    // 群发所有消息
                },
                {
                    path:'/send_all_msg_edit/:id/:type',
                    component: resolve => require(['../components/wechat/msg/all/Send_all_msg_edit.vue'], resolve)    // 群发消息更改
                },
                {
                    path: '/send_all_msg_list',
                    component: resolve => require(['../components/wechat/msg/all/Send_all_msg_list.vue'], resolve)    // 群发所有消息列表
                },
                {
                    path: '/material_list',
                    component: resolve => require(['../components/wechat/Material_list.vue'], resolve)    // 素材列表
                },
                {
                    path:'/send_service_msg',
                    component: resolve => require(['../components/wechat/msg/service/Send_service_msg.vue'], resolve)    // 客服消息内容
                },
                {
                    path:'/send_service_msg_edit/:id/:type',
                    component: resolve => require(['../components/wechat/msg/service/Send_service_msg_edit.vue'], resolve)    // 客服消息内容更改
                },
                {
                    path: '/send_service_msg_list',
                    component: resolve => require(['../components/wechat/msg/service/Send_service_msg_list.vue'], resolve)    // 客服消息列表
                },
                {
                    path: '/send_template_msg_list',
                    component: resolve => require(['../components/wechat/msg/template/Send_template_msg_list.vue'], resolve)    // 模板消息列表
                },
                {
                    path: '/send_template_msg',
                    component: resolve => require(['../components/wechat/msg/template/Send_template_msg.vue'], resolve)    // 模板消息列表
                },
                {
                    path:'/send_template_msg_edit/:id/:type',
                    component: resolve => require(['../components/wechat/msg/template/Send_template_msg_edit.vue'], resolve)    // 模板消息更改
                },
                {
                    path: '/custom_menu',
                    component: resolve => require(['../components/wechat/Custom_menu.vue'], resolve)    // 自定义菜单
                },
                {
                    path: '/wechat_menu_list',
                    component: resolve => require(['../components/wechat/wechat_menu_list.vue'], resolve)    // 自定义菜单1
                },
                {
                    path: '/fans_list',
                    component: resolve => require(['../components/wechat/Fans_list.vue'], resolve)    // 粉丝列表
                }
            ]
        },
        {
            path: '/login',
            component: resolve => require(['../components/page/Login.vue'], resolve)
        },
    ]
})
/**
 * to:目标路由对象
 * from:当前正要离开的路由
 * next:接收访问路由
 */
router.beforeEach((to, from, next) => {
    var user = localStorage.getItem('user');
    if (to.matched.some(res => res.meta.requireAuth)) {  // 判断该路由是否需要登录权限
        if (user === "000") {  // 通过vuex state获取当前的token是否存在
            next();
        }
        else {
            next({
                path: '/login',
                query: {redirect: to.fullPath}  // 将跳转的路由path作为参数，登录成功后跳转到该路由
            })
        }
    }
    else {
        if(to.path == '/login' && user === "000"){
            next({
                path:'/index',
            });
        }
        next();
    }
})
export default router;