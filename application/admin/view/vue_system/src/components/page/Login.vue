<style>
    .card_border{
        opacity: 0.8;
        background: none !important;
        border-color: #97a8be;
        height: 200px !important;
    }
</style>
<template>
    <div class="row">
        <div class="sky">
            <!--特效组-->
            <div class="clouds_one"></div>
            <div class="clouds_two"></div>
            <div class="clouds_three"></div>
            <div class="san"></div>
            <!--特效组end-->
            <div class="ms-title">后台管理系统</div>
            <el-card class="ms-login card_border">
                <el-form :model="ruleForm" :rules="rules" ref="ruleForm" label-width="0px" class="demo-ruleForm">
                    <el-form-item prop="username">
                        <el-input v-model="ruleForm.username" placeholder="username"></el-input>
                    </el-form-item>
                    <el-form-item prop="password">
                        <el-input type="password" placeholder="password" v-model="ruleForm.password" @keyup.enter.native="submitForm('ruleForm')"></el-input>
                    </el-form-item>
                    <div class="login-btn">
                        <el-button type="primary" @click="submitForm('ruleForm')">登录</el-button>
                    </div>
                </el-form>
            </el-card>
        </div>
    </div>
</template>

<script>
    export default {
        data: function(){
            return {
                ruleForm: {
                    username: '',
                    password: '',
                },
                rules: {
                    username: [
                        { required: true, message: '请输入用户名', trigger: 'blur' }
                    ],
                    password: [
                        { required: true, message: '请输入密码', trigger: 'blur' }
                    ]
                },
                is_long_login:true
            }
        },
        created:function(){
            var lost_time =  parseInt(localStorage.getItem('lost_time'));
            if(lost_time && (lost_time + 1000*60*60*24*3) < Date.now()){
                localStorage.setItem('login_name',null);
                localStorage.setItem('login_password',null);
            }
            var name = this.ruleForm.username = localStorage.getItem('login_name');
            var pwd = this.ruleForm.password = localStorage.getItem('login_password');
            if(name && pwd){
                this.is_long_login = true;
            }
        },
        methods: {
            submitForm(formName) {
                const self = this;
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        self.$ajax.post(self.$interfase.login, self[formName]).then(function (response) {
                            if (response.data.status === '000') {
                                if(self.is_long_login){
                                    //永不过期保存用户登录账户密码
                                    localStorage.setItem('login_name',self[formName].username);
                                    localStorage.setItem('login_password',self[formName].password);
                                    localStorage.setItem('lost_time',Date.now());
                                }else{
                                    localStorage.removeItem('login_name');
                                    localStorage.removeItem('login_password');
                                }
                                //登录状态
                                localStorage.setItem('user',response.data.status);
                                //用户名
                                localStorage.setItem('username',self.ruleForm.username);
                                //身份id/上级id
                                localStorage.setItem('creater_id',response.data.data.creater_id);
                                //当前使用的微信名称
                                localStorage.setItem('use_wechat_name',response.data.data.nick_name);
                                self.$refs[formName].resetFields();
                                self.$router.push('/index');
                            }else{
                                self.$message.error(response.data.msg);
                            }
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            }
        }
    }
</script>

<style scoped>
    .login-wrap{
        position: relative;
        width:100%;
        height:100%;
    }
    .ms-title{
        position: absolute;
        top:50%;
        width:100%;
        margin-top: -230px;
        text-align: center;
        font-size:30px;
        color: #fff;

    }
    .ms-login{
        position: absolute;
        left:50%;
        top:50%;
        width:300px;
        height:160px;
        margin:-150px 0 0 -190px;
        padding:40px;
        border-radius: 5px;
        background: #fff;
    }
    .login-btn{
        text-align: center;
    }
    .login-btn button{
        width:100%;
        height:36px;
    }
</style>