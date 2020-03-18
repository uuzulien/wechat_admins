<template>
    <div class="row">
        <el-row>
            <el-col>
                <el-form :inline="true"  class="demo-form-inline">
                    <el-form-item label="公众号">
                        <el-input  v-model="gzh_name" @keyup.enter.native="selectInfo()" placeholder="公众号名字"></el-input>
                    </el-form-item>
                    <el-form-item label="操作人">
                        <el-input  v-model="handle_name" @keyup.enter.native="selectInfo()" placeholder="操作人姓名"></el-input>
                    </el-form-item>
                    <el-form-item label="发送状态">
                        <el-select v-model="sent_status_val" placeholder="请选择" >
                            <el-option value="">所有</el-option>
                            <el-option v-for="item in sent_status_list" :key="item.value" :label="item.label" :value="item.value"> </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="日期">
                        <el-date-picker v-model="time_data" type="daterange" start-placeholder="开始日期" end-placeholder="结束日期">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="selectInfo()">查询</el-button>
                    </el-form-item>
                </el-form>
            </el-col>
            <el-col offset="22">
                <el-button type="primary" @click="go_set_send_msg()">添加群发</el-button>
            </el-col>
            <el-col>
                <span v-if="creater_id == 0 || creater_id == 1" style="color: red">
                    今日<a style="color: #c4c4c4">未发送</a>的公众号数量:{{todaySentInfo.todayNoSentCount}}<br>
                    今日<a style="color: green">发送成功</a>的公众号数量:{{todaySentInfo.todaySentSuccessCount}}<br>
                    今日<a style="color: darkred">发送失败</a>的公众号数量:{{todaySentInfo.todaySentErrorCount}}
                </span>
            </el-col>
        </el-row>
        <el-row>&nbsp</el-row>
        <el-table :data="msg_lsit"style="width: 100%">
            <el-table-column prop="wechat_nick_name" label="所属公众号" width="180"></el-table-column>
            <el-table-column prop="handle_user_name" label="添加人"></el-table-column>
            <el-table-column prop="reply_type" label="群发选项" width="180">
                <template slot-scope="scope">
                    <el-row>
                        <span v-if="scope.row.reply_type == 'text'">文本消息</span>
                        <span v-else-if="scope.row.reply_type == 'mpnews'">图文消息</span>
                        <span v-else-if="scope.row.reply_type == 'voice'">语音</span>
                        <span v-else-if="scope.row.reply_type == 'image'">图片</span>
                    </el-row>
                    <el-row>
                        <span v-if="scope.row.wechat_public_num_id == 0">全部粉丝</span>
                        <span v-else>微信公众号标签，使用id为：{{ scope.row.wechat_public_num_id }}</span>
                    </el-row>
                </template>
            </el-table-column>
            <el-table-column prop="sent_time" label="发送时间"></el-table-column>
            <el-table-column prop="sent_num" label="发送人数">
                <template slot-scope="scope">
                    <span style="color:green;">{{ scope.row.sent_num }}</span>
                </template>
            </el-table-column>
            <el-table-column prop="create_time" label="创建时间" width="180"></el-table-column>
            <el-table-column label="操作">
                <template slot-scope="scope">
                    <el-tag v-if="scope.row.bendi_sent_status == 0">未发送</el-tag>
                    <el-tag v-else-if="scope.row.bendi_sent_status == 1"  type="warning">发送中</el-tag>
                    <el-tag v-else-if="scope.row.bendi_sent_status == 2"  type="success">发送成功</el-tag>
                    <el-tag v-else="" type="danger">发送失败</el-tag>

                    <el-row>
                        <el-button type="primary" size="mini" @click="go_edit(scope.row.id)">编辑</el-button>
                        <el-button type="primary" size="mini" @click="handleAjax({id:scope.row.id},'确定删除该群发消息吗？')">删除</el-button>
                    </el-row>
                </template>
            </el-table-column>
        </el-table>
        <el-col :span="6" :offset="18">
            <el-pagination @current-change="pageset" :total="page.total" :page-size="page.ep" :current-page="page.p" background layout="prev,pager,next,jumper"></el-pagination>
        </el-col>
    </div>
