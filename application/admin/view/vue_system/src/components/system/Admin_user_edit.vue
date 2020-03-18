<template>
    <div class="row">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/admin_user_list' }">操作员列表</el-breadcrumb-item>
            <el-breadcrumb-item>新增操作员</el-breadcrumb-item>
        </el-breadcrumb>
        <el-row>&nbsp</el-row>
        <el-form ref="form_data" :model="form_data" label-width="120px">
            <el-form-item label="账号">
                <el-input v-model="form_data.username" placeholder="请输入账号"></el-input>
            </el-form-item>
            <el-form-item label="姓名">
                <el-input v-model="form_data.name" placeholder="请输入姓名"></el-input>
            </el-form-item>
            <el-form-item label="密码">
                <el-input type="password" v-model="form_data.password" placeholder="请输入密码"></el-input>
            </el-form-item>
            <el-form-item label="公众号权限" v-if="creater_id == 1 || creater_id == 0">
                <el-checkbox-group v-model="form_data.wechats">
                    <el-checkbox  v-for="item in wechat_list" :label="item.id"   :key="item.id" >{{item.nick_name}}</el-checkbox>
                </el-checkbox-group>
<!--                <el-checkbox-group v-model="form_data.wechats" size="small">-->
<!--                    <el-checkbox-button v-for="item in wechat_list" :label="item.nick_name" :key="item.id"></el-checkbox-button>-->
<!--                </el-checkbox-group>-->
            </el-form-item>
        </el-form>
        <el-row>&nbsp</el-row>
        <div slot="footer" class="dialog-footer">
            <el-button type="primary" @click="user_add('form_data')">添加</el-button>
        </div>
    </div>
</template>
<script>
    export default {
        data: function () {
            return {
                form_data:{
                    'wechats' : []
                },
                creater_id:-1,
                wechat_list:[]
            }
        },
        created:function(){
            this.creater_id = localStorage.getItem('creater_id');
            this.get_wechat_list();
        },
        methods:{
            user_add(formName){
                let self = this;
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        self.$ajax.post(self.$interfase.user_add,self[formName]).then(function (response) {
                            if (response.data.status == '000') {
                                self.$message({
                                    message:'操作成功',
                                    type:'success'
                                });
                                self.$refs[formName].resetFields();
                                this.$router.push('/admin_user_list');
                            }else{
                                self.$message.error({
                                    message:'操作失败:'+response.data.msg,
                                });
                                self.$refs[formName].resetFields();
                            }
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            get_wechat_list(){
                let self = this;
                self.$ajax.post(self.$interfase.get_user_group_wehcats).then(function (response) {
                    if (response.data.status == '000') {
                        self.wechat_list = response.data.data.list;
                        self.$message({
                            message:'操作成功',
                            type:'success'
                        });
                }else{
                        self.$message.error({
                            message:'操作失败:'+response.data.msg,
                        });
                        self.$refs[formName].resetFields();
                    }
                });
            }
        }
    }
</script>