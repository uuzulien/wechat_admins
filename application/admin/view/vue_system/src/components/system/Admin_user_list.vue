<style>
    .el-row {
        margin-bottom: 20px;
    }
    .demo-table-expand .el-form-item {
        margin-right: 0;
        margin-bottom: 0;
        width: 30%;
    }
</style>
<template>
    <div class="row">
        <el-tabs type="border-card">
            <el-row>
                <el-col :span="2" :offset="20"><el-button type="primary" @click="user_info('add')">添加操作员</el-button></el-col>
                <el-col :span="2" v-if="creater_id == 0"><el-button type="primary" @click="dialog_use('groupFormDialog')">指定组长</el-button></el-col>
            </el-row>
            <el-tab-pane label="操作员列表">
                <el-table :data="tableData" height="" :span-method="objectSpanMethod" border style="width: 100%" max-height="900">
                    <el-table-column type="expand">
                        <template scope="props">
                            <el-form label-position="left" inline class="demo-table-expand">
                                <el-form-item label="商品名称">
                                    <span>{{ props.row.name }}</span>
                                </el-form-item>
                            </el-form>
                        </template>
                    </el-table-column>
                    <el-table-column label="用户信息" width="240">
                        <template slot-scope="scope">
                            <p>账号：{{ scope.row.username }}</p>
                            <p>姓名：{{ scope.row.name }}</p>
                            <p v-if="scope.row.status == 1">状态：启用</p>
                            <p v-if="scope.row.status == 0">状态：弃用</p>
                        </template>
                    </el-table-column>
                    <el-table-column prop="last_use_datetime" label="最后登录时间" width="180"></el-table-column>
                    <el-table-column prop="power_group" label="模块权限" width="200">
                        <template slot-scope="scope">
                            <div v-if="scope.row.power_group === 0">
                                所有
                            </div>
                            <div v-else>
                                <p v-for="(item,index) in scope.row.power_group">
                                    {{ item.nick_name }}
                                </p>
                            </div>
                        </template>
                    </el-table-column>
                    <el-table-column prop="user_group" label="公众号权限">
                        <template slot-scope="scope">
                            <p v-for="(item,index) in scope.row.user_group">
                                {{ item.nick_name }}
                            </p>
                        </template>
                    </el-table-column>
                    <el-table-column fixed="right" label="操作" width="230">
                        <template slot-scope="scope">
                            <el-button @click="user_info(scope.row)" type="primary" round size="small">编辑</el-button>
                            <el-button @click="set_user_status(scope.row.id)" type="primary" v-if="scope.row.status == 1" size="small">
                                启用中
                            </el-button>
                            <el-button @click="set_user_status(scope.row.id)" type="danger" v-if="scope.row.status == 0" size="small">
                                已弃用
                            </el-button>
                            <el-button @click="user_del(scope.row.id)" type="danger" round size="small">删除</el-button>
                            <!--<el-button v-if="creater_id == 1" @click="reset_wechat_group(scope.row.id)" type="danger" round size="small">公众号小组转让</el-button>-->
                        </template>
                    </el-table-column>
                </el-table>
            </el-tab-pane>
        </el-tabs>

        <!--调整信息-->
        <el-dialog :visible.sync="infoFormDialog" title="操作员信息">
            <el-form label-width="140px" :model="bindinfo" ref="bindinfo" inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item prop="user_name" label="用户名">
                            <el-input v-model="bindinfo.username" placeholder="用户名" @keyup.enter.native="set_user_info('bindinfo')"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item prop="name" label="姓名">
                            <el-input v-model="bindinfo.name" placeholder="姓名" @keyup.enter.native="set_user_info('bindinfo')"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item prop="password" label="密码">
                            <el-input v-model="bindinfo.password" placeholder="密码" @keyup.enter.native="set_user_info('bindinfo')"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="分配公众号">
                            <el-checkbox-group v-model="bindinfo.wechats">
                                <el-checkbox v-for="item in wechat_list" :label="item.id" :key="item.id" >{{item.nick_name}}</el-checkbox>
                            </el-checkbox-group>
                        </el-form-item>
                    </el-col>

                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="set_user_info('bindinfo')">更新信息</el-button>
            </div>
        </el-dialog>

        <!--权限小组-->
        <el-dialog :visible.sync="groupFormDialog" title="小组添加">
            <el-form label-width="140px" :model="groupinfo" ref="groupinfo" inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item label="小组名">
                            <el-input v-model="groupinfo.group_name" placeholder="请输入小组名" @keyup.enter.native="submitForm('groupinfo')"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="小组所属">
                            <el-select v-model="groupinfo.leader_id" placeholder="请选择">
                                <el-option v-for="item in group_leader_list" :key="item.id" :label="item.name" :value="item.id"> </el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="add_group('groupinfo')">添加小组</el-button>
            </div>
        </el-dialog>

    </div>
