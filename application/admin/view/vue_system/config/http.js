// 引入axios以及element ui中的loading和message组件
import axios from 'axios'
import qs from 'qs'
import { Loading, Message } from 'element-ui'
// 超时时间
axios.defaults.timeout = 5000
// http请求拦截器
var loadinginstace

axios.interceptors.request.use(
    config => {
        if(config.method === 'post'){
            config.data = qs.stringify(config.data);
        }
        // config.baseURL = 'http://localhost';
        config.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        // element ui Loading方法
        // loadinginstace = Loading.service({ fullscreen: true });
        return config;
    }, 
    error => {
        // loadinginstace.close();
        Message.error({
            message: '加载超时'
        });
        return Promise.reject(error);
    }
);

// http响应拦截器
axios.interceptors.response.use(data => {// 响应成功关闭loading
    // loadinginstace.close()
    if (data.data.status != '000') {
        // if (data.data.error.code == '100') {
        //     sessionStorage.setItem('user', '');
        // }
    }
    return data
}, error => {
    loadinginstace.close()
    Message.error({
        message: '加载失败'
    })
    return Promise.reject(error)
})


export default axios