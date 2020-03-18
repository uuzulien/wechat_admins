<style>
    .el-row {
        margin-bottom: 20px;
    }
</style>

<template>
    <div class="row">
        <el-tabs type="border-card">
            <el-tab-pane label="公众号列表">
                <el-row>
                    <el-col :span="4">
                        <el-input type="text" placeholder="请输入公众号名称" @keyup.enter.native="get_list(keyword)" v-model="keyword" :rows="12" show-word-limit class="input-with-select">
                            <el-button slot="append" icon="search" @click="get_list(keyword)"></el-button>
                        </el-input>
                    </el-col>
                    <el-col :span="12" :push=12>
                        <el-button type="primary" round @click="getAuthQrcodeUrl()">添加公众号</el-button>
                        <el-button type="primary" round @click="wechat_platform('platformlist','platformList')">添加公众号对应平台</el-button>
                        <el-button type="primary" round @click="wechat_platform('platform_list_Dialog')">划分公众号对应平台</el-button>
                        <el-button type="primary" round @click="wechat_platform('service_link_update_Dialog')">客服链接批量修改</el-button>
                    </el-col>
                </el-row>
                <el-table :data="tableData" height="" :span-method="objectSpanMethod" border style="width: 100%" max-height="900">
                    <el-table-column prop="id" label="id" width="100"></el-table-column>
                    <el-table-column prop="nick_name" label="公众号名" width="100"></el-table-column>
                    <el-table-column label="账号logo" width="180">
                        <template slot-scope="scope">
                            <img :width="120" :height="120" :src="scope.row.head_img"/>
                        </template>
                    </el-table-column>
                    <el-table-column prop="service_type_info" label="类型" width="180"></el-table-column>
                    <el-table-column label="二维码" width="200">
                        <template slot-scope="scope">
                            <img :width="120" :height="120" :src="get_img(scope.row.qrcode_url)"/>
                        </template>
                    </el-table-column>
                    <el-table-column prop="active_fans_count" label="活跃粉丝数量"></el-table-column>
                    <el-table-column fixed="right" label="操作" width="300">
                        <template slot-scope="scope">
                            <el-button type="primary" @click="set_use_wechat(scope.row.id,scope.row.nick_name)" size="small">使用</el-button>
                            <el-button v-if="creater_id == 0 || creater_id == 1" @click="get_user_group_lilst(scope.row)" type="primary" size="small">转出</el-button>
                            <el-button v-if="creater_id == 0 || creater_id == 1" @click="get_wechat_fans_new_list(scope.row)" type="primary" size="small">粉丝更新</el-button>
                        </template>
                    </el-table-column>
                </el-table>
                <el-col :span="6" :offset="18">
                    <el-pagination @current-change="pageset" :total="page.total" :page-size="page.ep" :current-page="page.p" background layout="prev,pager,next,jumper"></el-pagination>
                </el-col>
            </el-tab-pane>
        </el-tabs>

        <!--权限小组-->
        <el-dialog :visible.sync="groupFormDialog" title="转让公众号">
            <el-form label-width="140px" :model="groupinfo" ref="groupinfo" inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item label="公众号名称" prop="name">
                            <el-tag type="success">{{ groupinfo.name }}</el-tag>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="小组所属">
                            <el-select v-model="groupinfo.user_group" placeholder="请选择">
                                <el-option v-for="item in user_group_list" :key="item.id" :label="item.group_name" :value="item.id"> </el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="switch_wechat_group(groupinfo,'转让后，<'+groupinfo.name+'>该公众号下所有数据将不再归属当前使用者，请问是否执行？')">转出</el-button>
            </div>
        </el-dialog>

        <!--公众号对应平台相关-->
        <el-dialog :visible.sync="platformlist" title="公众号对应平台列表">
            <el-tabs type="border-card">
                <el-tab-pane label="公众号对应平台">
                    <el-row>
                        <el-col :span="12" :push=12>
                            <el-button type="primary" round @click="wechat_platform('platformDialog')">添加公众号对应平台</el-button>
                        </el-col>
                    </el-row>
                    <el-table :data="platformtableData" height="" :span-method="objectSpanMethod" border style="width: 100%" max-height="900">