</template>
<script>
    export default {
        data:function() {
            return {
                keyword:'',
                infoFormDialog:false,
                groupFormDialog:false,
                //操作员表单
                bindinfo:{
                    'wechats' : []
                },
                //小组表单
                groupinfo:{},
                //组长人选
                group_leader_list:[],
                //组长列表
                group_list:[],
                //列表数据
                tableData: [],
                creater_id:-1,
                wechat_list:[],
            };
        },
        created: function () {
            this.reload();
        },
        methods: {
            objectSpanMethod({ row, column, rowIndex, columnIndex }) {
                if (columnIndex == 2) {
                    return {
                        rowspan: 2,
                        colspan: 1
                    };
                }
            },
            //获取用户列表
            get_list(){
                let self = this;
                self.$ajax.post(self.$interfase.user_list).then(function (response){
                    if(response.data.status == '000'){
                        self.tableData = response.data.data.list;
                    }else{
                        self.$message.error({
                            message: '查询失败,可能是权限不足',
                        });
                    }
                });
            },
            //开启用户信息界面
            user_info(info){
                let self = this;
                self.infoFormDialog = true;
                console.log(info);
                if(info != 'add'){
                    self.bindinfo = info;
                    self.bindinfo = {
                        'id' : info.id,
                        'username' : info.username,
                        'name' : info.name,
                        'password' : info.password,
                        'wechats' : [],
                    }
                }else{
                    self.bindinfo = {
                        'wechats':[],
                    };
                }
                self.$ajax.post(self.$interfase.get_user_group_wehcats,{'limit':500}).then(function (response) {
                    if (response.data.status == '000') {
                        self.wechat_list = response.data.data.list;
                    }else{
                        self.$message.error({
                            message:'操作失败:'+response.data.msg,
                        });
                    }
                });
            },
            //检查是否勾选
            checked(item,id){
                //存在id，判定当前是否可用
                if(id){
                    return item.use_user_id == id;
                }
            },
            //检查是否禁用
            disabled(item,id){
                //存在id，判定当前是否可用
                if(item.use_user_id != 0){
                    return !(item.use_user_id == id);
                }
            },
            //设置账号信息
            set_user_info(formName){
                let self = this;
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        let info = {
                            'name' : self[formName].name,
                            'username' : self[formName].username,
                            'password' : self[formName].password,
                            'wechats' : self[formName].wechats,
                        }
                        let url = self.$interfase.user_add;
                        //存在着id，为更新数据
                        if(self[formName].id){
                            info.id = self[formName].id;
                            url = self.$interfase.user_update;
                        }
                        self.$ajax.post(url,info).then(function (response) {
                            if (response.data.status == '000') {
                                self.$message({
                                    message:response.data.msg,
                                    type:'success'
                                });
                                self.$refs[formName].resetFields();
                                self.infoFormDialog = false;
                                self.reload();
                            }else{
                                self.$message.error({
                                    message:'操作失败',
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
            //删除账号信息
            user_del(id){
                let self = this;
                let info = {'id':id};
                self.$ajax.post(self.$interfase.user_del,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'删除成功',
                            type:'success'
                        });
                        self.reload();
                    }else{
                        self.$message.error({
                            message:'删除失败',
                        });
                    }
                });
            },
            //启用弃用账号
            set_user_status(id){
                let self = this;
                let info = {'id':id};
                self.$ajax.post(self.$interfase.set_user_status,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'设置成功',
                            type:'success'
                        });
                        self.reload();
                    }else{
                        self.$message.error({
                            message:'设置失败',
                        });
                    }
                });
            },
            add_use(){
                this.$router.push('/admin_user_edit');
            },
            //展示小组
            dialog_use(dialog_name){
                let self = this;
                self[dialog_name] = true;
                self.$ajax.get(self.$interfase.get_user_group_leader_list).then(function (response){
                    if(response.data.status == '000'){
                        self.group_leader_list = response.data.data;
                    }else{
                        self.$message.error({
                            message:'设置失败',
                        });
                    }
                });
            },
            //添加小组
            add_group(formName){
                let self = this;
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        self.$ajax.post(self.$interfase.group_add,self[formName]).then(function (response) {
                            if (response.data.status == '000') {
                                self.$message({
                                    message:'操作成功',
                                    type:'success'
                                });
                                self.reload();
                            }else{
                                self.$message.error({
                                    message:'操作失败,'+response.data.msg,
                                });
                            }
                            self.$refs[formName].resetFields();
                            self.groupFormDialog = false;
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            //获取用户组
            get_user_group_list(){
                let self = this;
                self.$ajax.post(self.$interfase.get_user_group_list,self[formName]).then(function (response) {
                    if (response.data.status == '000') {
                        self.group_list = response.data.data;
                    }
                });
            },
            reload(){
                //获取列表
                this.get_list();
                //获取当前身份信息
                this.creater_id = localStorage.getItem('creater_id');
            }
        }
    }
</script>
