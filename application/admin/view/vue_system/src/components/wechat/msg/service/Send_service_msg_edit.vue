<style>
    .img_card{
        height: 180px;
        width: 320px;
    }
</style>
<template>
    <div class="row">
        <el-breadcrumb separator="/">
            <el-breadcrumb-item :to="{ path: '/send_service_msg_list' }">群发客服消息</el-breadcrumb-item>
            <el-breadcrumb-item>群发客服消息设置</el-breadcrumb-item>
            <el-breadcrumb-item>{{ wechat_name }}</el-breadcrumb-item>
        </el-breadcrumb>
        <el-row>&nbsp</el-row>
        <el-form ref="form_data" :model="form_data" label-width="120px">
            <el-form-item label="站点">
<!--                <el-select v-model="form_data.site_type" placeholder="发布消息对应的战点">-->
<!--                    <el-option label="掌读" value="1"></el-option>-->
<!--                    <el-option label="掌中云" value="2"></el-option>-->
<!--                    <el-option label="网易" value="3"></el-option>-->
<!--                    <el-option label="火烧云" value="4"></el-option>-->
<!--                    <el-option label="阳光" value="5"></el-option>-->
<!--                    <el-option label="滕文" value="6"></el-option>-->
<!--                    <el-option label="掌文" value="7"></el-option>-->
<!--                    <el-option label="追书云" value="8"></el-option>-->
<!--                    <el-option label="文鼎" value="9"></el-option>-->
<!--                    <el-option label="阅文" value="10"></el-option>-->
<!--                </el-select>-->
                <el-select v-model="form_data.site_type" placeholder="请选择">
                    <el-option v-for="item in platform_list_all" :key="item.id" :label="item.name" :value="item.id"> </el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="任务类型">
                <el-select v-model="form_data.task_type" placeholder="任务类型">
                    <el-option label="站点链接" value="1"></el-option>
                    <el-option label="外部链接" value="2"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="消息类型">
                <el-select v-model="form_data.msg_type" placeholder="消息类型">
                    <el-option label="文本" value="text"></el-option>
                    <el-option label="图文" value="news"></el-option>
                </el-select>
            </el-form-item>
            <el-form-item label="消息内容">
                <el-row v-if="form_data.msg_type == 'news'">
                    <el-col :span="6">
                        <el-card>
                            <el-input style="padding-bottom: 10px" v-model="form_data.msg_content.title" placeholder="请输入标题"></el-input>
                            <el-input style="padding-bottom: 10px" v-model="form_data.msg_content.url" placeholder="请输入链接"></el-input>
                            <el-input style="padding-bottom: 10px" v-model="form_data.msg_content.description" placeholder="请输入描述"></el-input>
                            <img class="img_card" @click="get_material_img()" :src="get_img(form_data.msg_content.picurl)"/>
                        </el-card>
                    </el-col>
                </el-row>
                <el-row v-if="form_data.msg_type == 'text'" >
                    <el-col :span="12">
                        <el-input :autosize="{ minRows: 30, maxRows: 30}" @blur="checkhtmlatag(form_data.msg_content.text)" type="textarea" v-model="form_data.msg_content.text"></el-input>
                    </el-col>
                </el-row>
            </el-form-item>
            <el-form-item label="发送时间">
                <el-date-picker v-model="form_data.sent_time" type="datetime" placeholder="选择发送日期时间" align="right" :picker-options="pickerOptions"></el-date-picker>
            </el-form-item>
            <el-form-item label="测试粉丝id">
                <el-col :span="6">
                    <el-input v-model="form_data.touser" placeholder="如需提前测试，请输入测试粉丝id"></el-input>
                </el-col>
                <el-col :span="2">
                    <el-button type="primary" @click="send_msg_add('form_data','test')">发送测试</el-button>
                </el-col>
            </el-form-item>
            <el-form-item label="发送用户群">
                <el-select v-model="form_data.sent_group_type" placeholder="群发类型标签">
                    <el-option label="所有用户" value="1"></el-option>
                </el-select>
            </el-form-item>
        </el-form>
        <el-row>&nbsp</el-row>
        <div slot="footer" class="dialog-footer">
            <el-button type="primary" v-show="form_data.msg_type == 'text' && is_text_true"  @click="send_msg_add('form_data')">修改</el-button>
            <el-input style="padding-bottom: 10px" disabled v-show="form_data.msg_type == 'text' && is_text_true == false" value="消息内容里面的a标签有不正确的,修复后才可以提交."></el-input>
            <el-button type="primary" v-show="form_data.msg_type == 'news'"  @click="send_msg_add('form_data')">修改</el-button>
        </div>
        <el-dialog :visible.sync="infoFormDialog" title="选择图片素材">
            <el-row>
                <el-col :span="4" v-for="item in img_list" style="padding: 14px;" :offset="1">
                    <el-card>
                        <img height="120px" :src="get_img(item.link_url)" class="image" @click="set_use_img(item.link_url)">
                    </el-card>
                </el-col>
            </el-row>
        </el-dialog>

    </div>