<!--                        <el-table-column prop="id" label="id"></el-table-column>-->
                        <el-table-column prop="name" label="平台名"></el-table-column>
                        <el-table-column prop="url" label="平台url"></el-table-column>
                        <el-table-column fixed="right" label="操作">
                            <template slot-scope="scope">
                                <el-button type="primary" @click="wechat_platform('platformDialogedit','platformDialogEdit',{'name':scope.row.name,'url':scope.row.url,'id':scope.row.id})" size="small">修改</el-button>
                                <el-button type="danger" @click="del_wechat_platform({'id':scope.row.id,'name':scope.row.name})" size="small">删除</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-tab-pane>
            </el-tabs>
        </el-dialog>
        <el-dialog :visible.sync="platformDialogedit" title="公众号对应平台修改">
            <el-form label-width="140px" :model="platform" ref="platform">
                <el-row>
                    <el-col :span="10">
                        <el-form-item label="平台名字" prop="name">
                            <el-input v-model="platform.name"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-input type="hidden" v-model="platform.id"></el-input>
                    <el-col :span="24">
                        <el-form-item label="检测url" prop="url">
                            <el-input :autosize="{ minRows: 25, maxRows: 25}" type="textarea" v-model="platform.url"></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="edit_wechat_platform(platform.id,platform.name,platform.url)">修改</el-button>
            </div>
        </el-dialog>
        <el-dialog :visible.sync="platformDialog" title="公众号对应平台添加">
            <el-form label-width="140px" :model="platform" ref="platform">
                <el-row>
                    <el-col :span="10">
                        <el-form-item label="平台名字" prop="name">
                            <el-input v-model="platform.name"></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="检测url" prop="url">
                            <el-input :autosize="{ minRows: 25, maxRows: 25}" type="textarea" v-model="platform.url"></el-input>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="add_wechat_platform('platform')">添加</el-button>
            </div>
        </el-dialog>

        <!--结束-->


        <!--公众号对应平台划分-->
        <el-dialog :visible.sync="platform_list_Dialog" title="公众号对应平台划分">
            <el-form label-width="140px" :model="platform_list" ref="platform_list" inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item label="平台选择" prop="name">
                            <el-select v-model="platform_list.platform_id" placeholder="请选择">
                                <el-option v-for="item in platform_list_all" :key="item.id" :label="item.name" :value="item.id"> </el-option>
                            </el-select>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="公众号">
                            <el-checkbox-group v-model="platform_list.wechat_ids">
                                <el-checkbox v-for="item in wechat_list" :label="item.id" :key="item.id" >{{item.nick_name}}</el-checkbox>
                            </el-checkbox-group>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="wechat_to_platform('platform_list')">划分</el-button>
            </div>
        </el-dialog>
        <!--客服链接批量修改-->
        <el-dialog :visible.sync="service_link_update_Dialog" title="客服链接批量修改">
            <el-form label-width="140px"  inline>
                <el-row>
                    <el-col :span="24">
                        <el-form-item label="要修改的链接" prop="name">
                            <el-input style="width: 410px" v-model="service_url" ></el-input>
                        </el-form-item>
                    </el-col>
                    <el-col :span="24">
                        <el-form-item label="公众号">
                            <el-checkbox-group v-model="service_link_update_wechat_lists.wechat_ids">
                                <el-checkbox v-for="item in wechat_list" :label="item.id" :key="item.id" >{{item.nick_name}}</el-checkbox>
                            </el-checkbox-group>
                        </el-form-item>
                    </el-col>
                </el-row>
            </el-form>
            <div slot="footer" class="dialog-footer">
                <el-button type="primary" @click="handleAjax()">修改</el-button>
            </div>
        </el-dialog>

    </div>
