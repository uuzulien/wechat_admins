import Vue from 'vue';
import App from './App';
import router from './router';
import axios from 'axios';
import ElementUI from 'element-ui';
import 'element-ui/lib/theme-default/index.css';    // 默认主题
// import '../static/css/theme-green/index.css';       // 浅绿色主题
import "babel-polyfill";


import '../config/http.js';     //访问控制
import '../config/interfase.js';        //接口配置
axios.defaults.baseURL = process.env.API_BASH_PATH;

axios.defaults.withCredentials = true;

axios.defaults.timeout =  30000; //30秒

Vue.use(ElementUI);
Vue.prototype.$ajax = axios;
sessionStorage.setItem('left_menu',0);
new Vue({
    router,
    render: h => h(App)
}).$mount('#app');