</template>
<script>
    export default {
        data:function(){
            return {
                creater_id:localStorage.getItem('creater_id'),
                todaySentInfo: {
                    "todayNoSentCount": '查询失败',
                    "todaySentSuccessCount": '查询失败',
                    "todaySentErrorCount": '查询失败'
                },
                msg_lsit:[],
                time_data:[],
                sent_status_val:'',
                sent_status_list:[
                    {
                        'label' : '未发送',
                        'value' : 0,
                    },
                    {
                        'label' : '发送中',
                        'value' : 1,
                    },
                    {
                        'label' : '发送成功',
                        'value' : 2,
                    },
                    {
                        'label' : '发送失败',
                        'value' : 3,
                    },
                ],
                start_time:'',
                end_time:'',
                select_name:'',
                appid:'',
                option_list:[],
                page:{
                    ep:10,
                    p:1,
                    pages:0,
                    total:0
                },

            }
        },
        created:function(){
            this.get_all_user_group_wechats();
            this.get_send_msg_list();
            this.getTodaySentInfo();
        },
        methods:{
            getTodaySentInfo(){
                //todaySentNum
                let self = this;
                let info={};
                self.$ajax.post(self.$interfase.group_sent_today_sent_info,info).then(function (response){
                    if(response.data.status == '000'){
                        self.todaySentInfo = response.data.data;
                    }else{
                        self.$message.error({
                            message:'获取当天发送信息失败:'+response.data.msg,
                        });
                    }
                });
            },
            //获取公众号列表
            get_all_user_group_wechats(){
                let self = this;
                self.$ajax.post(self.$interfase.get_user_group_wehcats).then(function (response){
                    if(response.data.status == '000'){
                        self.option_list = response.data.data.list;
                    }else{

                    }
                });
            },
            selectInfo(){
                let self = this;
                self.start_time = self.time_data[0];
                self.end_time = self.time_data[1];
                let info = {
                    'gzh_name':self.gzh_name,
                    'start_time':self.start_time,
                    'end_time':self.end_time,
                    'handle_name':self.handle_name,
                }
                info.page = self.page.p;
                info.limit = self.page.ep;
                info.bendi_sent_status = self.sent_status_val
                // console.log(info);
                self.$ajax.post(self.$interfase.send_all_msg_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'操作成功',
                            type:'success'
                        });
                        self.msg_lsit = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }else{
                        self.$message.error({
                            message:'更换失败',
                        });
                    }
                });
            },
            //获取群发消息列表
            get_send_msg_list(){
                let self = this;
                if(self.time_data){
                    self.start_time = self.time_conversion(self.time_data[0]);
                    self.end_time = self.time_conversion(self.time_data[1]);
                }
                let info = {
                    'start_time':self.start_time,
                    'end_time':self.end_time,
                    'appid':self.appid,
                };
                info.page = self.page.p;
                info.limit = self.page.ep;
                self.$ajax.post(self.$interfase.send_all_msg_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.$message({
                            message:'操作成功',
                            type:'success'
                        });
                        self.msg_lsit = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }else{
                        self.$message.error({
                            message:'更换失败',
                        });
                    }
                });

            },
            pageset:function(val){
                if(this.page.p != val){
                    this.page.p = val;
                    this.selectInfo();
                }
            },
            go_edit:function(id){
                this.$router.push({ path:'send_all_msg_edit/' + id + '/3' });
            },
            handleAjax: function(info, message) {
                let  self = this;
                var url = self.$interfase.del_all_msg;
                self.$confirm(message || '确定执行该操作吗？', '提示', {type: 'warning'}).then(function() {
                    self.$ajax.post(url, info||{}).then(function(response) {
                        if(response.data.status == '000'){
                            self.$message({
                                message:'操作成功',
                                type:'success'
                            });
                            self.get_send_msg_list();
                        }
                    })
                }).catch(function(){})
            },
            //时间转换
            time_conversion(dates){
                if(dates != undefined){
                    let s = new Date(dates);
                    let date = s.getFullYear() + '-' + (s.getMonth() + 1) + '-' + s.getDate();
                    return date;
                }else{
                    return '';
                }
            },
            //跳转页面
            go_set_send_msg(){
                this.$router.push('/send_all_msg');
            },
        }
    }
</script>