</template>
<script>
    export default {
        data:function() {
            return {
                keyword:'',
                tableData: [],
                platformtableData: [],
                page:{
                    ep:10,
                    p:1,
                    pages:0,
                    total:0
                },
                service_url:'',
                creater_id:localStorage.getItem('creater_id'),
                //小组表单
                groupinfo:{
                    'wechat_id':0,
                    'name':'',
                    'user_group':'',
                },
                //用户组列表
                user_group_list:{},
                groupFormDialog:false,
                service_link_update_Dialog:false,
                //平台列表
                platform:{},
                platformDialogedit:false,
                platformDialog:false,
                platformlist:false,
                //平台划分
                platform_list:{
                    'wechat_ids':[]
                },
                //客服链接修改的公众号列表
                service_link_update_wechat_lists:{
                    'wechat_ids':[]
                },
                platform_list_Dialog:false,
                //平台总列表
                platform_list_all:{},
                //公众号列表
                wechat_list:[]
            };
        },
        created: function () {
            this.get_list('all');
        },
        methods: {
            //处理统改链接
            handleAjax(){
                let self = this;
                let info = {};
                info.url = self.service_url;
                info.ids = self.service_link_update_wechat_lists.wechat_ids;
                var url = self.$interfase.set_service_img_url;
                self.$confirm('确定执行该操作吗？', '替换操作', {
                    type: 'warning'
                }).then(function() {
                    self.$ajax.post(url, info).then(function(response) {
                        if(response.data.status == '000'){
                            self.groupFormDialog = false;
                            self.$message({
                                message: '替换成功',
                                type: 'success'
                            });
                        }else{
                            var error_msg = response.data.msg;
                            if(response.data.msg == undefined){
                                error_msg = response.data.data.error_message;
                            }
                            self.$message.error({
                                message: '错误：'+response.data.msg
                            });
                        }
                    })
                }).catch(function(){

                })
            },
            pageset:function(val){
                if(this.page.p != val){
                    this.page.p = val;
                    this.get_list('all');
                }
            },
            getCates(){
                let self = this;
                let info = {};
                self.$axios.post(self.$interfase.adminlogin, info).then(function (response){
                    let data = response.data.data;
                    if (data.length > 0) {
                    }
                });
            },
            objectSpanMethod({ row, column, rowIndex, columnIndex }) {
                if (columnIndex == 2) {
                    return {
                        rowspan: 2,
                        colspan: 1
                    };
                }
            },
            getAuthQrcodeUrl(){
                let self = this;
                self.$ajax.post(self.$interfase.get_auth_code).then(function (response){
                    if(response.data.status == '000'){
                        console.log(response.data.url);
                        window.location.href = response.data.url;
                        // head(response.data.url);
                    }
                });
            },
            //单个公众号重新拉取粉丝
            get_wechat_fans_new_list(nick_name){
                let self = this;
                  if(nick_name){
                      self.$ajax.post(self.$interfase.get_wechat_fans_new_list,{'nickname':nick_name}).then(function(response) {
                          if(response.data.status == '000'){
                              self.$message({
                                  message: '拉取成功',
                                  type: 'success'
                              });
                          }else{
                              self.$message.error({
                                  message: '错误：'+response.data.data.error_message
                              });
                          }
                      })
                  }else{
                      self.$message.error({
                          message: '公众号名称不正确'
                      });
                  }
            },
            //切换公众号所属组
            switch_wechat_group(info,message){
                let  self = this;
                var url = self.$interfase.switch_wechat_group;
                self.$confirm(message || '确定执行该操作吗？', '转让操作', {
                    type: 'warning'
                }).then(function() {
                    self.$ajax.post(url, info||{}).then(function(response) {
                        if(response.data.status == '000'){
                            self.groupFormDialog = false;
                            self.$message({
                                message: '转出成功',
                                type: 'success'
                            });
                            self.get_list('all');
                        }else{
                            self.$message.error({
                                message: '修改失败',
                            });
                        }
                    })
                }).catch(function(){})
            },
            //添加公众号对应平台
            add_wechat_platform(info){
                let  self = this;
                var url = self.$interfase.wechat_platform_handle;
                self.$ajax.post(url, self[info]||{}).then(function(response) {
                    if(response.data.status == '000'){
                        self.$refs[info].resetFields();
                        self.platformDialog = false;
                        self.platformlist = false;
                        self.$message({
                            message: '添加成功',
                            type: 'success'
                        });
                    }else{
                        self.$message.error({
                            message: '修改失败',
                        });
                    }
                })
            },
            //修改公众号的对应平台
            edit_wechat_platform(id,name,editurl){
                let  self = this;
                var url = self.$interfase.wechat_platform_handle;
                self.$ajax.post(url, {'id':id,'name':name,'url':editurl}).then(function(response) {
                    if(response.data.status == '000'){
                        self.platformDialogedit = false;
                        self.platformlist = false;
                        self.$message({
                            message: '添加成功',
                            type: 'success'
                        });
                    }else{
                        self.$message.error({
                            message: '修改失败',
                        });
                    }
                })
            },
            //删除公众号对应平台id
            del_wechat_platform(param){
                let  self = this;
                var id = param.id;
                var name = param.name;
                var url = self.$interfase.wechat_platform_del;
                self.$ajax.post(url, {'id':id,'name':name}).then(function(response) {
                    if(response.data.status == '000'){
                        self.platformlist = false;
                        self.$message({
                            message: '删除成功',
                            type: 'success'
                        });
                    }else{
                        self.$message.error({
                            message: '修改失败',
                        });
                    }
                })
            },
            //划分公众号对应平台
            wechat_to_platform(info){
                let  self = this;
                var url = self.$interfase.set_wechat_platform;
                self.$ajax.post(url, self[info]||{}).then(function(response) {
                    if(response.data.status == '000'){
                        self[info] = {
                            'wechat_ids':[]
                        },
                        self.platform_list_Dialog = false;
                        self.$message({
                            message: '添加成功',
                            type: 'success'
                        });
                    }
                })
            },
            //获取小组
            get_user_group_lilst(wechat){
                let self = this;
                self.groupFormDialog = true;
                self.groupinfo = {};
                self.groupinfo.wechat_id = wechat.id;
                self.groupinfo.name = wechat.nick_name;
                let info = {
                    'is_all' : true,
                };
                self.$ajax.post(self.$interfase.get_user_group_lilst,info).then(function (response){
                    if(response.data.status == '000'){
                        self.user_group_list = response.data.data;
                    }else{
                        self.$message.error({
                            message:'设置失败',
                        });
                    }
                });
            },
            //展开
            wechat_platform(wechat,action='',param={}){
                let self = this;
                self[wechat] = true;
                self.platform = {};
                if(action == 'platformDialogEdit'){
                    self.platform.name = param.name;
                    self.platform.id = param.id;
                    self.platform.url = param.url;
                    return;
                }


                //获取平台列表
                self.$ajax.post(self.$interfase.wechat_platform_list,{'limit':500}).then(function (response) {
                    if (response.data.status == '000') {
                        self.platform_list_all = response.data.data;
                        self.platformtableData = response.data.data;
                    }else{
                        self.$message.error({
                            message:'操作失败:'+response.data.msg,
                        });
                    }
                });
                if(action == 'platformList'){

                    return;
                }
                //获取公众号列表
                self.$ajax.post(self.$interfase.get_user_group_wehcats,{'limit':500}).then(function (response) {
                    if (response.data.status == '000') {
                        self.wechat_list = response.data.data.list;
                        self.service_link_update_wechat_list = response.data.data;
                    }else{
                        self.$message.error({
                            message:'操作失败:'+response.data.msg,
                        });
                    }
                });

            },
            //设置当前使用的公众号
            set_use_wechat(id,name){
                let self = this;
                let info = {'id':id};
                self.$ajax.post(self.$interfase.set_use_wechat,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message: '切换成功，当前使用：' + name,
                            type: 'success'
                        });
                        localStorage.setItem('use_wechat_name',name);
                        location.reload();
                    }else{
                        self.$message.error({
                            message: '现在正在使用或者切换失败：' + name,
                        });
                    }
                });
            },
            //获取公众号列表
            get_list(name){
                let self = this;
                let info = {};
                if(name != 'all' && name != ''){
                    info = {'name' : name};
                }
                info.page = self.page.p;
                info.limit = self.page.ep;
                self.$ajax.post(self.$interfase.get_empower_wechat_list,info).then(function (response){
                    if(response.data.status == '000'){
                        console.log(response.data.data.list);
                        self.tableData = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }
                });
            },
            //输出图片
            get_img(url){
                let self = this;
                return 'http://wechatadmins.weijuli8.com/admin/show_wechat_img?url='+ url;
            },
            //日期转换
            todate(unixtime){
                var now = new Date(unixtime * 1000); // 依情况进行更改 * 1
                y = now.getFullYear();
                m = now.getMonth() + 1;
                d = now.getDate();
                return y + "-" + (m < 10 ? "0" + m : m) + "-" + (d < 10 ? "0" + d : d) + " " + now.toTimeString().substr(0, 8);
            },
            handleSizeChange(val) {
                console.log(`每页 ${val} 条`);
            },
            handleCurrentChange(val) {
                console.log(`当前页: ${val}`);
            }
        }
    }
</script>
