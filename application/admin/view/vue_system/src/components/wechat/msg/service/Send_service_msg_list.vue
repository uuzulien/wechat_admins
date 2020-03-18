<template>
    <div class="row">
        <el-row>
            <el-col>
                <el-form :inline="true"  class="demo-form-inline">
                    <el-form-item label="公众号">
                        <el-input  v-model="gzh_name" @keyup.enter.native="selectInfo('gzhname')" placeholder="公众号名字"></el-input>
                    </el-form-item>
                    <el-form-item label="站点">
                        <el-select v-model="platform_id" placeholder="请选择">
                            <el-option value="">所有</el-option>
                            <el-option v-for="item in platform_list_all" :key="item.id" :label="item.name" :value="item.id"> </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="发送状态">
                        <el-select v-model="sent_status_val" placeholder="请选择" >
                            <el-option value="">所有</el-option>
                            <el-option v-for="item in sent_status_list" :key="item.value" :label="item.label" :value="item.value"> </el-option>
                        </el-select>
                    </el-form-item>
                    <el-form-item label="操作人">
                        <el-input  v-model="handle_name" @keyup.enter.native="selectInfo()" placeholder="操作人姓名"></el-input>
                    </el-form-item>
                    <el-form-item label="日期">
                        <el-date-picker
                            v-model="time_data"
                            type="daterange"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期"
                            >
                        </el-date-picker>
                    </el-form-item>

                    <el-form-item>
                        <el-button type="primary" @click="selectInfo()">查询</el-button>
                    </el-form-item>
                </el-form>
            </el-col>
            <el-col>
                <span v-if="creater_id == 0 || creater_id == 1" style="color: red">
                    今日<a style="color: #c4c4c4">未发送</a>的公众号数量:{{todaySentInfo.todayNoSentCount}}<br>
                    今日<a style="color: green">发送成功</a>的公众号数量:{{todaySentInfo.todaySentSuccessCount}}<br>
                    今日<a style="color: darkred">发送失败</a>的公众号数量:{{todaySentInfo.todaySentErrorCount}}
                </span>
            </el-col>
            <el-col offset="22">
                <el-button type="primary" @click="go_set_send_msg()">群发客服新消息</el-button>
            </el-col>
        </el-row>
        <el-row>&nbsp</el-row>
        <el-table :data="send_msg_list"style="width: 100%">
            <el-table-column prop="wechat_nick_name" label="所属公众号" width="180">
                <template slot-scope="scope">{{ scope.row.wechat_nick_name }}</template>
            </el-table-column>
            <el-table-column prop="wechat_nick_name" label="添加人" width="180">
                <template slot-scope="scope">{{ scope.row.handle_user_name }}</template>
            </el-table-column>
            <el-table-column prop="site_type" label="群发站点" width="180">
                <template slot-scope="scope">{{ scope.row.site_type_name }}-{{ scope.row.task_type_name }}</template>
            </el-table-column>
            <el-table-column prop="sent_time" label="预计发送">
                <template slot-scope="scope">
                    <p>时间：{{ scope.row.sent_time | formatDate }}</p>
                    <p>发送目标：{{ scope.row.sent_group_type }}</p>
                </template>
            </el-table-column>
            <el-table-column label="统计数据">
                <template slot-scope="scope">
                    <p>发送数：<span style="color:green">{{ scope.row.sent_num }}</span></p>
                    <p>点击量：{{ scope.row.click_num }}</p>
                    <p>点击率：{{ (scope.row.click_num / scope.row.sent_num)*100 }}%</p>
                </template>
            </el-table-column>
            <el-table-column label="充值数据">
                <template slot-scope="scope">
                    <p>充值金额：{{ scope.row.sum_amount }}</p>
                    <p>点击产值：{{ scope.row.click_num }}</p>
                </template>
            </el-table-column>
            <el-table-column prop="date" label="创建时间" width="180">
                <template slot-scope="scope">{{ scope.row.create_time | formatDate }}</template>
            </el-table-column>
            <el-table-column   prop="date" label="发送成功时间" width="180">
                <template slot-scope="scope" v-if="scope.row.sent_status == 2" >{{ scope.row.real_sent_time | formatDate }}</template>
            </el-table-column>
            <el-table-column label="操作">
                <template slot-scope="scope">
                    <el-row>
                        <el-tag v-if="scope.row.sent_status == 0">未发送</el-tag>
                        <el-tag v-else-if="scope.row.sent_status == 1" type="warning">发送中</el-tag>
                        <el-tag v-else-if="scope.row.sent_status == 2" type="success">发送成功</el-tag>
                        <el-tag v-else-if="scope.row.sent_status == 3" type="danger">发送失败</el-tag>
                    </el-row>
                    <el-row>
                        <el-button type="primary" size="mini" @click="go_edit(scope.row.id)">编辑</el-button>
                        <el-button type="primary" size="mini" @click="handleAjax({id:scope.row.id},'确定删除该客服群发消息吗？')">删除</el-button>
                    </el-row>
                </template>
            </el-table-column>
        </el-table>
        <el-col :span="6" :offset="18">
            <el-pagination @current-change="pageset" :total="page.total" :page-size="page.ep" :current-page="page.p" background layout="prev,pager,next,jumper"></el-pagination>
        </el-col>
        <br>
        <br>
        <br>
        <el-row v-if="creater_id == 0 || creater_id == 1">
            <el-col>
                <el-button type="info" @click="getNowSentInfo()">查看正在发送的数据</el-button>
            </el-col>
            <el-col v-if="serviceSentInfo == ''">
                <span style="color: red">当前暂无正在发送的数据,请点击按钮重新获取</span>
            </el-col>
            <el-col v-for="item in serviceSentInfo">
                公众号名字:<span style="color: red">{{item.gzh_nick_name}}</span>,当前发送数量:<span style="color: red">{{item.sent_num}}</span>,最后一次发送时间:<span style="color: red">{{item.last_time}}</span>
            </el-col>
        </el-row>
    </div>