</template>
<script>
    export default {
        data:function(){
            return {
                wechat_name:'',
                form_data: {
                    'site_type' : '',
                    'task_type' : '',
                    'msg_type' : '',
                    'sent_time' : '',
                    'msg_content': {},
                    'id' : '',
                },
                is_can_submit : true,
                is_text_true:false,
                infoFormDialog:false,
                img_list:[],
                //平台总列表
                platform_list_all:{},
                pickerOptions: {
                    shortcuts: [{
                        text: '10分钟后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 0.17);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '30分钟后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 0.5);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '1小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '2小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 2);
                            picker.$emit('pick', date);
                        }
                    }, {
                        text: '3小时后',
                        onClick(picker) {
                            const date = new Date();
                            date.setTime(date.getTime() + 3600 * 1000 * 3);
                            picker.$emit('pick', date);
                        }
                    }]
                },
            }
        },
        created:function(){
            this.wechat_name = localStorage.getItem('use_wechat_name');
            this.get_info();
            this.wechat_platform_list();
            this.is_can_submit = true;
        },
        methods:{
            wechat_platform_list(){
                let self = this;
                self.$ajax.post(self.$interfase.wechat_platform_list,{'limit':500}).then(function (response) {
                    if (response.data.status == '000') {
                        self.platform_list_all = response.data.data;
                    }else{
                        self.$message.error({
                            message:'操作失败:'+response.data.msg,
                        });
                    }
                });
            },
            get_info(){
                let self = this;
                var id = self.$route.params.id;
                var type = self.$route.params.type;
                //存在传参则取相应的数据
                if(id){
                    self.$ajax.post(self.$interfase.get_msg_info,{'id':id,'type':type}).then(function (response) {
                        console.log(response.data);
                        if (response.data.status == '000') {
                            self.form_data.id = id;
                            if(response.data.data.site_type == 0){
                                response.data.data.site_type = null;
                            }
                            self.form_data.site_type = response.data.data.site_type;
                            self.form_data.task_type = response.data.data.task_type.toString();
                            self.form_data.msg_type = response.data.data.msg_type.toString();
                            if(self.form_data.msg_type == 'text'){
                                self.form_data.msg_content.text = response.data.data.msg_content;
                            }else if(self.form_data.msg_type == 'news'){
                                self.form_data.msg_content.title = response.data.data.msg_content.title;
                                self.form_data.msg_content.url = response.data.data.msg_content.url;
                                self.form_data.msg_content.description = response.data.data.msg_content.description;
                                self.form_data.msg_content.picurl = response.data.data.msg_content.picurl;
                            }
                            let date = new Date();
                            self.form_data.sent_time = date.setTime(response.data.data.sent_time * 1000);
                            self.form_data.sent_group_type = response.data.data.sent_group_type.toString();
                        }else{
                            self.$message.error({
                                message:'操作失败:'+response.data.msg,
                            });
                        }
                    });
                }
            },
            //添加发送消息
            send_msg_add(formName,action){
                let self = this;
                if(action == 'test'){
                    if(self[formName].touser == '' || self[formName].touser == undefined){
                        self.$message.error({
                            message:'请填写正确的用户id',
                            duration:5000
                        });
                        return ;
                    }
                }
                self.$refs[formName].validate((valid) => {
                    if (valid) {
                        //临时变量储存数据
                        var msg_content = self[formName].msg_content;
                        var time = self[formName].sent_time;
                        self[formName].msg_content = JSON.stringify(self[formName].msg_content);
                        if(self[formName].sent_time != undefined){
                            self[formName].sent_time = self.time_conversion(self[formName].sent_time);
                        }
                        /***************以防用户连续点击多次**************/
                        if(self.is_can_submit === false){
                            self.$message.error({
                                message:'请等待上一步操作提交完成',
                                duration:5000
                            });
                            return;
                        }
                        /***************结束**************/

                        self.is_can_submit = false;
                        self.$ajax.post(self.$interfase.edit_service_msg,self[formName]).then(function (response) {
                            //复原数据，用于展示
                            self[formName].msg_content = msg_content;
                            self[formName].sent_time = time;
                            if (response.data.status == '000') {
                                if(self[formName].touser){
                                    //测试发送状态详情 获取
                                    if(response.data.status == '000'){
                                        self.$message({
                                            message:'发送成功',
                                            type:'success',
                                            duration:5000
                                        });
                                    }else{
                                        self.$message.error({
                                            message:'发送失败,状态码：' + response.data.data.errcode + '，状态信息：' + response.data.data.errmsg,
                                            duration:5000
                                        });
                                    }
                                }else{
                                    self.$message({
                                        message:'修改成功',
                                        type:'success',
                                        duration:5000
                                    });
                                }
                                self.$refs[formName].resetFields();
                                self.$router.push('/send_service_msg_list');
                            }else{
                                self.$message.error({
                                    message:'操作失败:'+response.data.msg,
                                });
                            }
                            self.is_can_submit = true;
                        });
                    } else {
                        console.log('error submit!!');
                        return false;
                    }
                });
            },
            //输出图片
            get_img(url){
                let self = this;
                return 'http://wechatadmins.weijuli8.com/admin/show_wechat_img?url='+ url;
            },
            //获取媒体素材的图片
            get_material_img(){
                let self = this;
                self.infoFormDialog = true;
                let info = {'file_type':'img'}
                self.$ajax.post(self.$interfase.get_material_list,info).then(function (response){
                    if(response.data.status == '000'){
                        self.img_list = response.data.data.list;
                    }
                });
            },
            //时间转换
            time_conversion(dates){
                if(dates != undefined){
                    let s = new Date(dates);
                    let date = s.getFullYear() + '-' + (s.getMonth() + 1) + '-' + s.getDate() +  ' '+ s.getHours() + ':' + s.getMinutes() + ':' + s.getSeconds();
                    return date;
                }else{
                    return '';
                }
            },
            //设置图片
            set_use_img(url){
                this.form_data.msg_content.picurl = url;
                this.infoFormDialog=false;
            },
            checkhtmlatag(str){
                let self = this;
                let info = {
                    'str' : str,
                    'site_type' : self.form_data.site_type,
                    'task_type' : self.form_data.task_type,
                };
                self.$ajax.post(self.$interfase.checkhtmlaTag,info).then(function (response) {
                    console.log(response.data);
                    if(response.data.status ==  '000'){
                        self.$message({
                            message:'文本检测通过',
                            type:'success'
                        });
                        self.is_text_true=true;
                    }else{
                        self.$message.error({
                            message:response.data.msg
                        });
                        self.is_text_true=false;
                    }
                    // self.is_text_true=true;
                });
            }
        }
    }
</script>