</template>
<script>
    export default {
        data:function(){
            return {
                //平台总列表
                platform_list_all:{},
                time_data:[],
                creater_id:localStorage.getItem('creater_id'),
                todaySentInfo: {
                    "todayNoSentCount": '查询失败',
                    "todaySentSuccessCount": '查询失败',
                    "todaySentErrorCount": '查询失败'
                },
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
                platform_id:'',
                serviceSentInfo:[],
                send_msg_list:[],
                page:{
                    ep:10,
                    p:1,
                    pages:0,
                    total:0
                },
            }
        },
        created:function(){
            this.get_service_msg_list();
            this.getPlatformList();
            this.getTodaySentInfo();
        },
        methods:{
            getPlatformList(){
                var self = this;
                //获取平台列表
                self.$ajax.post(self.$interfase.wechat_platform_list,{'limit':500}).then(function (response) {
                    if (response.data.status == '000') {
                        self.platform_list_all = response.data.data;
                    }else{
                        self.$message.error({
                            message:'获取失败:'+response.data.msg,
                        });
                    }
                });
            },
            getTodaySentInfo(){
            //todaySentNum
                let self = this;
                let info={};
                self.$ajax.post(self.$interfase.group_sent_service_today_sent_info,info).then(function (response){
                    if(response.data.status == '000'){
                        self.todaySentInfo = response.data.data;
                    }else{
                        self.$message.error({
                            message:'获取当天发送信息失败:'+response.data.msg,
                        });
                    }
                });
            },
            getNowSentInfo(){
                let self = this;
                let info={};
                info.type = 'qfkf';
                self.$ajax.post(self.$interfase.getMsgSentResult,info).then(function (response){
                    if(response.data.status == '000'){
                        self.serviceSentInfo = response.data.data;
                        self.$message({
                            message:'获取成功',
                            type:'success'
                        });
                    }else{
                        self.$message.error({
                            message:'获取失败:'+response.data.msg,
                        });
                    }
                });
            },
            selectInfo(type){
                let self = this;
                let info={}
                if(self.time_data){
                    self.start_time = self.time_conversion(self.time_data[0]);
                    self.end_time = self.time_conversion(self.time_data[1]);
                }
                info.sent_status = self.sent_status_val
                info.gzh_name = self.gzh_name
                info.handle_name = self.handle_name
                info.platform_id = self.platform_id
                info.start_time = self.start_time
                info.end_time = self.end_time
                info.page = self.page.p;
                info.limit = self.page.ep;
                console.log(info);
                self.$ajax.post(self.$interfase.get_service_msg_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.send_msg_list = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }else{

                    }
                });
            },
            get_service_msg_list(){
                let self = this;
                if(self.time_data){
                    self.start_time = self.time_conversion(self.time_data[0]);
                    self.end_time = self.time_conversion(self.time_data[1]);
                }
                let info = {
                    'start_time':self.start_time,
                    'end_time':self.end_time,
                }
                info.page = self.page.p;
                info.limit = self.page.ep;
                console.log(info);
                self.$ajax.post(self.$interfase.get_service_msg_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.send_msg_list = response.data.data.list;
                        self.page.total = response.data.data.total;
                    }else{

                    }
                });
            },
            go_set_send_msg(){
                this.$router.push('/send_service_msg');
            },
            go_edit:function(id){
                this.$router.push({ path:'send_service_msg_edit/' + id + '/4' });
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
            handleAjax: function(info, message) {
                let  self = this;
                var url = self.$interfase.del_service_msg;
                self.$confirm(message || '确定执行该操作吗？', '提示', {type: 'warning'}).then(function() {
                    self.$ajax.post(url, info||{}).then(function(response) {
                        if(response.data.status == '000'){
                            self.$message({
                                message:'操作成功',
                                type:'success'
                            });
                            self.get_service_msg_list();
                        }
                    })
                }).catch(function(){})
            },
            pageset:function(val){
                if(this.page.p != val){
                    this.page.p = val;
                    // this.get_service_msg_list();
                    this.selectInfo();
                }
            },
        },
        filters: {
            formatDate: function (value) {
                console.log(value);
                let date = new Date(value);
                console.log(date);
                let y = date.getFullYear();
                let MM = date.getMonth() + 1;
                MM = MM < 10 ? ('0' + MM) : MM;
                let d = date.getDate();
                d = d < 10 ? ('0' + d) : d;
                let h = date.getHours();
                h = h < 10 ? ('0' + h) : h;
                let m = date.getMinutes();
                m = m < 10 ? ('0' + m) : m;
                let s = date.getSeconds();
                s = s < 10 ? ('0' + s) : s;
                return y + '-' + MM + '-' + d + ' ' + h + ':' + m + ':' + s;
            }
        },
    }
</script>
